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

    /** Exhaustive on purpose: a reason added without an answer here is a compile-time hole. */
    public function resolution(): StuckResolution
    {
        return match ($this) {
            self::TooBig, self::DontKnowWhatToDo => StuckResolution::Split,
            self::NeedSomething, self::NotEnoughInformation => StuckResolution::NextStep,
            self::Tired, self::DontWantTo => StuckResolution::Stop,
            self::SomethingElse => StuckResolution::StayPut,
        };
    }
}
