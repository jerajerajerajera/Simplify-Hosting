<?php

declare(strict_types=1);

namespace Simplify\Hosting\Services;

use App\Models\Server;
use Illuminate\Support\Facades\DB;

/** The hoster's links and the host mode default, in one row. */
final class Settings
{
    public const TABLE = 'simplify_hosting_settings';

    /** An https address; the billing link may hold {uuid} and {external_id}. */
    public const LINK = '#^https://[^\s<>"{}]+$#';

    public const BILLING = '#^https://[^\s<>"]+$#';

    public const FIELDS = ['billing_url', 'client_area_url', 'order_url', 'host_mode'];

    /** @var array<string, mixed>|null */
    private static ?array $row = null;

    public static function get(string $key): mixed
    {
        $value = (self::$row ??= (array) (DB::table(self::TABLE)->find(1) ?? []))[$key] ?? null;

        return $key === 'host_mode' ? (bool) $value : ($value === '' ? null : $value);
    }

    /** @param array<string, mixed> $values */
    public static function save(array $values): void
    {
        $row = ['updated_at' => now()];
        foreach (self::FIELDS as $key) {
            $value = $values[$key] ?? null;
            $row[$key] = $key === 'host_mode' ? (bool) $value : (trim((string) $value) ?: null);
        }
        DB::table(self::TABLE)->updateOrInsert(['id' => 1], $row);
        self::$row = null;
    }

    /** The billing page of one server, with its own {uuid} and {external_id}. */
    public static function billing(Server $server): ?string
    {
        $url = self::get('billing_url');

        return $url === null ? null : strtr($url, [
            '{uuid}' => rawurlencode((string) $server->uuid),
            '{external_id}' => rawurlencode((string) $server->external_id),
        ]);
    }

    /** Forgets the row read in this request. */
    public static function flush(): void
    {
        self::$row = null;
    }
}
