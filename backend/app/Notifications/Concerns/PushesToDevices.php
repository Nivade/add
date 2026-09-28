<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Attributes\NotificationKind;
use App\Notifications\Channels\ExpoPushChannel;
use LogicException;
use ReflectionClass;

trait PushesToDevices
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', ExpoPushChannel::class];
    }

    public function kind(): string
    {
        $attribute = new ReflectionClass(static::class)->getAttributes(NotificationKind::class)[0] ?? null;

        if ($attribute === null) {
            throw new LogicException(static::class.' uses PushesToDevices but carries no #[NotificationKind] attribute and does not override kind().');
        }

        return $attribute->newInstance()->kind;
    }
}
