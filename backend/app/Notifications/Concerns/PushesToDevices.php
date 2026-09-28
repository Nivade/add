<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Attributes\NotificationKind;
use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Support\Traits\ReadsClassAttributes;
use LogicException;

trait PushesToDevices
{
    use ReadsClassAttributes;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', ExpoPushChannel::class];
    }

    public function kind(): string
    {
        $attribute = $this->getAttributeInstance($this, NotificationKind::class);

        if (! $attribute instanceof NotificationKind) {
            throw new LogicException(static::class.' uses PushesToDevices but carries no #[NotificationKind] attribute and does not override kind().');
        }

        return $attribute->kind;
    }
}
