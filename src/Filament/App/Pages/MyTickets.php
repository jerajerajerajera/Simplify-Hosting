<?php

declare(strict_types=1);

namespace Simplify\Hosting\Filament\App\Pages;

use Filament\Pages\Page;
use Simplify\Hosting\Services\Tickets;

/** My tickets, from the account menu: every ticket of the customer and of their servers, also those without a server. */
final class MyTickets extends Page
{
    protected static ?string $slug = 'tickets';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'simplify-hosting::pages.my-tickets';

    public static function canAccess(): bool
    {
        return Tickets::enabled() && user() !== null;
    }

    public function getTitle(): string
    {
        return __('simplify-hosting::tickets.title_account');
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 404);
    }
}
