<?php

declare(strict_types=1);

namespace App\Support\Time;

/** Mirrors `formatEstimate` in packages/shared: one estimate must not read two ways on one screen. */
final class EstimateWords
{
    public static function for(?int $seconds): ?string
    {
        if ($seconds === null || $seconds <= 0) {
            return null;
        }

        if ($seconds < 60) {
            return (int) round($seconds / 10) * 10 .' seconds';
        }

        $minutes = (int) round($seconds / 60);

        if ($minutes < 60) {
            return $minutes === 1 ? '1 minute' : $minutes.' minutes';
        }

        $hours = round($minutes / 30) / 2;

        return $hours === 1.0 ? '1 hour' : $hours.' hours';
    }
}
