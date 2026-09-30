<?php

declare(strict_types=1);

use App\Actions\Whereabouts\ReportNotHere;
use App\Enums\Place;
use App\Models\User;
use App\Support\NextAction\Whereabouts;
use Carbon\CarbonImmutable;

function whereabouts(User $user): Whereabouts
{
    return Whereabouts::forUser($user, $user->now());
}

it('knows nothing until the person does or says something', function (): void {
    $where = whereabouts(User::factory()->create());

    expect($where->isKnown())->toBeFalse()
        ->and(array_map(fn (Place $place): int => $where->fit($place), Place::cases()))->toBe([1, 1, 1, 1]);
});

it('takes a home step finished minutes ago to mean home, and not work or out', function (): void {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));

    finishStepAt($user, Place::Home);
    $this->travel(10)->minutes();

    $where = whereabouts($user);

    expect($where->likely)->toBe([Place::Home])
        ->and($where->unlikely)->toBe([Place::Work, Place::Out])
        ->and($where->fit(Place::Computer))->toBe(1)
        ->and($where->fit(null))->toBe(1);
});

it('forgets a finished step once the window has passed', function (): void {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));

    finishStepAt($user, Place::Home);
    $this->travel(Whereabouts::LIKELY_MINUTES + 1)->minutes();

    expect(whereabouts($user)->isKnown())->toBeFalse();
});

it('believes a newer "not here" over an older finished step', function (): void {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));

    finishStepAt($user, Place::Home);
    $this->travel(5)->minutes();
    ReportNotHere::run($user, Place::Home);

    $where = whereabouts($user);

    expect($where->likely)->toBe([])
        ->and($where->unlikely)->toBe([Place::Home]);
});

it('believes a newer finished step over an older "not here"', function (): void {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));

    ReportNotHere::run($user, Place::Home);
    $this->travel(5)->minutes();
    finishStepAt($user, Place::Home);

    expect(whereabouts($user)->likely)->toBe([Place::Home]);
});

it('takes only the latest location, since a person is in one at a time', function (): void {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));

    finishStepAt($user, Place::Home);
    $this->travel(5)->minutes();
    finishStepAt($user, Place::Out);

    $where = whereabouts($user);

    expect($where->likely)->toBe([Place::Out])
        ->and($where->unlikely)->toBe([Place::Home, Place::Work]);
});

it('lets no older location stand in once the latest one is taken back', function (): void {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-09-26 09:50:00'));

    finishStepAt($user, Place::Work);
    $this->travel(5)->minutes();
    finishStepAt($user, Place::Home);
    $this->travel(3)->minutes();
    ReportNotHere::run($user, Place::Home);

    $where = whereabouts($user);

    expect($where->likely)->toBe([])
        ->and($where->unlikely)->toBe([Place::Home])
        ->and($where->fit(Place::Work))->toBe(1);
});

it('judges a computer on its own evidence, apart from any location', function (): void {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));

    finishStepAt($user, Place::Computer);
    $this->travel(5)->minutes();
    finishStepAt($user, Place::Work);
    ReportNotHere::run($user, Place::Home);

    $where = whereabouts($user);

    expect($where->likely)->toBe([Place::Work, Place::Computer])
        ->and($where->unlikely)->toBe([Place::Home, Place::Out]);

    ReportNotHere::run($user, Place::Computer);

    expect(whereabouts($user)->fit(Place::Computer))->toBe(0)
        ->and(whereabouts($user)->fit(Place::Work))->toBe(2);
});

it('lets a "not here" lapse after its own window', function (): void {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00'));

    ReportNotHere::run($user, Place::Home);
    $this->travel(Whereabouts::NOT_HERE_HOURS * 60 + 1)->minutes();

    expect(whereabouts($user)->isKnown())->toBeFalse();
});

it('measures the window in instants for someone far from UTC', function (): void {
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);
    $this->travelTo(CarbonImmutable::parse('2026-09-26 10:00:00', 'UTC'));

    finishStepAt($user, Place::Home);

    $this->travel(Whereabouts::LIKELY_MINUTES - 1)->minutes();
    expect(whereabouts($user)->likely)->toBe([Place::Home]);

    $this->travel(2)->minutes();
    expect(whereabouts($user)->isKnown())->toBeFalse();
});
