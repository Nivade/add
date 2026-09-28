<?php

declare(strict_types=1);

use App\Actions\Intentions\ConvertCaptureToIntention;
use App\Actions\Intentions\DecomposeIntention;
use App\Actions\Steps\SplitStep;
use App\Enums\StepStatus;
use App\Models\Capture;
use App\Models\Intention;
use App\Models\Step;
use App\Models\User;
use Illuminate\Pipeline\Pipeline;
use Lorisleiva\Actions\Decorators\JobDecorator;

/** Mirrors CallQueuedHandler::dispatchThroughMiddleware(), so #[FailOn] is exercised the way a worker exercises it. */
function runQueuedJob(JobDecorator $job): void
{
    $job->withFakeQueueInteractions();

    try {
        app(Pipeline::class)->send($job)->through($job->middleware())->then(fn (JobDecorator $job): mixed => $job->handle());
    } catch (Throwable) {
        // The exception always propagates; what matters here is whether the middleware marked the job failed.
    }
}

it('fails a decomposition job immediately when the AI layer is unavailable', function (): void {
    $intention = Intention::factory()->create();

    $job = DecomposeIntention::makeJob($intention);
    runQueuedJob($job);

    $job->assertFailed();
});

it('leaves a decomposition job for the worker to retry on an invalid AI answer', function (): void {
    fakeAi()->push(['steps' => []]);
    $intention = Intention::factory()->create();

    $job = DecomposeIntention::makeJob($intention);
    runQueuedJob($job);

    $job->assertNotFailed();
});

it('fails an intention conversion job immediately when the AI layer is unavailable', function (): void {
    $capture = Capture::factory()->for(User::factory())->create();

    $job = ConvertCaptureToIntention::makeJob($capture);
    runQueuedJob($job);

    $job->assertFailed();
});

it('fails a step split job immediately when the AI layer is unavailable', function (): void {
    $intention = Intention::factory()->create();
    $step = Step::factory()->for($intention)->create(['status' => StepStatus::Pending]);

    $job = SplitStep::makeJob($step);
    runQueuedJob($job);

    $job->assertFailed();
});
