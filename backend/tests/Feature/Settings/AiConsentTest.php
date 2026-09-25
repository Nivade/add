<?php

declare(strict_types=1);

use App\Actions\Intentions\ConvertCaptureToIntention;
use App\Models\Capture;
use App\Models\User;
use App\Support\Ai\Exceptions\AiUnavailable;
use Inertia\Testing\AssertableInertia;

it('reads off by default', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('ai.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/ai')
            ->where('consented', false)
        );
});

it('turns AI on and records when', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('ai.update'), ['consented' => true])
        ->assertRedirect(route('ai.edit'));

    expect($user->refresh()->ai_consented_at)->not->toBeNull();
});

it('turns AI back off rather than leaving a stale timestamp', function (): void {
    $user = User::factory()->create(['ai_consented_at' => now()]);

    $this->actingAs($user)
        ->put(route('ai.update'), ['consented' => false])
        ->assertRedirect(route('ai.edit'));

    expect($user->refresh()->ai_consented_at)->toBeNull();
});

it('sends a guest to sign in', function (): void {
    $this->get(route('ai.edit'))->assertRedirect(route('login'));
});

it('serves the same consent switch to the API', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.ai-consent.show'))
        ->assertOk()
        ->assertJson(['consented' => false]);

    $this->actingAs($user)
        ->patchJson(route('api.v1.ai-consent.update'), ['consented' => true])
        ->assertOk()
        ->assertJson(['consented' => true]);

    expect($user->refresh()->ai_consented_at)->not->toBeNull();
});

it('never reaches a live model for a person who has not consented, even when the driver is openai', function (): void {
    config()->set('ai.driver', 'openai');
    $user = User::factory()->create(['ai_consented_at' => null]);
    $capture = Capture::factory()->for($user)->create();

    expect(fn () => ConvertCaptureToIntention::run($capture))->toThrow(AiUnavailable::class);
});
