<?php

declare(strict_types=1);

namespace App\Support\Database;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/** Columns hold UTC, and a date bound on the person's clock would otherwise be compared as its wall clock. */
trait BindsDatesInUtc
{
    /**
     * @param  array<array-key, mixed>  $bindings
     * @return array<array-key, mixed>
     */
    public function prepareBindings(array $bindings)
    {
        $utc = new DateTimeZone('UTC');

        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = DateTimeImmutable::createFromInterface($value)->setTimezone($utc);
            }
        }

        return parent::prepareBindings($bindings);
    }
}
