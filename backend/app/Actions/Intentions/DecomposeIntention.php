<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Contracts\AiProvider;
use App\Data\Ai\ParsedStepData;
use App\Enums\IntentionStatus;
use App\Enums\StepStatus;
use App\Models\Intention;
use App\Models\Step;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Parsers\DecomposeParser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

final class DecomposeIntention
{
    use AsJob;
    use AsObject;

    public function __construct(
        private readonly AiProvider $provider,
        private readonly DecomposeParser $parser,
    ) {}

    /** @return Collection<int, Step> */
    public function handle(Intention $intention): Collection
    {
        if ($intention->decomposed_at !== null) {
            return $intention->steps()->get();
        }

        $steps = $this->parser->parse(
            $this->provider->complete(AiRequest::decomposeIntention($this->describe($intention)))->payload
        );

        foreach ($this->parser->violations($steps) as $violation) {
            Log::warning('Decomposition quality violation.', [
                'intention_id' => $intention->id,
                'violation' => $violation,
            ]);
        }

        return DB::transaction(function () use ($intention, $steps): Collection {
            $written = new Collection;

            foreach ($steps as $index => $step) {
                $written->push($this->write($intention, $step, $index + 1));
            }

            $intention->update([
                'status' => IntentionStatus::Active,
                'decomposed_at' => now(),
            ]);

            return $written;
        });
    }

    /** `position` is the order the model gave, which is a claim about sequence and not about priority. */
    private function write(Intention $intention, ParsedStepData $step, int $position): Step
    {
        return $intention->steps()->create([
            'title' => $step->title,
            'position' => $position,
            'estimated_seconds' => $step->estimatedSeconds,
            'status' => StepStatus::Pending,
            'generated' => true,
        ]);
    }

    private function describe(Intention $intention): string
    {
        $lines = ['Intention: '.$intention->title];

        if ($intention->why !== null) {
            $lines[] = 'Why it matters: '.$intention->why;
        }

        if ($intention->deadline_at !== null) {
            // Their clock, not the column's: a deadline stated in UTC reads as the wrong evening.
            $lines[] = 'Deadline: '.$intention->deadline_at
                ->setTimezone($intention->user->timezone)
                ->format('l j F Y H:i');
        }

        return implode("\n", $lines);
    }
}
