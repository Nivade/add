<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\MariaDbConnection as BaseMariaDbConnection;

final class UtcMariaDbConnection extends BaseMariaDbConnection
{
    use BindsDatesInUtc;
}
