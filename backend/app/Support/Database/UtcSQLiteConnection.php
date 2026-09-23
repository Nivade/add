<?php

declare(strict_types=1);

namespace App\Support\Database;

use Illuminate\Database\SQLiteConnection as BaseSQLiteConnection;

final class UtcSQLiteConnection extends BaseSQLiteConnection
{
    use BindsDatesInUtc;
}
