<?php

declare(strict_types=1);

namespace App\Support\Calendar;

use App\Contracts\HostResolver;

final class DnsHostResolver implements HostResolver
{
    public function addresses(string $host): array
    {
        $v4 = gethostbynamel($host) ?: [];
        $v6 = array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6');

        return array_values(array_filter([...$v4, ...$v6], is_string(...)));
    }
}
