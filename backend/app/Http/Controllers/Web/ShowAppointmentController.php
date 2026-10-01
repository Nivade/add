<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Data\ComingUpData;
use App\Enums\AppointmentKind;
use App\Http\Controllers\Concerns\ResolvesOwned;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Response;

/** Where an appointment's settings live, so home only ever says when it is and what to do. */
final class ShowAppointmentController extends Controller
{
    use ResolvesOwned;

    public function __invoke(Request $request, AppointmentKind $kind, string $id): Response
    {
        $appointment = $kind->find($id);

        abort_if(! $appointment instanceof \App\Contracts\Appointment, 404);

        $comingUp = ComingUpData::of($this->owned($request, $appointment), $this->user($request)->now());

        abort_if(! $comingUp instanceof ComingUpData, 404);

        return inertia('appointment', ['appointment' => $comingUp]);
    }
}
