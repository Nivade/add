<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Contracts\Appointment;
use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ResolvesOwnedAppointment
{
    /** Someone else's appointment is not found rather than forbidden: its existence is not theirs to learn. */
    protected function ownedAppointment(Request $request, Intention|CalendarEvent $appointment): Appointment&Model
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);
        abort_unless($appointment->user_id === $user->id, 404);

        return $appointment;
    }
}
