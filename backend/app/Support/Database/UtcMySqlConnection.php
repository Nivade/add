<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

final class UtcMySqlConnection extends BaseMySqlConnection
{
    use BindsDatesInUtc;
}
