<?php

declare(strict_types=1);

namespace Simplify\Hosting\Livewire;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Simplify\Hosting\Services\BillingSystems;
use Simplify\Hosting\Services\Settings;

/**
 * The hoster's links and the host mode default. Shown on Hosting's own settings page and, with Simplify, in
 * Simplify settings › Add-ons (Simplify's ADDONS.md, "Settings"). Root admins only.
 *
 * @property Schema $form
 */
final class HostingSettingsForm extends Component implements HasSchemas
{
    use InteractsWithForms;

    private const T = 'simplify-hosting::hosting.';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
        $this->form->fill(self::current());
    }

    public static function canAccess(): bool
    {
        return (bool) user()?->isRootAdmin();
    }

    /** @return array<string, mixed> the saved settings, by field name */
    public static function current(): array
    {
        return array_combine(Settings::FIELDS, array_map(Settings::get(...), Settings::FIELDS));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components(self::sections());
    }

    /**
     * The links for customers and the host mode default; also asked in Simplify's setup.
     *
     * @return list<Section>
     */
    public static function sections(): array
    {
        $link = fn (string $key, string $placeholder, string $regex = Settings::LINK) => TextInput::make($key.'_url')
            ->label(__(self::T.$key))->helperText(__(self::T.$key.'_help'))->placeholder($placeholder)->maxLength(255)->regex($regex);

        // Not saved: picking a billing system and its address fills in the three links below.
        $fill = function (Get $get, Set $set): void {
            foreach (BillingSystems::links($get('billing_system'), $get('billing_address')) as $key => $url) {
                $set($key, $url);
            }
        };

        return [
            Section::make(__(self::T.'customers'))->schema([
                Select::make('billing_system')->label(__(self::T.'billing_system'))->helperText(__(self::T.'billing_system_help'))
                    ->options(array_combine($names = array_keys(BillingSystems::LINKS), $names))->dehydrated(false)->live()->afterStateUpdated($fill),
                TextInput::make('billing_address')->label(__(self::T.'billing_address'))->placeholder('https://billing.example.com')->maxLength(200)
                    ->regex(Settings::LINK)->dehydrated(false)->live(onBlur: true)->afterStateUpdated($fill),
                $link('billing', 'https://billing.example.com/clientarea.php?action=productdetails&id={external_id}', Settings::BILLING),
                $link('client_area', 'https://billing.example.com/clientarea.php'),
                $link('order', 'https://billing.example.com/store'),
            ]),
            Section::make(__(self::T.'admins'))->schema([
                Toggle::make('host_mode')->label(__(self::T.'host_mode'))->helperText(__(self::T.'host_mode_help')),
            ]),
        ];
    }

    /** Saves the form; also called by Save in the page's title bar and by Simplify's Save for unsaved changes. */
    #[On('simplify-hosting-settings-save')]
    public function save(): void
    {
        abort_unless(self::canAccess(), 403);
        Settings::save($this->form->getState());
        Notification::make()->title(__(self::T.'saved'))->success()->send();
    }

    public function render(): View
    {
        return view('simplify-hosting::livewire.settings-form');
    }
}
