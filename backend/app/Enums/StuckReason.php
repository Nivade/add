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
    case NotHere = 'not_here';
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
            self::NotHere => StuckResolution::Elsewhere,
            self::SomethingElse => StuckResolution::StayPut,
        };
    }

    /** Said above the step that replaces the one they were stuck on; a stop says its own line. */
    public function acknowledgement(string $intentionTitle): ?string
    {
        return match ($this) {
            self::TooBig, self::DontKnowWhatToDo => "Let's make it smaller. Forget the rest of {$intentionTitle} for now.",
            self::NeedSomething, self::NotEnoughInformation => 'That one can wait until you have what it needs. Here is something you can do now.',
            self::NotHere => 'That one waits until you are there. Here is one you can do here.',
            self::SomethingElse => 'Noted. This one is still here when you want it.',
            self::Tired, self::DontWantTo => null,
        };
    }
}
