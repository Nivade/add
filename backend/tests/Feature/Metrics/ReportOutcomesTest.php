<?php

declare(strict_types=1);

use App\Actions\CheckIns\RecordCheckIn;
use App\Actions\Commitments\CreateCommitment;
use App\Actions\Commitments\RespondToCommitment;
use App\Actions\Metrics\ReportOutcomes;
use App\Actions\Sessions\CompleteStep;
use App\Actions\Sessions\RecordDistraction;
use App\Actions\Sessions\RecordExecutionEvent;
use App\Actions\Sessions\SkipCurrentStep;
use App\Actions\Sessions\StartSession;
use App\Actions\Sessions\StopSession;
use App\Data\Metrics\CheckInOutcomeData;
use App\Data\Metrics\IntentionOutcomesData;
use App\Data\Metrics\OutcomesData;
use App\Data\Metrics\RecoveryOutcomesData;
use App\Data\Metrics\RungOutcomeData;
use App\Data\Metrics\SessionOutcomesData;
use App\Enums\CheckInAnswer;
use App\Enums\CheckInTopic;
use App\Enums\CommitmentProvenance;
use App\Enums\CommitmentResponse;
use App\Enums\ExecutionEventType;
use App\Enums\IntentionStatus;
use App\Models\Intention;
use App\Models\User;
use App\Support\Metrics\MetricsWindow;
use Carbon\CarbonImmutable;

function clockAt(string $moment): void
{
    test()->travelTo(CarbonImmutable::parse($moment));
}

function reportFor(?User $user = null): OutcomesData
{
    return ReportOutcomes::run(MetricsWindow::lastDays(28, CarbonImmutable::parse('2026-09-30 12:00:00'), $user?->id));
}

/** One intention finished, one stopped then picked back up, one set aside, one from before the window. */
function monthOfUse(): User
{
    $user = User::factory()->create();

    clockAt('2026-08-01 10:00:00');
    kitchen(2, $user);

    clockAt('2026-09-10 10:00:00');
    $finished = kitchen(2, $user);
    $resumed = kitchen(2, $user);
    Intention::factory()->for($user)->create(['status' => IntentionStatus::SetAside]);

    clockAt('2026-09-10 10:30:00');
    $first = StartSession::run($user, $finished->steps()->where('position', 1)->sole());
    RecordDistraction::run($first);
    CompleteStep::run($first, $first->current_step_id);
    CompleteStep::run($first, $first->current_step_id);

    clockAt('2026-09-10 11:30:00');
    $stopped = StartSession::run($user, $resumed->steps()->where('position', 1)->sole());
    SkipCurrentStep::run($stopped, $stopped->current_step_id);
    RecordDistraction::run($stopped);
    StopSession::run($stopped);

    clockAt('2026-09-12 09:00:00');
    $resumedStep = $resumed->steps()->where('position', 1)->sole();
    CompleteStep::run(StartSession::run($user, $resumedStep), $resumedStep->id);

    $kept = CreateCommitment::run($user, 'Send the form back.', CommitmentProvenance::UserStated);
    RespondToCommitment::run($kept, CommitmentResponse::Keep);
    $released = CreateCommitment::run($user, 'Call about the boiler.', CommitmentProvenance::UserStated);
    RespondToCommitment::run($released, CommitmentResponse::Release);

    RecordCheckIn::run($user, CheckInTopic::Overwhelm, CheckInAnswer::Less);

    clockAt('2026-09-30 12:00:00');

    return $user;
}

it('follows the intentions created in the window to where they are now', function (): void {
    expect(reportFor(monthOfUse())->intentions)->toEqual(new IntentionOutcomesData(
        created: 3,
        done: 1,
        setAside: 1,
        open: 1,
        medianMinutesToStart: 30,
        p75MinutesToStart: 90,
        notStarted: 1,
    ));
});

it('counts sessions with a step done, and distractions a step was done after', function (): void {
    expect(reportFor(monthOfUse())->sessions)->toEqual(new SessionOutcomesData(
        ended: 2,
        withProgress: 1,
        distractions: 2,
        backAfterDistraction: 1,
    ));
});

it('counts a stopped stretch started again, and a skipped step done after all', function (): void {
    expect(reportFor(monthOfUse())->recovery)->toEqual(new RecoveryOutcomesData(
        landedUnfinished: 1,
        pickedBackUp: 1,
        skippedSteps: 1,
        skippedThenDone: 1,
    ));
});

it('counts kept commitments and never measures them against released ones', function (): void {
    expect(reportFor(monthOfUse())->commitmentsKept)->toBe(1);
});

it('counts check-in answers per topic', function (): void {
    expect(reportFor(monthOfUse())->checkIns)->toEqual([
        new CheckInOutcomeData(CheckInTopic::Overwhelm, less: 1, same: 0, more: 0, notNow: 0),
        new CheckInOutcomeData(CheckInTopic::Remembering, less: 0, same: 0, more: 0, notNow: 0),
    ]);
});

it('judges each rung by what happened to the step it started, and ignores starts from before attribution', function (): void {
    $user = User::factory()->create();
    $intention = kitchen(3, $user);

    clockAt('2026-09-20 10:00:00');
    $session = StartSession::run($user, $intention->steps()->where('position', 1)->sole());
    CompleteStep::run($session, $session->current_step_id);

    clockAt('2026-09-20 10:10:00');
    StartSession::run($user, $intention->steps()->where('position', 3)->sole());
    SkipCurrentStep::run($session->refresh(), $session->current_step_id);
    RecordExecutionEvent::run($session->refresh(), ExecutionEventType::Started);

    clockAt('2026-09-30 12:00:00');

    expect(reportFor($user)->rungs)->toEqual([
        new RungOutcomeData('prerequisite_first', starts: 1, doneInSession: 1, skippedInSession: 0),
        new RungOutcomeData(null, starts: 1, doneInSession: 0, skippedInSession: 1),
    ]);
});

it('leaves out everything outside the window', function (): void {
    $user = monthOfUse();

    $later = ReportOutcomes::run(MetricsWindow::lastDays(28, CarbonImmutable::parse('2026-12-01 12:00:00'), $user->id));

    expect($later->intentions->created)->toBe(0)
        ->and($later->sessions->ended)->toBe(0)
        ->and($later->sessions->distractions)->toBe(0)
        ->and($later->recovery->landedUnfinished)->toBe(0)
        ->and($later->recovery->skippedSteps)->toBe(0)
        ->and($later->commitmentsKept)->toBe(0)
        ->and($later->rungs)->toBe([])
        ->and($later->checkIns[0]->less)->toBe(0);
});

it('narrows the report to one person', function (): void {
    $user = monthOfUse();
    $other = User::factory()->create();
    clockAt('2026-09-15 10:00:00');
    kitchen(2, $other);
    clockAt('2026-09-30 12:00:00');

    $theirs = reportFor($other);

    expect(reportFor($user)->intentions->created)->toBe(3)
        ->and($theirs->intentions->created)->toBe(1)
        ->and(reportFor()->intentions->created)->toBe(4)
        ->and($theirs->sessions->ended)->toBe(0)
        ->and($theirs->recovery->skippedSteps)->toBe(0)
        ->and($theirs->commitmentsKept)->toBe(0)
        ->and($theirs->rungs)->toBe([])
        ->and($theirs->checkIns[0]->less)->toBe(0);
});

it('prints numbers and never a title', function (): void {
    monthOfUse();

    $this->artisan('metrics:report')
        ->expectsOutputToContain('1 of 3 (33%)')
        ->doesntExpectOutputToContain('Clean the kitchen')
        ->doesntExpectOutputToContain('Step 1.')
        ->doesntExpectOutputToContain('Send the form back.')
        ->assertSuccessful();
});

it('refuses a person who does not exist', function (): void {
    $this->artisan('metrics:report', ['--user' => 999])->assertFailed();
});
