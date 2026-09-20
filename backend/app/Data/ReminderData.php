<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A reminder the person has not seen yet, in the words the notification was sent with. */
#[TypeScript]
class ReminderData extends Data
{
    /** @param  list<string>  $lines */
    public function __construct(
        public string $id,
        public string $title,
        public array $lines,
    ) {}
}
