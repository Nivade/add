<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\IntentionStatus;
use App\Enums\StepStatus;
use App\Models\Step;
use Illuminate\Http\Request;

trait ResolvesStartableStep
{
    /** Work that is finished, skipped past, or on an intention nobody is working on is not somewhere to start. */
    protected function startableStep(Request $request, string $stepId): Step
    {
        $step = Step::query()->with('intention')->findOrFail($stepId);

        abort_unless($step->intention->user_id === $this->user($request)->id, 404);
        abort_unless($step->status === StepStatus::Pending, 404);
        abort_unless($step->intention->status === IntentionStatus::Active, 404);

        return $step;
    }
}
