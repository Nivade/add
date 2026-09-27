<?php

declare(strict_types=1);

namespace App\Support\Calendar;

use App\Contracts\HostResolver;
use App\Support\Calendar\Exceptions\CalendarFeedUnreadable;

/** A feed is fetched from the queue worker, so an address inside the network it runs on is never one to read. */
final readonly class PublicFeedHost
{
    private const string NAT64_WELL_KNOWN_PREFIX = "\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00";

    private const string NAT64_LOCAL_USE_PREFIX = "\x00\x64\xff\x9b\x00\x01";

    public function __construct(private HostResolver $resolver) {}

    public function allows(string $url): bool
    {
        return $this->publicAddresses($url) !== [];
    }

    /** The address the request is pinned to, so the answer checked here is the one connected to. */
    public function address(string $url): string
    {
        return $this->publicAddresses($url)[0] ?? throw new CalendarFeedUnreadable('The calendar feed is not on a public address.');
    }

    /** @return list<string> empty when any address the host answers with is not public */
    private function publicAddresses(string $url): array
    {
        $host = trim((string) parse_url($url, PHP_URL_HOST), '[]');
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
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) {
            return false;
        }

        $packed = (string) inet_pton($address);

        // NAT64 passes the global-range filter, and the IPv4 address it wraps can be anything.
        return ! str_starts_with($packed, self::NAT64_WELL_KNOWN_PREFIX) && ! str_starts_with($packed, self::NAT64_LOCAL_USE_PREFIX);
    }
}
