<?php

// Loads Simplify Hosting inside a real Pelican panel and opens its pages.
// Run from the panel folder after `php artisan p:plugin:install simplify-hosting`:
// php plugins/simplify-hosting/tests/panel/smoke.php
use App\Filament\Server\Pages\Console;
use App\Models\Role;
use App\Models\Server;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Simplify\Hosting\Filament\Admin\Pages\HostingSettings;
use Simplify\Hosting\HostingAddon;
use Simplify\Hosting\Services\Settings;

define('LARAVEL_START', microtime(true));
require getcwd().'/vendor/autoload.php';

// Each step gets a fresh application, like a real request.
function boot(): Application
{
    $app = require getcwd().'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    // Laravel's console handler would print an uncaught error and still exit 0, which CI reads as a pass.
    set_exception_handler(function (Throwable $e): void {
        echo 'FAIL setup: '.get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
        exit(1);
    });
    Settings::flush();

    return $app;
}

$failed = false;
function step(string $what, callable $run): void
{
    global $failed;
    try {
        $run(boot());
        echo "PASS $what\n";
    } catch (Throwable $e) {
        $failed = true;
        echo "FAIL $what: ".get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
    }
}
function visit(User $user, string $url): TestResponse
{
    $client = new class(app())
    {
        use InteractsWithAuthentication, InteractsWithSession, MakesHttpRequests;

        public function __construct(public $app) {}
    };

    return $client->actingAs($user)->get($url);
}
// Simplify puts its page data in the HTML as JSON, where a slash is written as \/.
function shows(string $html, string $url): bool
{
    return str_contains($html, $url) || str_contains($html, str_replace('/', '\\/', $url));
}
function must(bool $ok, string $why): void
{
    if (! $ok) {
        throw new RuntimeException($why);
    }
}

boot();
// Pelican has no root_admin column: root admin is a role.
$admin = User::where('email', 'smoke-hosting@example.com')->first()
    ?? User::factory()->create(['email' => 'smoke-hosting@example.com']);
$admin->syncRoles(Role::getRootAdmin());
$customer = User::where('email', 'smoke-hosting-customer@example.com')->first()
    ?? User::factory()->create(['email' => 'smoke-hosting-customer@example.com']);

step('settings page renders for a root admin', function () use ($admin) {
    $url = HostingSettings::getUrl(panel: 'admin');
    $response = visit($admin, $url);
    must($response->getStatusCode() === 200, 'HTTP '.$response->getStatusCode().' at '.$url);
});
step('settings page is closed to other users', function () use ($customer) {
    must(visit($customer, HostingSettings::getUrl(panel: 'admin'))->getStatusCode() !== 200, 'a customer can open the settings');
});
step('the settings save from the page and reject a link that is not https', function () use ($admin) {
    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
    Livewire::actingAs($admin)->test(HostingSettings::class)
        ->set('data.billing_url', 'https://billing.example.com/s/{uuid}')
        ->set('data.client_area_url', 'https://billing.example.com/clientarea')
        ->set('data.order_url', 'https://billing.example.com/store')
        ->set('data.host_mode', true)
        ->call('save')->assertHasNoErrors();
    Settings::flush();
    must(Settings::get('client_area_url') === 'https://billing.example.com/clientarea' && Settings::get('host_mode') === true, 'the settings were not saved');
    Livewire::actingAs($admin)->test(HostingSettings::class)->set('data.order_url', 'http://plain.example.com')->call('save')->assertHasErrors(['data.order_url']);
});
step('the add-on gives Simplify the billing link, the customer links and host mode', function () use ($customer) {
    if (! class_exists('Simplify\Core\Addons\Addon')) {
        require __DIR__.'/stubs/Addon.php';
    }
    $addon = app(HostingAddon::class);
    $server = new Server(['uuid' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee']);
    $server->uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
    $links = $addon->serverLinks($server);
    must(($links[0]->url ?? null) === 'https://billing.example.com/s/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee', 'the billing link is wrong: '.json_encode($links));
    $urls = array_map(fn ($link) => $link->url, $addon->customerLinks($customer));
    must($urls === ['https://billing.example.com/clientarea', 'https://billing.example.com/store'], 'the customer links are wrong: '.json_encode($urls));
    must($addon->hostModeDefault() && $addon->id() === 'simplify-hosting' && $addon->description() !== '', 'the add-on is incomplete');
    $tagged = array_map(fn ($a) => $a::class, iterator_to_array(app()->tagged('simplify.addons')));
    must(in_array(HostingAddon::class, $tagged, true), 'the add-on is not tagged');
});
step('Simplify\'s setup asks for the links and host mode and saves them', function () use ($admin, $customer) {
    $addon = app(HostingAddon::class);
    auth()->setUser($customer);
    must($addon->setupFields() === [], 'a customer gets the setup fields');
    auth()->setUser($admin);
    $state = $addon->setupState();
    must(count($addon->setupFields()) === 2 && $state['client_area_url'] === 'https://billing.example.com/clientarea', 'the setup fields or values are missing');
    $addon->saveSetup([...$state, 'order_url' => 'https://billing.example.com/order']);
    Settings::flush();
    must(Settings::get('order_url') === 'https://billing.example.com/order' && Settings::get('host_mode') === true, 'the setup answers were not saved');
    $addon->saveSetup($state);
    Settings::flush();
});
$simplify = class_exists('Simplify\Core\SimplifyPlugin');
$server = Server::where('owner_id', $customer->id)->first()
    ?? Server::factory()->create(['owner_id' => $customer->id, 'name' => 'Smoke hosting server']);
if (! $simplify) {
    step('the client area is in the account menu', function () use ($customer) {
        $url = Filament\Facades\Filament::getPanel('app')->getUrl();
        $response = visit($customer, $url);
        if ($response->isRedirect()) {
            $response = visit($customer, (string) $response->headers->get('Location'));
        }
        must(shows($response->getContent(), 'https://billing.example.com/clientarea'), 'the client area link is missing');
    });
    step('the console shows Upgrade this server to its owner', function () use ($customer, $server) {
        $url = Console::getUrl(panel: 'server', tenant: $server);
        $response = visit($customer, $url);
        must($response->getStatusCode() === 200, 'HTTP '.$response->getStatusCode().' at '.$url);
        must(shows($response->getContent(), 'https://billing.example.com/s/'.$server->uuid), 'the billing link is missing on the console');
    });
} else {
    // P08: a customer sees a greeting with the client area and order link, and Upgrade this server on their server.
    step('with Simplify a customer gets the greeting links and Upgrade this server', function () use ($customer, $server) {
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('app'));
        $boot = Livewire::actingAs($customer)->test(Simplify\Core\Filament\App\Pages\Overview::class)->instance()->initial();
        $urls = array_column($boot['customer'], 'url');
        must(array_slice($urls, 0, 2) === ['https://billing.example.com/clientarea', 'https://billing.example.com/store'], 'the greeting links are wrong: '.json_encode($urls));
        $card = collect($boot['servers'])->firstWhere('uuid', $server->uuid);
        must(in_array('https://billing.example.com/s/'.$server->uuid, array_column($card['links'] ?? [], 'url'), true), 'Upgrade this server is missing: '.json_encode($card['links'] ?? null));
        $response = visit($customer, Simplify\Core\Filament\App\Pages\Overview::getUrl(panel: 'app'));
        must($response->getStatusCode() === 200 && shows($response->getContent(), 'https://billing.example.com/store'), 'the overview does not show the order link');
    });
    step('with Simplify an admin gets no greeting and starts in host mode', function () use ($admin) {
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('app'));
        $boot = Livewire::actingAs($admin)->test(Simplify\Core\Filament\App\Pages\Overview::class)->instance()->initial();
        must($boot['customer'] === [] && $boot['host_mode_auto'] === true, 'an admin gets the greeting or no host mode: '.json_encode([$boot['customer'], $boot['host_mode_auto']]));
    });
}

echo $failed ? "Some steps failed\n" : "All steps passed\n";
exit($failed ? 1 : 0);
