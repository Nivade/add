<?php

declare(strict_types=1);

namespace App\Support\Calendar;

use App\Contracts\HostResolver;
use App\Support\Calendar\Exceptions\CalendarFeedUnreadable;
use Symfony\Component\HttpFoundation\IpUtils;

/** A feed is fetched from the queue worker, so an address inside the network it runs on is never one to read. */
final readonly class PublicFeedHost
{
    /** NAT64 passes the global-range filter, and the IPv4 address it wraps can be anything. */
    private const array NAT64_RANGES = ['64:ff9b::/96', '64:ff9b:1::/48'];

    public function __construct(private HostResolver $resolver) {}

    public function allows(string $url): bool
    {
        return $this->publicAddresses(self::host($url)) !== [];
    }

    /** A `CURLOPT_RESOLVE` entry, so the address checked here is the one connected to. */
    public function pin(string $url): string
    {
        $host = self::host($url);
        $address = $this->publicAddresses($host)[0] ?? throw new CalendarFeedUnreadable('The calendar feed is not on a public address.');
        $port = parse_url($url, PHP_URL_PORT) ?? 443;

        return $host.':'.$port.':'.(str_contains($address, ':') ? "[{$address}]" : $address);
    }

    private static function host(string $url): string
    {
        return trim((string) parse_url($url, PHP_URL_HOST), '[]');
    }

    /** @return list<string> empty when any address the host answers with is not public */
    private function publicAddresses(string $host): array
    {
        $addresses = match (true) {
            $host === '' => [],
            filter_var($host, FILTER_VALIDATE_IP) !== false => [$host],
            default => $this->resolver->addresses($host),
        };

        foreach ($addresses as $address) {
            if (! $this->isPublic($address)) {
                return [];
            }
        }

        return $addresses;
    }

    private function isPublic(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false
            && ! IpUtils::checkIp($address, self::NAT64_RANGES);
    }
}
