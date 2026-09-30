<?php

declare(strict_types=1);

namespace App\Support\Metrics;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** `[from, to)`, over everyone unless one person is named. */
final readonly class MetricsWindow
{
    public function __construct(public CarbonImmutable $from, public CarbonImmutable $to, public ?int $userId = null) {}

    public static function lastDays(int $days, CarbonImmutable $now, ?int $userId = null): self
    {
        return new self($now->subDays($days), $now, $userId);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scope(Builder $query, string $column = 'user_id'): Builder
    {
        return $this->userId === null ? $query : $query->where($column, $this->userId);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function within(Builder $query, string $column): Builder
    {
        return $query->where($column, '>=', $this->from)->where($column, '<', $this->to);
    }
}
