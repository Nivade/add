<?php

declare(strict_types=1);

namespace App\Actions\WaitingFor;

use App\Enums\WaitingForStatus;
use App\Models\User;
use App\Models\WaitingFor;
use Lorisleiva\Actions\Concerns\AsObject;

/** Zero-friction, same as a capture: who or what, and what for. No AI, no classification. */
final class CreateWaitingFor
{
    use AsObject;

    public function handle(User $user, string $subject, ?string $note): WaitingFor
    {
        return WaitingFor::query()->create([
            'user_id' => $user->id,
            'subject' => trim($subject),
            'note' => $note !== null ? trim($note) : null,
            'status' => WaitingForStatus::Waiting,
        ]);
    }
}
