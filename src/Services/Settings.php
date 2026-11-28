<?php

declare(strict_types=1);

namespace Simplify\Hosting\Services;

use App\Models\Server;
use Illuminate\Support\Facades\DB;

/** The hoster's links, the host mode default and the ticket settings, in one row. */
final class Settings
{
    public const TABLE = 'simplify_hosting_settings';

    /** An https address; the billing link may hold {uuid} and {external_id}. */
    public const LINK = '#^https://[^\s<>"{}]+$#';

    public const BILLING = '#^https://[^\s<>"]+$#';

    public const FIELDS = ['billing_url', 'client_area_url', 'order_url', 'host_mode', 'tickets', 'tickets_without_server', 'tickets_auto_close', 'ticket_categories', 'ticket_intro'];

    /** On or off, never empty. */
    public const SWITCHES = ['host_mode', 'tickets', 'tickets_without_server', 'tickets_auto_close'];

    /** The value of a switch before the hoster saved one. */
    private const DEFAULTS = ['tickets_without_server' => true];

    /** @var array<string, mixed>|null */
    private static ?array $row = null;

    public static function get(string $key): mixed
    {
        $value = (self::$row ??= (array) (DB::table(self::TABLE)->find(1) ?? []))[$key] ?? self::DEFAULTS[$key] ?? null;

        return in_array($key, self::SWITCHES, true) ? (bool) $value : ($value === '' ? null : $value);
    }

    /**
     * Saves the fields given; a field left out keeps its value (Simplify's setup asks only for the links).
     *
     * @param  array<string, mixed>  $values
     */
    public static function save(array $values): void
    {
        $row = ['updated_at' => now()];
        foreach (array_intersect(self::FIELDS, array_keys($values)) as $key) {
            $value = $values[$key];
            if (is_array($value)) {
                // The categories come from a tag field: one name per tag, kept as one comma separated text.
                $value = implode(', ', array_filter(array_map(fn ($name) => trim(str_replace(',', '', (string) $name)), $value)));
            }
            $row[$key] = in_array($key, self::SWITCHES, true) ? (bool) $value : (trim((string) $value) ?: null);
        }
        DB::table(self::TABLE)->updateOrInsert(['id' => 1], $row);
        self::$row = null;
    }

    /**
     * The billing page of one server, with its own {uuid} and {external_id}. None when the link needs a value the
     * server does not have: a server made in the panel itself has no external id, and a link that ends in "id="
     * opens nothing useful.
     */
    public static function billing(Server $server): ?string
    {
        $url = self::get('billing_url');
        if ($url === null) {
            return null;
        }
        $values = ['{uuid}' => (string) $server->uuid, '{external_id}' => (string) $server->external_id];
        foreach ($values as $placeholder => $value) {
            if ($value === '' && str_contains($url, $placeholder)) {
                return null;
            }
        }

        return strtr($url, array_map(rawurlencode(...), $values));
    }

    /** @return list<string> the categories customers pick from, in the hoster's order */
    public static function categories(): array
    {
        $names = array_map(fn (string $name) => mb_substr(trim($name), 0, 60), explode(',', (string) self::get('ticket_categories')));

        return array_values(array_unique(array_filter($names, fn (string $name) => $name !== '')));
    }

    /** Forgets the row read in this request. */
    public static function flush(): void
    {
        self::$row = null;
    }
}
