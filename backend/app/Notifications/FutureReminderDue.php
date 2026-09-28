<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Attributes\NotificationKind;
use App\Contracts\ExpoPushable;
use App\Models\FutureReminder;
use App\Notifications\Concerns\PushesToDevices;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;

/** What you wanted, stated plainly — §15's own bar, nothing generated on top of it. */
#[NotificationKind('future_reminder')]
final class FutureReminderDue extends Notification implements ExpoPushable
{
    use PushesToDevices;

    public function __construct(
        private readonly FutureReminder $reminder,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind(),
            'future_reminder_id' => $this->reminder->id,
            'message' => $this->reminder->message,
        ];
    }

    /** @return array{title: string, body: string, data: array<string, mixed>} */
    public function toExpo(object $notifiable): array
    {
        return [
            'title' => 'A note from earlier',
            'body' => $this->reminder->message,
            'data' => Arr::only($this->toArray($notifiable), ['kind', 'future_reminder_id']),
        ];
    }
}
