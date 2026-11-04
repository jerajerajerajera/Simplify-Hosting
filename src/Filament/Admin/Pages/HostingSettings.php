<?php

declare(strict_types=1);

namespace Simplify\Hosting\Filament\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Simplify\Hosting\Services\BillingSystems;
use Simplify\Hosting\Services\Settings;

/**
 * Where a root admin enters the hoster's links and chooses host mode as the default.
 *
 * @property Schema $form
 */
final class HostingSettings extends Page implements HasSchemas
{
    use InteractsWithForms;

    private const T = 'simplify-hosting::hosting.';

    protected static string|BackedEnum|null $navigationIcon = 'tabler-receipt';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'simplify-hosting';

    protected string $view = 'simplify-hosting::settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) user()?->isRootAdmin();
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

    public function mount(): void
    {
        $this->form->fill(self::current());
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label(__(self::T.'save'))->icon('tabler-device-floppy')->action('save')->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        abort_unless(self::canAccess(), 403);
        Settings::save($this->form->getState());
        Notification::make()->title(__(self::T.'saved'))->success()->send();
    }
}
