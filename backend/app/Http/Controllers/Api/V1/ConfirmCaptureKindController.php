<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Captures\ConfirmCaptureKind;
use App\Data\CaptureData;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Models\Capture;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConfirmCaptureKindController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, Capture $capture): Response
    {
        return CaptureData::from(ConfirmCaptureKind::run($this->owned($request, $capture)))->toResponse($request)->setStatusCode(Response::HTTP_OK);
    }
}
