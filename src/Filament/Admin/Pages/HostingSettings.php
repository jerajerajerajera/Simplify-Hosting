<?php

declare(strict_types=1);

namespace Simplify\Hosting\Filament\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Simplify\Core\Addons\Addon;
use Simplify\Hosting\Livewire\HostingSettingsForm;

/**
 * Hosting's own settings page, with the same form as Simplify settings › Add-ons. With a Simplify that shows add-on
 * settings there (add-on API 8), the page leaves the menu; it still opens by its address.
 */
final class HostingSettings extends Page
{
    private const T = 'simplify-hosting::hosting.';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-receipt';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'simplify-hosting';

    protected string $view = 'simplify-hosting::settings';

    public static function canAccess(): bool
    {
        return HostingSettingsForm::canAccess();
    }

    /** Whether Simplify shows Hosting's settings in its own settings (Simplify's ADDONS.md, "Settings"). */
    public static function inSimplifySettings(): bool
    {
        return class_exists(Addon::class) && defined(Addon::class.'::API') && self::coreApi() >= 8;
    }

    /** The add-on API of the installed Simplify, read at run time: the panel may have an older or newer core than the one checked against. */
    private static function coreApi(): int
    {
        return (int) constant(Addon::class.'::API');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! self::inSimplifySettings();
    }

    public static function getNavigationLabel(): string
    {
        return __(self::T.'title');
    }

    /** In the Simplify group of Pelican's menu, with the other Simplify add-ons (Simplify's ADDONS.md, "Admin menu"). */
    public static function getNavigationGroup(): string
    {
        return 'Simplify';
    }

    public function getTitle(): string
    {
        return __(self::T.'title');
    }

    protected function getHeaderActions(): array
    {
        // Save in the head of the page, as on Simplify's own pages; the form saves itself on this event.
        return [
            Action::make('save')->label(__(self::T.'save'))->icon('tabler-device-floppy')
                ->dispatch('simplify-hosting-settings-save')->keyBindings(['mod+s']),
        ];
    }
}
