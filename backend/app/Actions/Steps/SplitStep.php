<?php

declare(strict_types=1);

namespace App\Actions\Steps;

use App\Actions\Sessions\RecordExecutionEvent;
use App\Contracts\AiProvider;
use App\Enums\ExecutionEventType;
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
            $this->provider->complete(AiRequest::splitStep($step->intention->user_id, $this->describe($step)))->payload
        );

        return DB::transaction(function () use ($step, $smaller): Collection {
            $this->makeRoom($step, count($smaller));

            $written = [];

            foreach ($smaller as $index => $parsed) {
                $written[] = Step::generate($step->intention, $parsed, $step->position + $index);
            }

            $first = $written[0] ?? null;

            if (! $first instanceof Step) {
                throw new AiResponseInvalid('split_step returned no steps.');
            }

            $this->recordReplacement($step, $written, $first);

            $step->delete();

            return new Collection($written);
        });
    }

    /**
     * The row goes, so what it was goes into the replay before it does.
     *
     * @param  list<Step>  $written
     */
    private function recordReplacement(Step $step, array $written, Step $first): void
    {
        $sessions = ExecutionSession::query()->where('current_step_id', $step->id)->get();

        foreach ($sessions as $session) {
            RecordExecutionEvent::run($session, ExecutionEventType::StepSplit, $step->id, [
                'title' => $step->title,
                'skip_count' => $step->skip_count,
                'replaced_by' => array_map(fn (Step $smaller): string => $smaller->id, $written),
            ]);

            $session->update(['current_step_id' => $first->id]);
        }
    }

    private function makeRoom(Step $step, int $count): void
    {
        if ($count > 1) {
            $step->intention->steps()
                ->where('position', '>', $step->position)
                ->increment('position', $count - 1);
        }
    }

    private function describe(Step $step): string
    {
        return implode("\n", [
            'Intention: '.$step->intention->title,
            'The step they are stuck on: '.$step->title,
        ]);
    }
}
