<?php

declare(strict_types=1);

use App\Support\Metrics\Percentile;

it('has no percentile for nothing', function (): void {
    expect(Percentile::of([], 50))->toBeNull();
});

it('answers the one value for a single observation', function (): void {
    expect(Percentile::of([7], 50))->toBe(7)
        ->and(Percentile::of([7], 75))->toBe(7);
});

it('picks an observed value by nearest rank for an even count', function (): void {
    expect(Percentile::of([40, 10, 30, 20], 50))->toBe(20)
        ->and(Percentile::of([40, 10, 30, 20], 75))->toBe(30);
});
