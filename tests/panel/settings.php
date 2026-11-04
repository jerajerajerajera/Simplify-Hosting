<?php

/*
 * The hoster's settings across an update, an uninstall and a step back (checks.yml, panel-smoke).
 * Run from the panel folder: `php <plugin>/tests/panel/settings.php fill` writes every field, `check` fails when one
 * of them is gone or changed. Plain PDO: it also runs while no version of the plugin is installed.
 */

declare(strict_types=1);

const FILLED = [
    'billing_url' => 'https://billing.example.com/server/{uuid}?ref={external_id}',
    'client_area_url' => 'https://billing.example.com/clientarea',
    'order_url' => 'https://billing.example.com/order',
    'host_mode' => 1,
];

$db = new PDO('sqlite:database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (($argv[1] ?? '') === 'fill') {
    $db->prepare('INSERT OR REPLACE INTO simplify_hosting_settings (id, billing_url, client_area_url, order_url, host_mode, updated_at) VALUES (1, ?, ?, ?, ?, ?)')
        ->execute([...array_values(FILLED), '2026-11-03 12:00:00']);
    echo "filled\n";
    exit(0);
}

$row = $db->query('SELECT billing_url, client_area_url, order_url, host_mode FROM simplify_hosting_settings WHERE id = 1')->fetch(PDO::FETCH_ASSOC) ?: [];
$row['host_mode'] = (int) ($row['host_mode'] ?? 0);
echo json_encode($row, JSON_UNESCAPED_SLASHES), PHP_EOL;
if ($row !== FILLED) {
    echo "The hoster's settings were not kept\n";
    exit(1);
}
echo "The hoster's settings were kept\n";
