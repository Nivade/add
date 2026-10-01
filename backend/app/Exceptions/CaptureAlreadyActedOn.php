<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Attributes\RespondsWith;
use Symfony\Component\HttpFoundation\Response;

#[RespondsWith(Response::HTTP_CONFLICT)]
final class CaptureAlreadyActedOn extends OutOfDateTransition {}
