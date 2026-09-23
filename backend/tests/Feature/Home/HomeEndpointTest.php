<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Inertia\Testing\AssertableInertia;

it('answers the device with the home the web renders', function (): void {
    $intention = kitchen();

    $web = null;

    $this->actingAs($intention->user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use (&$web): void {
            $web = $page->toArray()['props']['home'];
        });

    $this->actingAs($intention->user)
        ->getJson('/api/v1/home')
        ->assertOk()
        ->assertJson(json_decode(json_encode($web), true));
});

it('refuses an unauthenticated reader', function (): void {
    $this->getJson('/api/v1/home')->assertUnauthorized();
});

it('lets a device dismiss the reminder band', function (): void {
    $user = User::factory()->create();
    $event = CalendarEvent::factory()->for($user)->create(['title' => 'Dentist']);
    $user->notify(new AppointmentReminder($event, ['Dentist is at 14:00.']));

    $notification = $user->unreadNotifications()->sole();

    $this->actingAs($user)
        ->postJson('/api/v1/reminders/'.$notification->id.'/dismiss')
        ->assertNoContent();

    expect($user->unreadNotifications()->count())->toBe(0);
});
