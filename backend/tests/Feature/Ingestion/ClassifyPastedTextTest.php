<?php

declare(strict_types=1);

use App\Models\User;

it('classifies pasted text as actionable', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);

    fakeAi()->respondWith([
        'actionable' => true,
        'title' => 'Car insurance renewal',
        'why' => 'Policy expires 14 October',
        'deadline_at' => '2026-10-14T00:00:00+02:00',
        'estimated_seconds' => 600,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('api.v1.ingestion.classify'), [
            'text' => 'Your car insurance policy expires on 14 October.',
        ])
        ->assertOk();

    expect($response->json('actionable'))->toBeTrue()
        ->and($response->json('title'))->toBe('Car insurance renewal')
        ->and($response->json('why'))->toBe('Policy expires 14 October')
        ->and($response->json('estimatedSeconds'))->toBe(600);
});

it('classifies pasted text as not actionable, with no title invented', function (): void {
    $user = User::factory()->create();

    fakeAi()->respondWith([
        'actionable' => false,
        'title' => null,
        'why' => null,
        'deadline_at' => null,
        'estimated_seconds' => null,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('api.v1.ingestion.classify'), [
            'text' => 'Thanks for your recent purchase. Your receipt is attached.',
        ])
        ->assertOk();

    expect($response->json('actionable'))->toBeFalse()
        ->and($response->json('title'))->toBeNull();
});

it('rejects pasted text with no body', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.ingestion.classify'), ['text' => ''])
        ->assertUnprocessable();
});

it('requires authentication', function (): void {
    $this->postJson(route('api.v1.ingestion.classify'), ['text' => 'anything'])
        ->assertUnauthorized();
});

it('classifies from the web too, for the paste dialog', function (): void {
    $user = User::factory()->create();

    fakeAi()->respondWith([
        'actionable' => true,
        'title' => 'Pay the water bill',
        'why' => null,
        'deadline_at' => null,
        'estimated_seconds' => 300,
    ]);

    $this->actingAs($user)
        ->postJson(route('ingestion.classify'), ['text' => 'Your water bill of 42 euro is due.'])
        ->assertOk()
        ->assertJsonPath('title', 'Pay the water bill');
});

it('says why it cannot read anything when AI consent is off', function (): void {
    $user = User::factory()->create(['ai_consented_at' => null]);
    config()->set('ai-toolkit.driver', 'openai');

    $this->actingAs($user)
        ->postJson(route('ingestion.classify'), ['text' => 'Your water bill of 42 euro is due.'])
        ->assertServiceUnavailable()
        ->assertJsonPath('message', 'Reading this needs AI, which is off. It can be turned on in settings.');
});
