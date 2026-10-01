<?php

declare(strict_types=1);

use App\Actions\Intentions\SendDueRecurringIntentions;
use App\Actions\Sessions\BuildFinished;
use App\Actions\Sessions\CompleteStep;
use App\Actions\Sessions\PauseSession;
use App\Actions\Sessions\ResumeSession;
use App\Actions\Sessions\SkipCurrentStep;
use App\Actions\Sessions\StartSession;
use App\Enums\IntentionStatus;
use App\Enums\SessionOutcome;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

function finishedSession(int $steps = 1): ExecutionSession
{
    $session = started($steps);

    while ($session->isRunning()) {
        $session = CompleteStep::run($session, (string) $session->current_step_id);
    }

    return $session;
}

it('sends the last Done to the closing screen, and every other Done back', function (): void {
    $session = started(2);

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route('focus.complete-step', $session), ['step_id' => $session->current_step_id])
        ->assertRedirect(route('focus'));

    $this->actingAs($session->user)
        ->from(route('focus'))
        ->post(route('focus.complete-step', $session), ['step_id' => $session->refresh()->current_step_id])
        ->assertRedirect(route('focus.finished', $session));

    $this->actingAs($session->user)
        ->get(route('focus.finished', $session))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('finished')
            ->where('finished.intention.title', 'Clean the kitchen'));
});

it('says only what is true of a quick finish', function (): void {
    $session = finishedSession();

    expect(BuildFinished::run($session)->lines)->toBe([
        '1 step done.',
        '1 thing finished today.',
    ]);
});

it('says how long it took without the pauses, how early it was, and how long it waited', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-09-27 09:00:00'));
    $intention = kitchen(2);

    $this->travelTo(CarbonImmutable::parse('2026-09-30 10:00:00'));
    $intention->update(['deadline_at' => now()->addDays(2)->addHour(), 'deadline_confirmed_at' => now()]);
    $session = StartSession::run($intention->user, $intention->steps()->first());
    $session = SkipCurrentStep::run($session, (string) $session->current_step_id);

    $this->travel(10)->minutes();
    PauseSession::run($session);
    $this->travel(30)->minutes();
    ResumeSession::run($session);
    $this->travel(15)->minutes();

    $session = CompleteStep::run($session, (string) $session->current_step_id);
    $session = CompleteStep::run($session, (string) $session->current_step_id);

    expect(BuildFinished::run($session)->lines)->toBe([
        '2 steps done, 1 skipped along the way.',
        'About 25 minutes of work.',
        '2 days before the deadline.',
        'It had been on your list for 3 days.',
        '1 thing finished today.',
    ]);
});

it('says a deadline it read out of what they wrote is only going by that', function (): void {
    $intention = kitchen(1);
    $intention->update(['deadline_at' => now()->addDays(2)->addHour()]);
    $session = StartSession::run($intention->user, $intention->steps()->first());
    $session = CompleteStep::run($session, (string) $session->current_step_id);

    expect(BuildFinished::run($session)->lines)->toContain('Going by what you wrote, that is 2 days before the deadline.');
});

it('offers one thing next when there is one', function (): void {
    $session = finishedSession();
    $other = kitchen(1, $session->user);
    $other->update(['title' => 'Water the plants']);

    $next = BuildFinished::run($session)->next;

    expect($next?->intention->id)->toBe($other->id)
        ->and($next?->why)->not->toBe([]);
});

it('answers the closing screen on the API', function (): void {
    $session = finishedSession();

    $this->actingAs($session->user)
        ->getJson(route('api.v1.sessions.finished', $session))
        ->assertOk()
        ->assertJsonPath('intention.id', $session->intention_id)
        ->assertJsonPath('lines.0', '1 step done.')
        ->assertJsonPath('next', null)
        ->assertJsonPath('recurrenceEveryDays', null);
});

it('refuses a session that is still running, and one that is not theirs', function (): void {
    $running = started();
    $finished = finishedSession();
    $stranger = User::factory()->create();

    $this->actingAs($running->user)->get(route('focus.finished', $running))->assertNotFound();
    $this->actingAs($running->user)->getJson(route('api.v1.sessions.finished', $running))->assertNotFound();
    $this->actingAs($stranger)->get(route('focus.finished', $finished))->assertNotFound();
    $this->actingAs($stranger)->getJson(route('api.v1.sessions.finished', $finished))->assertNotFound();
});

it('says it repeats once set, and when its template does', function (): void {
    $session = finishedSession();

    $this->actingAs($session->user)->post(route('intentions.recurrence', $session->intention), ['every_days' => 7]);

    expect(BuildFinished::run($session->refresh())->recurrenceEveryDays)->toBe(7);

    Queue::fake();
    $template = Intention::factory()->for($session->user)->done()->create([
        'completed_at' => now()->subDays(7),
        'recurrence_every_days' => 3,
        'recurrence_next_at' => now()->subMinute(),
    ]);
    $copy = SendDueRecurringIntentions::run($session->user, CarbonImmutable::now())[0];
    $copy->update(['status' => IntentionStatus::Done, 'completed_at' => now()]);

    $copySession = ExecutionSession::factory()->for($session->user)->for($copy)->create([
        'outcome' => SessionOutcome::Completed,
        'ended_at' => now(),
    ]);

    expect(BuildFinished::run($copySession)->recurrenceEveryDays)->toBe($template->recurrence_every_days);
});
