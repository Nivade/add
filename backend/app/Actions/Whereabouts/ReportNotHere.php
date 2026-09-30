<?php

declare(strict_types=1);

namespace App\Actions\Whereabouts;

use App\Enums\Place;
use App\Models\NotHereReport;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

final class ReportNotHere
{
    use AsObject;

    public function handle(User $user, Place $place): NotHereReport
    {
        return $user->notHereReports()->create(['place' => $place]);
    }
}
