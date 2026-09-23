<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\PostgresConnection as BasePostgresConnection;

final class UtcPostgresConnection extends BasePostgresConnection
{
    use BindsDatesInUtc;
}
