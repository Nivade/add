<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\PlanRung;

/** The three assumption columns are identical wherever an appointment lives. */
trait PlansBackwards
{
    public function statedSeconds(PlanRung $rung): ?int
    {
        $stated = $this->getAttribute($rung->column());

        return is_int($stated) ? $stated : null;
    }

    public function stateSeconds(PlanRung $rung, ?int $seconds): void
    {
        $this->setAttribute($rung->column(), $seconds);
    }
}
