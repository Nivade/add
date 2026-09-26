<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\ExpoPushable;
use App\Models\FutureReminder;
use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Notifications\Notification;

/** What you wanted, stated plainly — §15's own bar, nothing generated on top of it. */
final class FutureReminderDue extends Notification implements ExpoPushable
{
    public function __construct(
        private readonly FutureReminder $reminder,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', ExpoPushChannel::class];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'future_reminder',
            'future_reminder_id' => $this->reminder->id,
            'message' => $this->reminder->message,
        ];
    }

    /** @return array{title: string, body: string, data: array<string, mixed>} */
    public function toExpo(object $notifiable): array
    {
        return [
            'title' => $this->reminder->message,
            'body' => $this->reminder->message,
            'data' => [
                'kind' => 'future_reminder',
                'future_reminder_id' => $this->reminder->id,
            ],
        ];
    }
}
