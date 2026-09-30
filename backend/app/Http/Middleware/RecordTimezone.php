<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use IntlTimeZone;
use Symfony\Component\HttpFoundation\Response;

/** The device knows the person's zone; every clock the server draws is read in it. */
final class RecordTimezone
{
    /** @param Closure(Request): (Response) $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $zone = $this->canonical($request->header('X-Timezone') ?? $request->cookie('tz'));

        if ($user instanceof User && $zone !== null && $zone !== $user->timezone) {
            $user->forceFill(['timezone' => $zone])->save();
        }

        return $next($request);
    }

    /** Some browsers still report a renamed zone, such as Asia/Calcutta, which PHP no longer knows. */
    private function canonical(mixed $zone): ?string
    {
        if (! is_string($zone)) {
            return null;
        }

        $known = DateTimeZone::listIdentifiers();
        $renamed = IntlTimeZone::getIanaID($zone);

        return match (true) {
            in_array($zone, $known, true) => $zone,
            is_string($renamed) && in_array($renamed, $known, true) => $renamed,
            default => null,
        };
    }
}
