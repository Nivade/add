<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Attributes\RespondsWith;
use Nvade\AiToolkit\Exceptions\AiUnavailable;
use Symfony\Component\HttpFoundation\Response;

#[RespondsWith(Response::HTTP_SERVICE_UNAVAILABLE, 'Reading this needs AI, which is off. It can be turned on in settings.')]
final class AiConsentRequired extends AiUnavailable {}
