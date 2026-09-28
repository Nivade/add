<?php

declare(strict_types=1);

namespace App\Support\Calendar;

use App\Contracts\HostResolver;

final class DnsHostResolver implements HostResolver
{
    public function addresses(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        return array_values(array_filter(array_map(fn (array $record): mixed => $record['ip'] ?? $record['ipv6'] ?? null, $records), is_string(...)));
    }
}
