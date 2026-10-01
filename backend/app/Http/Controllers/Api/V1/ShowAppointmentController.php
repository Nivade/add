<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Data\ComingUpData;
use App\Enums\AppointmentKind;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use App\Http\Responses\NullAnswer;
use Illuminate\Http\Request;

/** A deep link resolves the appointment it names directly, rather than trusting it is still what home shows next. */
final class ShowAppointmentController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, AppointmentKind $kind, string $id): ComingUpData|NullAnswer
    {
        $appointment = $kind->find($id);

        abort_if(! $appointment instanceof \App\Contracts\Appointment, 404);

        $appointment = $this->owned($request, $appointment);

        return ComingUpData::of($appointment, $this->user($request)->now()) ?? new NullAnswer;
    }
}
