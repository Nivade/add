<?php

declare(strict_types=1);

namespace App\Data;

/** The band's rows, and how many open waiting-fors and standalone commitments exist behind them. */
final readonly class NeedsAttentionBand
{
    /** @param  list<NeedsAttentionData>  $items */
    public function __construct(
        public array $items,
        public int $openBesidesIntentions,
    ) {}
}
