<?php

declare(strict_types=1);

namespace Simplify\Hosting\Services;

/**
 * The links of common billing systems, from the address they run at. Each system's Pelican module sets the server's
 * external_id to its own service id (Blesta's does not, so its link opens the list of services).
 */
final class BillingSystems
{
    /** @var array<string, array{billing_url: string, client_area_url: string, order_url: string}> */
    public const LINKS = [
        'WHMCS' => ['billing_url' => '/upgrade.php?type=package&id={external_id}', 'client_area_url' => '/clientarea.php', 'order_url' => '/cart.php'],
        'Paymenter' => ['billing_url' => '/services/{external_id}/upgrade', 'client_area_url' => '/dashboard', 'order_url' => '/'],
        'Blesta' => ['billing_url' => '/client/services/', 'client_area_url' => '/client/', 'order_url' => '/order/'],
    ];

    /** @return array<string, string> the three links, or none for an unknown system or an address that is not https */
    public static function links(?string $system, ?string $address): array
    {
        $address = rtrim(trim((string) $address), '/');
        if (! isset(self::LINKS[$system]) || ! preg_match(Settings::LINK, $address)) {
            return [];
        }

        return array_map(fn (string $path) => $address.$path, self::LINKS[$system]);
    }
}
