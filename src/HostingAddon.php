<?php

declare(strict_types=1);

namespace Simplify\Hosting;

use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Simplify\Core\Addons\Addon;
use Simplify\Core\Addons\Button;
use Simplify\Core\Overview\AdminTree;
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

        if ($url === null) {
            return [];
        }

        // A suspended server mostly means an open invoice: newer Simplify versions show a primary link on its card.
        return [$server->isSuspended()
            ? new Button(__('simplify-hosting::hosting.pay_invoice'), $url, 'tabler-receipt', true)
            : new Button(__('simplify-hosting::hosting.upgrade'), $url)];
    }

    public function customerLinks(User $user): array
    {
        // A link with an icon also goes into Simplify's account menu (Simplify 1.5.2+): only the client area, as in 0.7.0.
        $links = [];
        foreach (['client_area_url' => ['client_area_link', 'tabler-receipt'], 'order_url' => ['order_link', null]] as $key => [$label, $icon]) {
            if ($url = Settings::get($key)) {
                $links[] = new Button(__('simplify-hosting::hosting.'.$label), $url, $icon);
            }
        }

        return $links;
    }

    /** Simplify's host mode, before Simplify had its Admin page (Simplify 1.5.5 and older). */
    public function hostModeDefault(): bool
    {
        return (bool) Settings::get('host_mode');
    }

    /**
     * Admins of a panel with more than one node start on the Admin page (Pelican's Health page) once per session, with
     * the host mode setting on (Simplify 1.5.6+, add-on API 5). After that the overview opens as usual.
     */
    public function startUrl(User $user): ?string
    {
        if (! $user->isAdmin() || ! Settings::get('host_mode') || ! class_exists(AdminTree::class)
            || session()->get('simplify-hosting.started') || Node::query()->count() < 2) {
            return null;
        }
        session()->put('simplify-hosting.started', true);
        $sections = AdminTree::build();
        foreach ($sections as $section) {
            if ($section['key'] === 'health') {
                return $section['pages'][0]['url'];
            }
        }

        return $sections[0]['pages'][0]['url'] ?? null;
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
