<?php

declare(strict_types=1);

namespace Simplify\Hosting\Filament\Server\Pages;

use App\Models\Server;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Simplify\Hosting\Services\Tickets;

/** Support in the server's menu: the tickets about this server and a new one, while the hoster has tickets on. */
final class Support extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'tabler-lifebuoy';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'support';

    protected string $view = 'simplify-hosting::pages.support';

    public static function canAccess(): bool
    {
        return Tickets::enabled() && user() !== null && Filament::getTenant() instanceof Server;
    }

    public static function getNavigationLabel(): string
    {
        return __('simplify-hosting::tickets.support');
    }

    /** How many tickets of this server have an answer the customer has not read. */
    public static function getNavigationBadge(): ?string
    {
        $server = Filament::getTenant();
        $user = user();
        if (! $server instanceof Server || $user === null || ! Tickets::enabled()) {
            return null;
        }
        $count = Tickets::ofCustomer($user, $server)->where('customer_unread', true)->where('status', '!=', 'closed')->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return __('simplify-hosting::tickets.support');
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 404);
    }

    public function server(): Server
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $server;
    }
}
