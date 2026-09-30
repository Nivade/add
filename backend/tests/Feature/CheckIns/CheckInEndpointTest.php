<?php

declare(strict_types=1);

use App\Enums\CheckInAnswer;
use App\Enums\CheckInTopic;
use Inertia\Testing\AssertableInertia;

it('records the answer from the web and takes the question off home', function (): void {
    $user = activeFor();

    $this->actingAs($user)->from('/home')
        ->post('/check-ins/overwhelm', ['response' => 'less'])
        ->assertRedirect('/home');

    expect($user->checkIns()->sole())
        ->topic->toBe(CheckInTopic::Overwhelm)
        ->answer->toBe(CheckInAnswer::Less);

    $this->actingAs($user)->get('/home')
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('home.checkIn', null));
});

it('records the answer from the API', function (): void {
    $user = activeFor();

    $this->actingAs($user)
        ->postJson('/api/v1/check-ins/overwhelm', ['response' => 'not_now'])
        ->assertNoContent();

    expect($user->checkIns()->sole()->answer)->toBe(CheckInAnswer::NotNow);
});

it('refuses an answer that is not one of the four', function (): void {
    $this->actingAs(activeFor())
        ->postJson('/api/v1/check-ins/overwhelm', ['response' => 'much'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('response');
});

it('knows no topic but the two it asks', function (): void {
    $this->actingAs(activeFor())
        ->postJson('/api/v1/check-ins/mood', ['response' => 'less'])
        ->assertNotFound();
});

it('takes no answer from a guest', function (): void {
    $this->postJson('/api/v1/check-ins/overwhelm', ['response' => 'less'])->assertUnauthorized();
    $this->post('/check-ins/overwhelm', ['response' => 'less'])->assertRedirect('/login');
});
