<?php

declare(strict_types=1);

namespace Simplify\Hosting\Services;

use App\Enums\SubuserPermission;
use App\Models\Role;
use App\Models\Server;
use App\Models\User;
use App\Repositories\Daemon\DaemonServerRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Simplify\Hosting\Filament\Admin\Pages\Tickets as StaffTickets;
use Simplify\Hosting\Filament\App\Pages\MyTickets;
use Simplify\Hosting\Filament\Server\Pages\Support;
use Simplify\Hosting\Models\Ticket;
use Simplify\Hosting\Models\TicketMessage;
use Throwable;

/**
 * Support tickets: who may see and handle them, and what happens when someone writes. Support is a root admin or
 * anyone whose role has the permission "Handle" under "Ticket" (People › Roles).
 */
final class Tickets
{
    /** The role permission, as Pelican stores it: "<permission> <model>". */
    public const PERMISSION = 'handle ticket';

    public const SUBJECT = 120;

    public const BODY = 5000;

    public const LOG = 20000;

    /** A customer has at most this many tickets open at once. */
    public const OPEN_LIMIT = 10;

    private const DRAFT = 'simplify-hosting.ticket-draft';

    private const T = 'simplify-hosting::tickets.';

    public static function enabled(): bool
    {
        return (bool) Settings::get('tickets');
    }

    public static function canHandle(?User $user): bool
    {
        return $user !== null && ($user->isRootAdmin() || $user->can(self::PERMISSION));
    }

    /** @return list<int> everyone who handles tickets */
    public static function staffIds(): array
    {
        return User::query()->whereHas('roles', fn (Builder $roles) => $roles->where('name', Role::ROOT_ADMIN)
            ->orWhereHas('permissions', fn (Builder $permissions) => $permissions->where('name', self::PERMISSION)))
            ->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * The tickets a customer sees: their own, and every ticket about a server they own.
     *
     * @return Builder<Ticket>
     */
    public static function ofCustomer(User $user, ?Server $server = null): Builder
    {
        return Ticket::query()
            ->when($server, fn (Builder $query) => $query->where('server_id', $server->id))
            ->where(fn (Builder $query) => $query->where('user_id', $user->id)
                ->orWhereIn('server_id', Server::query()->where('owner_id', $user->id)->select('id')));
    }

    public static function customerSees(User $user, Ticket $ticket): bool
    {
        return self::ofCustomer($user)->whereKey($ticket->id)->exists();
    }

    /** Opens a ticket with its first message, and tells support. */
    public static function open(User $user, ?Server $server, string $subject, ?string $category, string $body, ?string $log = null, string $priority = 'normal'): Ticket
    {
        $ticket = DB::transaction(function () use ($user, $server, $subject, $category, $body, $log, $priority) {
            $ticket = Ticket::query()->create([
                'user_id' => $user->id,
                'server_id' => $server?->id,
                'subject' => mb_substr(trim($subject), 0, self::SUBJECT),
                'category' => $category,
                'status' => Ticket::OPEN,
                'priority' => in_array($priority, Ticket::PRIORITIES, true) ? $priority : 'normal',
                'staff_unread' => true,
                'customer_unread' => false,
                'last_reply_at' => now(),
            ]);
            self::message($ticket, $user, $body, false, false, $log);

            return $ticket;
        });
        self::tellStaff($ticket, $user, __(self::T.'notify_new', ['id' => $ticket->id]));

        return $ticket;
    }

    /**
     * A reply in a ticket. From support it waits for the customer; an internal note changes nothing for the customer.
     * From the customer it is support's turn again, also on a closed ticket.
     */
    public static function reply(Ticket $ticket, User $user, string $body, bool $staff, bool $internal = false): TicketMessage
    {
        $internal = $staff && $internal;
        $message = self::message($ticket, $user, $body, $staff, $internal);
        if ($internal) {
            return $message;
        }
        $ticket->forceFill($staff
            ? ['status' => Ticket::ANSWERED, 'customer_unread' => true, 'last_reply_at' => now(), 'closed_at' => null]
            : ['status' => Ticket::OPEN, 'staff_unread' => true, 'last_reply_at' => now(), 'closed_at' => null])->save();
        $staff
            ? self::tellCustomer($ticket, __(self::T.'notify_answered', ['id' => $ticket->id]))
            : self::tellStaff($ticket, $user, __(self::T.'notify_reply', ['id' => $ticket->id]));

        return $message;
    }

    public static function setStatus(Ticket $ticket, string $status): void
    {
        if (! in_array($status, Ticket::STATUSES, true) || $ticket->status === $status) {
            return;
        }
        $ticket->forceFill(['status' => $status, 'closed_at' => $status === Ticket::CLOSED ? now() : null])->save();
    }

    public static function setPriority(Ticket $ticket, string $priority): void
    {
        if (in_array($priority, Ticket::PRIORITIES, true)) {
            $ticket->forceFill(['priority' => $priority])->save();
        }
    }

    /** Hands a ticket to someone of support, or to nobody. */
    public static function assign(Ticket $ticket, ?int $userId): void
    {
        if ($userId !== null && ! in_array($userId, self::staffIds(), true)) {
            return;
        }
        $ticket->forceFill(['assigned_to' => $userId])->save();
    }

    /** With the setting on: tickets that waited a week for the customer close. */
    public static function closeStale(): void
    {
        if (! Settings::get('tickets_auto_close')) {
            return;
        }
        Ticket::query()->where('status', Ticket::ANSWERED)->where('last_reply_at', '<', now()->subDays(7))
            ->update(['status' => Ticket::CLOSED, 'closed_at' => now()]);
    }

    /** The last 100 console lines, for someone who may use the server's console; null when Wings does not answer. */
    public static function consoleLog(Server $server, User $user): ?string
    {
        if (! $user->can(SubuserPermission::ControlConsole, $server)) {
            return null;
        }
        try {
            $lines = app(DaemonServerRepository::class)->setServer($server)->getHttpClient()
                ->timeout(5)->get('/api/servers/'.$server->uuid.'/logs', ['size' => 100])->throw()->json('data');
        } catch (Throwable) {
            return null;
        }
        $text = implode("\n", array_map(fn (string $line) => (string) preg_replace('/\e\[[0-9;?]*[A-Za-z]/', '', $line), array_filter((array) $lines, 'is_string')));

        return trim($text) === '' ? null : mb_substr($text, -self::LOG);
    }

    /**
     * For another add-on, such as Explain: a new ticket with a subject and a text (for example an explained error with
     * its log) filled in. Returns where the customer writes it, or null while tickets are off. The text is kept in the
     * session, so it never shows in an address.
     */
    public static function draft(?Server $server, string $subject, string $text): ?string
    {
        if (! self::enabled() || user() === null) {
            return null;
        }
        session()->put(self::DRAFT, [
            'server' => $server?->id,
            'subject' => mb_substr(trim($subject), 0, self::SUBJECT),
            'log' => mb_substr($text, 0, self::LOG),
        ]);

        return $server !== null
            ? Support::getUrl(['new' => 1], panel: 'server', tenant: $server)
            : MyTickets::getUrl(['new' => 1], panel: 'app');
    }

    /** @return array{server: int|null, subject: string, log: string}|null the draft another add-on left, once */
    public static function takeDraft(): ?array
    {
        $draft = session()->pull(self::DRAFT);

        return is_array($draft) ? $draft : null;
    }

    public static function staffUrl(Ticket $ticket): string
    {
        return StaffTickets::getUrl(['ticket' => $ticket->id], panel: 'admin');
    }

    public static function customerUrl(Ticket $ticket): string
    {
        return MyTickets::getUrl(['ticket' => $ticket->id], panel: 'app');
    }

    private static function message(Ticket $ticket, User $user, string $body, bool $staff, bool $internal, ?string $log = null): TicketMessage
    {
        return TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'staff' => $staff,
            'internal' => $internal,
            'body' => mb_substr(trim($body), 0, self::BODY),
            'log' => $log === null || trim($log) === '' ? null : mb_substr($log, 0, self::LOG),
        ]);
    }

    /** The one it is assigned to, otherwise everyone of support; never the writer. */
    private static function tellStaff(Ticket $ticket, User $writer, string $title): void
    {
        $ids = $ticket->assigned_to !== null ? [$ticket->assigned_to] : self::staffIds();
        self::tell(array_values(array_diff($ids, [$writer->id])), $title, $ticket, self::staffUrl($ticket));
    }

    private static function tellCustomer(Ticket $ticket, string $title): void
    {
        self::tell([$ticket->user_id], $title, $ticket, self::customerUrl($ticket));
    }

    /**
     * In the bell, and as an app notification on phones where Simplify's push is on (Simplify's ADDONS.md, "simplify.push").
     *
     * @param  list<int>  $ids
     */
    private static function tell(array $ids, string $title, Ticket $ticket, string $url): void
    {
        $users = User::query()->whereIn('id', $ids)->get();
        if ($users->isEmpty()) {
            return;
        }
        try {
            $notification = Notification::make()->title($title)->body($ticket->subject)->icon('tabler-lifebuoy')
                ->actions([Action::make('open')->label(__(self::T.'open'))->url($url)->markAsRead()]);
            // Right away, not through the queue: many panels run no queue worker, and a ticket must not wait for one.
            foreach ($users as $recipient) {
                $recipient->notifyNow($notification->toDatabase());
            }
            if (Event::hasListeners('simplify.push')) {
                event('simplify.push', [[
                    'users' => $users->pluck('id')->all(),
                    'title' => $title,
                    'body' => $ticket->subject,
                    'url' => $url,
                    'tag' => 'simplify-hosting-ticket-'.$ticket->id,
                ]]);
            }
        } catch (Throwable $e) {
            // A notification that fails must not lose the message that was just written.
            report($e);
        }
    }
}
