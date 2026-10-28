<?php

declare(strict_types=1);

namespace Simplify\Hosting;

use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Simplify\Hosting\Filament\Admin\Pages\HostingSettings;
use Simplify\Hosting\Services\Settings;

final class SimplifyHostingPlugin implements Plugin
{
    public const ID = 'simplify-hosting';

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() === 'admin') {
            $panel->pages([HostingSettings::class]);
        }
        if (in_array($panel->getId(), ['app', 'server'], true)) {
            // The client area in everyone's account menu, with or without Simplify.
            $panel->userMenuItems([
                Action::make('simplify-hosting-client-area')->label(fn () => __('simplify-hosting::hosting.client_area_link'))->icon('tabler-receipt')
                    ->url(fn () => Settings::get('client_area_url'), shouldOpenInNewTab: true)
                    ->visible(fn () => Settings::get('client_area_url') !== null),
            ]);
        }
    }

    public function boot(Panel $panel): void {}
}
