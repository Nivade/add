<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ingestion\ClassifyPastedText;
use App\Data\Ai\IngestionClassificationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassifyPastedTextRequest;

final class ClassifyPastedTextController extends Controller
{
    public function __invoke(ClassifyPastedTextRequest $request): IngestionClassificationData
    {
        $user = $this->user($request);

        return ClassifyPastedText::run($user, $request->text());
    }
}
