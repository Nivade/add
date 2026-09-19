<?php

declare(strict_types=1);

namespace App\Actions\Captures;

use App\Actions\Intentions\ConvertCaptureToIntention;
use App\Enums\CaptureSource;
use App\Models\Capture;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** Returns before anything is parsed: a thought costs nothing to drop here, including when the model is down. */
final class RecordCapture
{
    use AsObject;

    public function handle(User $user, string $body, CaptureSource $source = CaptureSource::Text): Capture
    {
        $capture = Capture::create([
            'user_id' => $user->id,
            'body' => trim($body),
            'source' => $source,
        ]);

        ConvertCaptureToIntention::dispatch($capture);

        return $capture;
    }
}
