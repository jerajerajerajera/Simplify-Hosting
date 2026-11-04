<?php

declare(strict_types=1);

namespace Simplify\Hosting\Providers;

use App\Enums\HeaderActionPosition;
use App\Filament\Server\Pages\Console;
use App\Models\Server;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use Simplify\Hosting\HostingAddon;
use Simplify\Hosting\Services\Settings;

final class HostingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Simplify reads this tag to list its add-ons. Without Simplify nothing reads it and HostingAddon is never loaded.
        $this->app->tag([HostingAddon::class], 'simplify.addons');
    }

    public function boot(): void
    {
        // With Simplify the link sits in the server page's ⋯ menu (HostingAddon::serverLinks); without it on the console.
        if (class_exists('Simplify\Core\SimplifyPlugin')) {
            return;
        }
        Console::registerCustomHeaderActions(HeaderActionPosition::Before, Action::make('simplify-hosting-billing')
            ->label(fn () => __('simplify-hosting::hosting.upgrade'))->icon('tabler-receipt')->color('gray')->button()
            ->url(fn () => self::billing(), shouldOpenInNewTab: true)
            ->visible(fn () => self::billing() !== null));
    }

    /** The billing page of the server this console belongs to. */
    private static function billing(): ?string
    {
        $server = Filament::getTenant();

        return $server instanceof Server ? Settings::billing($server) : null;
    }
}
