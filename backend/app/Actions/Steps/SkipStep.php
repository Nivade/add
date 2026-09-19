<?php

declare(strict_types=1);

namespace App\Actions\Steps;

use App\Models\Step;
use Lorisleiva\Actions\Concerns\AsObject;

/** Skipping moves past the step now; it stays pending work and comes back once the cool-off ends. */
final class SkipStep
{
    use AsObject;

    public function handle(Step $step): Step
    {
        $step->update([
            'skip_count' => $step->skip_count + 1,
            'last_skipped_at' => now(),
        ]);

        return $step;
    }
}
