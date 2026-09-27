<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Ingestion\ClassifyPastedText;
use App\Data\Ai\IngestionClassificationData;
use App\Http\Requests\ClassifyPastedTextRequest;

/** Both clients read the same JSON: the paste dialog asks without leaving the page. */
final class ClassifyPastedTextController extends Controller
{
    public function __invoke(ClassifyPastedTextRequest $request): IngestionClassificationData
    {
        $user = $this->user($request);

        return ClassifyPastedText::run($user, $request->text());
    }
}
