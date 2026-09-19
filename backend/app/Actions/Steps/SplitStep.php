<?php

declare(strict_types=1);

namespace App\Actions\Steps;

use App\Contracts\AiProvider;
use App\Data\Ai\ParsedStepData;
use App\Enums\StepStatus;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiResponseInvalid;
use App\Support\Ai\Parsers\DecomposeParser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsObject;

/** Replaces one step with smaller ones. Queued, because the person already moved on to something they can start. */
final class SplitStep
{
    use AsJob;
    use AsObject;

    public function __construct(
        private readonly AiProvider $provider,
        private readonly DecomposeParser $parser,
    ) {}

    /** @return Collection<int, Step> */
    public function handle(Step $step): Collection
    {
        // The person may have done or skipped it while this waited in the queue.
        if ($step->status !== StepStatus::Pending) {
            return new Collection;
        }

        $smaller = $this->parser->parse(
            $this->provider->complete(AiRequest::splitStep($this->describe($step)))->payload
        );

        return DB::transaction(function () use ($step, $smaller): Collection {
            $this->makeRoom($step, count($smaller));

            $written = [];

            foreach ($smaller as $index => $parsed) {
                $written[] = $this->write($step, $parsed, $step->position + $index);
            }

            $first = $written[0] ?? null;

            if (! $first instanceof Step) {
                throw new AiResponseInvalid('split_step returned no steps.');
            }

            ExecutionSession::query()
                ->where('current_step_id', $step->id)
                ->update(['current_step_id' => $first->id]);

            $step->delete();

            return new Collection($written);
        });
    }

    private function makeRoom(Step $step, int $count): void
    {
        if ($count > 1) {
            $step->intention->steps()
                ->where('position', '>', $step->position)
                ->increment('position', $count - 1);
        }
    }

    private function write(Step $step, ParsedStepData $parsed, int $position): Step
    {
        return $step->intention->steps()->create([
            'title' => $parsed->title,
            'position' => $position,
            'estimated_seconds' => $parsed->estimatedSeconds,
            'status' => StepStatus::Pending,
            'generated' => true,
        ]);
    }

    private function describe(Step $step): string
    {
        return implode("\n", [
            'Intention: '.$step->intention->title,
            'The step they are stuck on: '.$step->title,
        ]);
    }
}
