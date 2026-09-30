<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Data\NextActionData;

final readonly class Decision
{
    public const string CONTINUATION = 'continuation';

    public function __construct(public NextActionData $answer, public string $decidedBy) {}
}
