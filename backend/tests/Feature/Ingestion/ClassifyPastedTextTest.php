<?php

declare(strict_types=1);

use App\Models\User;

it('classifies pasted text as actionable through the manual ingestion source', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/Amsterdam']);

    fakeAi()->push([
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
        ->assertCreated();

    expect($response->json('actionable'))->toBeTrue()
        ->and($response->json('title'))->toBe('Car insurance renewal')
        ->and($response->json('why'))->toBe('Policy expires 14 October')
        ->and($response->json('estimatedSeconds'))->toBe(600);
});

it('classifies pasted text as not actionable, with no title invented', function (): void {
    $user = User::factory()->create();

    fakeAi()->push([
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
        ->assertCreated();

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
