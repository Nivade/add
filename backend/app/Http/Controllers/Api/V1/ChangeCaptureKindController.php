<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Captures\ChangeCaptureKind;
use App\Data\CaptureData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeCaptureKindRequest;
use App\Models\Capture;
use Symfony\Component\HttpFoundation\Response;

final class ChangeCaptureKindController extends Controller
{
    use ResolvesOwned;

    public function __invoke(ChangeCaptureKindRequest $request, Capture $capture): Response
    {
        return CaptureData::from(ChangeCaptureKind::run($this->owned($request, $capture), $request->kind()))->toResponse($request)->setStatusCode(Response::HTTP_OK);
    }
}
