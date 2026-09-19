<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum StuckReason: string
{
    case DontKnowWhatToDo = 'dont_know_what_to_do';
    case TooBig = 'too_big';
    case NeedSomething = 'need_something';
    case NotEnoughInformation = 'not_enough_information';
    case Tired = 'tired';
    case DontWantTo = 'dont_want_to';
    case SomethingElse = 'something_else';

    /** Whether the answer is that the step itself should get smaller. */
    public function wantsSmallerStep(): bool
    {
        return $this === self::TooBig || $this === self::DontKnowWhatToDo;
    }
}
