<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Enums\Place;
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
            ->whereNotNull('place')
            ->where('completed_at', '>=', $now->subMinutes(self::LIKELY_MINUTES))
            ->whereHas('intention', fn (Builder $query) => $query->where('user_id', $user->id))
            ->oldest('completed_at')
            ->pluck('completed_at', 'place');

        $reported = $user->notHereReports()
            ->where('created_at', '>=', $now->subHours(self::NOT_HERE_HOURS))
            ->oldest()
            ->pluck('created_at', 'place');

        return self::weigh($completed->all(), $reported->all());
    }

    public function fit(?Place $place): int
    {
        return match (true) {
            ! $place instanceof Place => self::UNKNOWN,
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
     * @param  array<array-key, mixed>  $completed
     * @param  array<array-key, mixed>  $reported
     */
    private static function weigh(array $completed, array $reported): self
    {
        $likely = [];
        $unlikely = [];

        foreach (Place::cases() as $place) {
            $doneAt = $completed[$place->value] ?? null;
            $saidNotAt = $reported[$place->value] ?? null;

            if ($doneAt instanceof CarbonImmutable && (! $saidNotAt instanceof CarbonImmutable || $doneAt > $saidNotAt)) {
                $likely[$place->value] = $doneAt;
            } elseif ($saidNotAt instanceof CarbonImmutable) {
                $unlikely[$place->value] = $place;
            }
        }

        $here = self::latestLocation($likely);

        // A person is in one location at a time, so the latest one rules out the others.
        if ($here instanceof Place) {
            foreach (Place::cases() as $place) {
                if ($place->isLocation() && $place !== $here) {
                    unset($likely[$place->value]);
                    $unlikely[$place->value] = $place;
                }
            }
        }

        return new self(
            array_map(Place::from(...), array_keys($likely)),
            array_values(array_filter(Place::cases(), fn (Place $place): bool => isset($unlikely[$place->value]))),
        );
    }

    /** @param  array<string, CarbonImmutable>  $likely */
    private static function latestLocation(array $likely): ?Place
    {
        $locations = array_filter($likely, fn (string $place): bool => Place::from($place)->isLocation(), ARRAY_FILTER_USE_KEY);
        arsort($locations);
        $latest = array_key_first($locations);

        return $latest === null ? null : Place::from($latest);
    }
}
