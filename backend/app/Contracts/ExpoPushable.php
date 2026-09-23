<?php

declare(strict_types=1);

namespace App\Contracts;

/** What a notification has to answer before it can reach a lock screen. */
interface ExpoPushable
{
    /** @return array{title: string, body: string, data: array<string, mixed>} */
    public function toExpo(object $notifiable): array;
}
