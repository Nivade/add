<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('stores a date written on the person\'s clock as the instant it names', function (): void {
    $event = CalendarEvent::factory()->create(['starts_at' => CarbonImmutable::parse('2026-09-19 14:00:00', 'Europe/Amsterdam')]);

    expect(DB::table('calendar_events')->where('id', $event->id)->value('starts_at'))->toBe('2026-09-19 12:00:00');
});

it('compares a date bound on the person\'s clock as the instant it names', function (): void {
    CalendarEvent::factory()->create(['starts_at' => CarbonImmutable::parse('2026-09-19 12:00:00')]);

    expect(CalendarEvent::query()->where('starts_at', '>=', CarbonImmutable::parse('2026-09-19 13:30:00', 'Europe/Amsterdam'))->count())->toBe(1)
        ->and(CalendarEvent::query()->where('starts_at', '>=', CarbonImmutable::parse('2026-09-19 14:30:00', 'Europe/Amsterdam'))->count())->toBe(0);
});
