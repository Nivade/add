<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The device knows the person's zone; every clock the server draws is read in it. */
final class RecordTimezone
{
    /** @param Closure(Request): (Response) $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $zone = $request->header('X-Timezone') ?? $request->cookie('tz');

        if ($user instanceof User
            && is_string($zone)
            && $zone !== $user->timezone
            && in_array($zone, DateTimeZone::listIdentifiers(), true)) {
            $user->forceFill(['timezone' => $zone])->save();
        }

        return $next($request);
    }
}
