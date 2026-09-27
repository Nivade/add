<?php

declare(strict_types=1);

namespace App\Actions\WaitingFor;

use App\Enums\WaitingForResponse;
use App\Exceptions\InvalidResponse;
use App\Models\WaitingFor;
use Lorisleiva\Actions\Concerns\AsObject;

/** Every answer restarts the staleness clock; received and cancelled also retire it. */
final class RespondToWaitingFor
{
    use AsObject;

    public function handle(WaitingFor $waitingFor, WaitingForResponse $response): WaitingFor
    {
        if (! $waitingFor->status->isOpen()) {
            throw new InvalidResponse("Waiting-for {$waitingFor->id} is already retired.");
        }

        $waitingFor->update(['status' => $response->status(), 'last_answered_at' => now()]);

        return $waitingFor;
    }
}
