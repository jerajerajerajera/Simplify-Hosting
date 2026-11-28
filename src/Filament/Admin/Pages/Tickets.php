<?php

declare(strict_types=1);

namespace Simplify\Hosting\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Simplify\Hosting\Models\Ticket;
use Simplify\Hosting\Services\Tickets as TicketService;

/** Tickets in Admin, for root admins and everyone whose role may handle tickets. Shown also while tickets are off. */
final class Tickets extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'tabler-lifebuoy';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'simplify-hosting-tickets';

    protected string $view = 'simplify-hosting::pages.tickets';

    public static function canAccess(): bool
    {
        return TicketService::canHandle(user());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return TicketService::canHandle(user()) && (TicketService::enabled() || Ticket::query()->exists());
    }

    public static function getNavigationLabel(): string
    {
        return __('simplify-hosting::tickets.title_staff');
    }

    /** In the Simplify group of Pelican's menu, with the other Simplify add-ons (Simplify's ADDONS.md, "Admin menu"). */
    public static function getNavigationGroup(): string
    {
        return 'Simplify';
    }

    /** Tickets waiting for support. */
    public static function getNavigationBadge(): ?string
    {
        $count = Ticket::query()->where('status', Ticket::OPEN)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getTitle(): string
    {
        return __('simplify-hosting::tickets.title_staff');
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
    }
}
