<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Enums\Place;
use App\Enums\StepStatus;
use App\Models\Step;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/** Where the person seems to be, from what they did and what they said, never from a question. */
final readonly class Whereabouts
{
    public const int LIKELY_MINUTES = 45;

    public const int NOT_HERE_HOURS = 2;

    private const int FITS = 2;

    private const int UNKNOWN = 1;

    private const int DOES_NOT_FIT = 0;

    /**
     * @param  list<Place>  $likely
     * @param  list<Place>  $unlikely
     */
    public function __construct(
        public array $likely = [],
        public array $unlikely = [],
    ) {}

    public static function forUser(User $user, CarbonImmutable $now): self
    {
        // Oldest first, so the latest per place is the one pluck keeps.
        $completed = Step::query()
            ->where('status', StepStatus::Done)
            ->whereNotNull('place')
            ->where('completed_at', '>=', $now->subMinutes(self::LIKELY_MINUTES))
            ->whereHas('intention', fn (Builder $query) => $query->where('user_id', $user->id))
            ->oldest('completed_at')
            ->pluck('completed_at', 'place');

        $reported = $user->notHereReports()
            ->where('created_at', '>=', $now->subHours(self::NOT_HERE_HOURS))
            ->oldest()
            ->pluck('created_at', 'place');

        return self::weigh(self::instantsByPlace($completed->all()), self::instantsByPlace($reported->all()));
    }

    public function fit(?Place $place): int
    {
        return match (true) {
            in_array($place, $this->likely, true) => self::FITS,
            in_array($place, $this->unlikely, true) => self::DOES_NOT_FIT,
            default => self::UNKNOWN,
        };
    }

    public function isKnown(): bool
    {
        return $this->likely !== [] || $this->unlikely !== [];
    }

    /**
     * @param  array<string, CarbonImmutable>  $completed
     * @param  array<string, CarbonImmutable>  $reported
     */
    private static function weigh(array $completed, array $reported): self
    {
        $stands = fn (Place $place): bool => isset($completed[$place->value])
            && (! isset($reported[$place->value]) || $completed[$place->value] > $reported[$place->value]);

        // Only the latest location can be where they are: an older one was left behind, whatever came after.
        $latest = self::latestLocation($completed);
        $here = $latest instanceof Place && $stands($latest) ? $latest : null;

        $likely = array_filter(Place::cases(), fn (Place $place): bool => $place === $here
            || (! $place->isLocation() && $stands($place)));

        $unlikely = array_filter(Place::cases(), fn (Place $place): bool => ! in_array($place, $likely, true)
            && (isset($reported[$place->value]) || ($place->isLocation() && $here instanceof Place)));

        return new self(array_values($likely), array_values($unlikely));
    }

    /** @param  array<string, CarbonImmutable>  $completed */
    private static function latestLocation(array $completed): ?Place
    {
        $latest = null;

        foreach ($completed as $value => $at) {
            $place = Place::from($value);

            if ($place->isLocation() && ($latest === null || $at > $completed[$latest->value])) {
                $latest = $place;
            }
        }

        return $latest;
    }

    /**
     * @param  array<array-key, mixed>  $plucked
     * @return array<string, CarbonImmutable>
     */
    private static function instantsByPlace(array $plucked): array
    {
        $instants = [];

        foreach ($plucked as $place => $at) {
            if (is_string($place) && $at instanceof CarbonImmutable) {
                $instants[$place] = $at;
            }
        }

        return $instants;
    }
}
