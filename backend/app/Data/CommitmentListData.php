<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CommitmentListData extends Data
{
    /** @param  list<CommitmentData>  $commitments */
    public function __construct(
        public array $commitments,
    ) {}
}
