<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Contracts\NextActionResolver;
use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsObject;

/** A stretch of focused work is one row, and it points at the step the person asked for. */
final class StartSession
{
    use AsObject;

    public function __construct(private readonly NextActionResolver $resolver) {}

    public function handle(User $user, Step $step): ExecutionSession
    {
        return Cache::lock($user->sessionLockKey(), 10)->block(5, function () use ($user, $step): ExecutionSession {
            $running = $user->runningSession()->getResults();

            if ($running?->current_step_id === $step->id) {
                return $running;
            }

            $attribution = $this->attribution($user, $step);

            return $running instanceof ExecutionSession
                ? $this->retarget($running, $user, $step, $attribution)
                : $this->open($user, $step, $attribution);
        });
    }

    /**
     * Pressing start on something else is an answer to "what now", so the session follows rather than ignoring it.
     *
     * @param  array{recommended: bool, rung: string|null}  $attribution
     */
    private function retarget(ExecutionSession $running, User $user, Step $step, array $attribution): ExecutionSession
    {
        return $running->transition(function () use ($running, $user, $step, $attribution): ExecutionSession {
            // A session belongs to one intention, so moving to another one closes this stretch and opens the next.
            if ($running->intention_id !== $step->intention_id) {
                StopSession::run($running);

                return $this->open($user, $step, $attribution);
            }

            $running->update(['current_step_id' => $step->id]);

            RecordExecutionEvent::run($running, ExecutionEventType::Started, $step->id, $attribution);

            return $running;
        });
    }

    /** @param  array{recommended: bool, rung: string|null}  $attribution */
    private function open(User $user, Step $step, array $attribution): ExecutionSession
    {
        $session = ExecutionSession::query()->create([
            'user_id' => $user->id,
            'intention_id' => $step->intention_id,
            'current_step_id' => $step->id,
            'started_at' => now(),
        ]);

        RecordExecutionEvent::run($session, ExecutionEventType::Started, payload: $attribution);

        return $session;
    }

    /**
     * Asked before anything moves, so it ranks the world the person saw when they pressed start.
     *
     * @return array{recommended: bool, rung: string|null}
     */
    private function attribution(User $user, Step $step): array
    {
        $decision = $this->resolver->decide($user, ResolutionContext::forUser($user));
        $recommended = $decision?->answer->step->id === $step->id;

        return ['recommended' => $recommended, 'rung' => $recommended ? $decision->decidedBy : null];
    }
}
