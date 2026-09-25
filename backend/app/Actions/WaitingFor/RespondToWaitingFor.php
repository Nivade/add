<?php

declare(strict_types=1);

namespace App\Actions\WaitingFor;

use App\Enums\WaitingForResponse;
use App\Models\WaitingFor;
use Lorisleiva\Actions\Concerns\AsObject;

/** "Wait longer" resets the staleness clock without changing status; the other three retire or restart it. */
final class RespondToWaitingFor
{
    use AsObject;

    public function handle(WaitingFor $waitingFor, WaitingForResponse $response): WaitingFor
    {
        $waitingFor->status = $response->status();
        $waitingFor->touch();

        return $waitingFor;
    }
}
