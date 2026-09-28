<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Support\Calendar\DnsHostResolver;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;

#[Bind(DnsHostResolver::class)]
#[Singleton]
interface HostResolver
{
    /** @return list<string> every address the host answers with, empty when it answers with none */
    public function addresses(string $host): array;
}
