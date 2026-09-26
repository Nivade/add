<?php

declare(strict_types=1);

use App\Actions\Intentions\DecomposeIntention;
use App\Actions\Intentions\SendDueRecurringIntentions;
use App\Enums\IntentionStatus;
use App\Models\Intention;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Lorisleiva\Actions\Decorators\JobDecorator;

it('sets recurrence on a done intention from the web', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-25 09:00:00', 'Europe/Amsterdam'));
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);
    $intention = Intention::factory()->for($user)->done()->create(['title' => 'Water the plants']);

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('intentions.recurrence', $intention), ['every_days' => 7])
        ->assertRedirect(route('home'));

    $intention->refresh();

    expect($intention->recurrence_every_days)->toBe(7)
        ->and($intention->is_recurring)->toBeTrue()
        ->and($intention->recurrence_next_at?->equalTo(CarbonImmutable::now()->addDays(7)))->toBeTrue();
});

it('refuses to set recurrence on an intention that is not done', function (): void {
    $user = User::factory()->create();
    $intention = Intention::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.intentions.recurrence', $intention), ['every_days' => 3])
        ->assertConflict();

    expect($intention->refresh()->recurrence_every_days)->toBeNull();
});

it('does not let one person set recurrence on another person\'s intention', function (): void {
    $intention = Intention::factory()->done()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('intentions.recurrence', $intention), ['every_days' => 7])
        ->assertNotFound();

    expect($intention->refresh()->recurrence_every_days)->toBeNull();
});

it('creates a fresh intention from a due template and queues it for re-decomposition', function (): void {
    Queue::fake();

    $now = CarbonImmutable::parse('2026-10-02 09:00:00', 'Europe/Amsterdam');
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);
    $template = Intention::factory()->for($user)->done()->create([
        'title' => 'Water the plants',
        'why' => 'They keep dying',
        'recurrence_every_days' => 7,
        'recurrence_next_at' => $now->subMinute(),
    ]);

    SendDueRecurringIntentions::run($user, $now);

    $fresh = Intention::query()->where('id', '!=', $template->id)->sole();

    expect($fresh->title)->toBe('Water the plants')
        ->and($fresh->why)->toBe('They keep dying')
        ->and($fresh->status)->toBe(IntentionStatus::Captured)
        ->and($fresh->steps()->count())->toBe(0)
        ->and($template->refresh()->recurrence_next_at?->equalTo($now->subMinute()->addDays(7)))->toBeTrue();

    Queue::assertPushed(JobDecorator::class, fn (JobDecorator $job): bool => $job->getAction() instanceof DecomposeIntention);
});

it('leaves a template alone before it is due', function (): void {
    $now = CarbonImmutable::parse('2026-10-02 09:00:00', 'Europe/Amsterdam');
    $user = User::factory()->create();
    Intention::factory()->for($user)->done()->create([
        'recurrence_every_days' => 7,
        'recurrence_next_at' => $now->addDay(),
    ]);

    SendDueRecurringIntentions::run($user, $now);

    expect(Intention::query()->count())->toBe(1);
});
