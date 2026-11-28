<?php

declare(strict_types=1);

namespace Simplify\Hosting\Livewire;

use App\Enums\SubuserPermission;
use App\Models\Server;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Simplify\Hosting\Models\Ticket;
use Simplify\Hosting\Services\Settings;
use Simplify\Hosting\Services\Tickets;

/**
 * The tickets on one page: a list, a new ticket and one conversation. The same component serves the server's Support
 * page (mode "server"), My tickets in the account menu ("account") and Tickets in Admin ("staff"). Every action checks
 * again who may do it.
 */
final class TicketDesk extends Component
{
    private const T = 'simplify-hosting::tickets.';

    public const FILTERS = ['open', 'answered', 'closed', 'mine', 'all'];

    #[Locked]
    public string $mode = 'account';

    /** The id of the server of the Support page. */
    #[Locked]
    public ?int $serverId = null;

    #[Url]
    public ?int $ticket = null;

    #[Url]
    public bool $new = false;

    #[Url]
    public string $filter = 'open';

    public string $search = '';

    public string $subject = '';

    public string $category = '';

    /** For a new ticket from My tickets: the id of one of the customer's servers, '' for none. */
    public string $about = '';

    public string $message = '';

    public string $priority = 'normal';

    public bool $attachLog = true;

    /** A text another add-on sent along (Tickets::draft), such as an explained error. */
    public string $log = '';

    public string $reply = '';

    public bool $internal = false;

    public function mount(string $mode, ?int $serverId = null): void
    {
        abort_unless(in_array($mode, ['server', 'account', 'staff'], true), 404);
        $this->mode = $mode;
        $this->serverId = $mode === 'server' ? $serverId : null;
        abort_unless($this->allowed(), 403);
        if (! in_array($this->filter, self::FILTERS, true)) {
            $this->filter = 'open';
        }
        Tickets::closeStale();
        if ($this->new && ($draft = Tickets::takeDraft()) !== null) {
            $this->subject = $draft['subject'];
            $this->log = $draft['log'];
            $this->attachLog = false;
            if ($this->mode === 'account' && $draft['server'] !== null) {
                $this->about = (string) $draft['server'];
            }
        }
        if ($this->ticket !== null) {
            $this->show($this->ticket);
        }
    }

    public function staff(): bool
    {
        return $this->mode === 'staff';
    }

    private function user(): User
    {
        $user = user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function allowed(): bool
    {
        $user = user();
        if (! $user instanceof User) {
            return false;
        }
        if ($this->staff()) {
            return Tickets::canHandle($user);
        }
        if (! Tickets::enabled()) {
            return false;
        }

        return $this->mode === 'account' || ($this->server() !== null && $user->canAccessTenant($this->server()));
    }

    public function server(): ?Server
    {
        return $this->serverId === null ? null : Server::query()->find($this->serverId);
    }

    /** @return Builder<Ticket> */
    private function visible(): Builder
    {
        return $this->staff() ? Ticket::query() : Tickets::ofCustomer($this->user(), $this->server());
    }

    private function current(): ?Ticket
    {
        return $this->ticket === null ? null : $this->visible()->with(['user', 'server', 'assignee'])->find($this->ticket);
    }

    public function show(int $id): void
    {
        abort_unless($this->allowed(), 403);
        $ticket = $this->visible()->find($id);
        if ($ticket === null) {
            $this->ticket = null;

            return;
        }
        $this->ticket = $ticket->id;
        $this->new = false;
        $this->reply = '';
        $this->internal = false;
        $ticket->forceFill($this->staff() ? ['staff_unread' => false] : ['customer_unread' => false])->save();
    }

    public function back(): void
    {
        $this->ticket = null;
        $this->new = false;
        $this->resetValidation();
    }

    public function start(): void
    {
        abort_unless($this->allowed() && ! $this->staff(), 403);
        $this->ticket = null;
        $this->new = true;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, self::FILTERS, true) ? $filter : 'open';
        $this->ticket = null;
    }

    /** @return array<int, string> the customer's own servers, for a ticket from My tickets */
    public function servers(): array
    {
        return Server::query()->where('owner_id', $this->user()->id)->orderBy('name')->pluck('name', 'id')->all();
    }

    public function create(): void
    {
        abort_unless($this->allowed() && ! $this->staff(), 403);
        $user = $this->user();
        $categories = Settings::categories();
        $this->validate([
            'subject' => ['required', 'string', 'max:'.Tickets::SUBJECT],
            'message' => ['required', 'string', 'max:'.Tickets::BODY],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'category' => $categories === [] ? ['nullable'] : ['required', Rule::in($categories)],
            'about' => $this->mode === 'account' && ! Settings::get('tickets_without_server')
                ? ['required', Rule::in(array_map('strval', array_keys($this->servers())))]
                : ['nullable', Rule::in(['', ...array_map('strval', array_keys($this->servers()))])],
        ], [], [
            'subject' => __(self::T.'subject'),
            'message' => __(self::T.'message'),
            'category' => __(self::T.'category'),
            'priority' => __(self::T.'priority'),
            'about' => __(self::T.'server'),
        ]);
        if (Tickets::ofCustomer($user)->where('user_id', $user->id)->where('status', '!=', Ticket::CLOSED)->count() >= Tickets::OPEN_LIMIT) {
            $this->addError('subject', __(self::T.'too_many', ['count' => Tickets::OPEN_LIMIT]));

            return;
        }
        $server = $this->mode === 'server' ? $this->server() : ($this->about === '' ? null : Server::query()->where('owner_id', $user->id)->find((int) $this->about));
        $log = trim($this->log);
        if ($this->attachLog && $server !== null && ($console = Tickets::consoleLog($server, $user)) !== null) {
            $log = trim($log."\n\n".$console);
        }
        $ticket = Tickets::open($user, $server, $this->subject, $categories === [] ? null : $this->category, $this->message, $log === '' ? null : $log, $this->priority);
        $this->reset('subject', 'category', 'about', 'message', 'log', 'priority');
        $this->attachLog = true;
        $this->new = false;
        $this->ticket = $ticket->id;
        Notification::make()->title(__(self::T.'sent'))->success()->send();
    }

    public function send(): void
    {
        $ticket = $this->current();
        abort_unless($this->allowed() && $ticket !== null, 403);
        $this->validate(['reply' => ['required', 'string', 'max:'.Tickets::BODY]], [], ['reply' => __(self::T.'reply')]);
        Tickets::reply($ticket, $this->user(), $this->reply, $this->staff(), $this->staff() && $this->internal);
        $this->reply = '';
        Notification::make()->title(__($this->staff() && $this->internal ? self::T.'note_saved' : self::T.'sent'))->success()->send();
        $this->internal = false;
    }

    public function close(): void
    {
        $ticket = $this->current();
        abort_unless($this->allowed() && $ticket !== null, 403);
        Tickets::setStatus($ticket, Ticket::CLOSED);
    }

    public function status(string $status): void
    {
        $ticket = $this->current();
        abort_unless($this->staff() && $this->allowed() && $ticket !== null, 403);
        Tickets::setStatus($ticket, $status);
    }

    public function prioritize(string $priority): void
    {
        $ticket = $this->current();
        abort_unless($this->staff() && $this->allowed() && $ticket !== null, 403);
        Tickets::setPriority($ticket, $priority);
    }

    public function assign(string $userId): void
    {
        $ticket = $this->current();
        abort_unless($this->staff() && $this->allowed() && $ticket !== null, 403);
        Tickets::assign($ticket, $userId === '' ? null : (int) $userId);
    }

    /** @return Collection<int, Ticket> */
    private function rows(): Collection
    {
        $user = $this->user();

        return $this->visible()->with(['user', 'server', 'assignee'])
            ->when($this->staff() && $this->filter !== 'all', fn (Builder $query) => $this->filter === 'mine'
                ? $query->where('assigned_to', $user->id)->where('status', '!=', Ticket::CLOSED)
                : $query->where('status', $this->filter))
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('subject', 'like', '%'.addcslashes(trim($this->search), '%_\\').'%')
                ->orWhere('id', (int) ltrim(trim($this->search), '#'))))
            ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END', [Ticket::CLOSED])
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderByDesc('last_reply_at')->limit(100)->get();
    }

    /** @return array<string, int> how many tickets each filter holds, for Admin */
    private function counts(): array
    {
        $counts = Ticket::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($total) => (int) $total)->all();
        $counts['mine'] = Ticket::query()->where('assigned_to', $this->user()->id)->where('status', '!=', Ticket::CLOSED)->count();

        return $counts;
    }

    public function render(): View
    {
        $ticket = $this->current();
        $staffIds = $this->staff() ? Tickets::staffIds() : [];

        return view('simplify-hosting::livewire.ticket-desk', [
            'tickets' => $ticket === null && ! $this->new ? $this->rows() : null,
            'current' => $ticket,
            'messages' => $ticket?->messages()->with('user')->when(! $this->staff(), fn ($query) => $query->where('internal', false))->get(),
            'counts' => $this->staff() ? $this->counts() : [],
            'staffNames' => $staffIds === [] ? [] : User::query()->whereIn('id', $staffIds)->orderBy('username')->pluck('username', 'id')->all(),
            'categories' => Settings::categories(),
            'servers' => $this->mode === 'account' && $this->new ? $this->servers() : [],
            'withoutServer' => (bool) Settings::get('tickets_without_server'),
            'intro' => Settings::get('ticket_intro'),
            'canLog' => $this->mode === 'server' && $this->server() !== null && $this->user()->can(SubuserPermission::ControlConsole, $this->server()),
        ]);
    }
}
