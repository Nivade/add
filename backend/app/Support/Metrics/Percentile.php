<?php

declare(strict_types=1);

namespace App\Support\Metrics;

/** Nearest rank, so the answer is always a value that was actually observed. */
final class Percentile
{
    /** @param  array<int, int>  $values */
    public static function of(array $values, int $percent): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);

        return $values[max((int) ceil($percent / 100 * count($values)), 1) - 1];
    }
}
