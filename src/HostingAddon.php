<?php

declare(strict_types=1);

namespace Simplify\Hosting;

use App\Models\Server;
use App\Models\User;
use Simplify\Core\Addons\Addon;
use Simplify\Core\Addons\Button;
use Simplify\Hosting\Filament\Admin\Pages\HostingSettings;
use Simplify\Hosting\Services\Settings;

/**
 * How Hosting shows up inside Simplify. Only loaded when Simplify asks for its add-ons, so Hosting also works
 * without Simplify.
 */
final class HostingAddon extends Addon
{
    public function id(): string
    {
        return SimplifyHostingPlugin::ID;
    }

    public function name(): string
    {
        return 'Hosting';
    }

    public function description(): string
    {
        return __('simplify-hosting::hosting.description');
    }

    public function icon(): string
    {
        return 'tabler-receipt';
    }

    public function settingsUrl(): ?string
    {
        return HostingSettings::canAccess() ? HostingSettings::getUrl(panel: 'admin') : null;
    }

    public function serverLinks(Server $server): array
    {
        $url = Settings::billing($server);

        return $url === null ? [] : [new Button(__('simplify-hosting::hosting.upgrade'), $url)];
    }

    public function customerLinks(User $user): array
    {
        $links = [];
        foreach (['client_area_url' => 'client_area_link', 'order_url' => 'order_link'] as $key => $label) {
            if ($url = Settings::get($key)) {
                $links[] = new Button(__('simplify-hosting::hosting.'.$label), $url);
            }
        }

        return $links;
    }

    public function hostModeDefault(): bool
    {
        return (bool) Settings::get('host_mode');
    }

    /** In Simplify's setup (Simplify 1.3+, add-on API 3): the hoster's links and the host mode default. */
    public function setupFields(): array
    {
        return HostingSettings::canAccess() ? HostingSettings::sections() : [];
    }

    public function setupState(): array
    {
        return HostingSettings::current();
    }

    public function saveSetup(array $state): void
    {
        if (HostingSettings::canAccess()) {
            Settings::save($state);
        }
    }
}
