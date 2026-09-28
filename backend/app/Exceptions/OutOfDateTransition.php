<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use RuntimeException;

/** A client asking for a change that no longer applies is out of date, not broken. */
abstract class OutOfDateTransition extends RuntimeException implements ShouldntReport {}
