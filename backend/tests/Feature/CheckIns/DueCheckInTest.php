<?php

declare(strict_types=1);

use App\Actions\CheckIns\DueCheckIn;
use App\Actions\CheckIns\RecordCheckIn;
use App\Actions\Sessions\StartSession;
use App\Actions\Sessions\StopSession;
use App\Enums\CheckInAnswer;
use App\Enums\CheckInTopic;
use App\Models\User;
use Carbon\CarbonImmutable;

function workedOn(User $user): void
{
    $intention = kitchen();
    $intention->update(['user_id' => $user->id]);

    StopSession::run(StartSession::run($user, $intention->steps()->first()));
}

/** An account opened some days ago that stopped a session yesterday. */
function activeFor(int $days = 30): User
{
    test()->travelTo(CarbonImmutable::parse('2026-09-30 10:00:00')->subDays($days));
    $user = User::factory()->create();

    test()->travelTo(CarbonImmutable::parse('2026-09-29 10:00:00'));
    workedOn($user);

    test()->travelTo(CarbonImmutable::parse('2026-09-30 10:00:00'));

    return $user;
}

function dueCheckIn(User $user): ?CheckInTopic
{
    return DueCheckIn::run($user, CarbonImmutable::now());
}

it('asks nobody whose account is too new to compare with', function (): void {
    expect(dueCheckIn(activeFor(days: 10)))->toBeNull();
});

it('asks nobody who has not used the app in the last fortnight', function (): void {
    $user = activeFor();
    $this->travel(15)->days();

    expect(dueCheckIn($user))->toBeNull();
});

it('asks an old, active account about overwhelm first', function (): void {
    expect(dueCheckIn(activeFor()))->toBe(CheckInTopic::Overwhelm);
});

it('alternates to remembering, and not before the fortnight is up', function (): void {
    $user = activeFor();
    RecordCheckIn::run($user, CheckInTopic::Overwhelm, CheckInAnswer::Less);

    $this->travel(13)->days();
    workedOn($user);
    expect(dueCheckIn($user))->toBeNull();

    $this->travel(1)->days();
    expect(dueCheckIn($user))->toBe(CheckInTopic::Remembering);
});

it('counts "not now" as asked', function (): void {
    $user = activeFor();
    RecordCheckIn::run($user, CheckInTopic::Overwhelm, CheckInAnswer::NotNow);

    expect(dueCheckIn($user))->toBeNull()
        ->and($user->checkIns()->sole()->answer)->toBe(CheckInAnswer::NotNow);
});

it('keeps the first answer when a second arrives inside the fortnight', function (): void {
    $user = activeFor();
    $first = RecordCheckIn::run($user, CheckInTopic::Overwhelm, CheckInAnswer::Less);

    $second = RecordCheckIn::run($user, CheckInTopic::Overwhelm, CheckInAnswer::More);

    expect($second->id)->toBe($first->id)
        ->and($user->checkIns()->count())->toBe(1);
});
