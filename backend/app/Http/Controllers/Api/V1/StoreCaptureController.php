<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Captures\RecordCapture;
use App\Data\CaptureData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCaptureRequest;
use App\Models\User;

final class StoreCaptureController extends Controller
{
    public function __invoke(StoreCaptureRequest $request): CaptureData
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return CaptureData::from(RecordCapture::run(
            $user,
            $request->string('body')->toString(),
            $request->source(),
        ));
    }
}
