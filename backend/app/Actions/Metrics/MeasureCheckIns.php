<?php

declare(strict_types=1);

namespace App\Actions\Metrics;

use App\Data\Metrics\CheckInOutcomeData;
use App\Enums\CheckInAnswer;
use App\Enums\CheckInTopic;
use App\Models\CheckIn;
use App\Support\Metrics\MetricsWindow;
use Lorisleiva\Actions\Concerns\AsObject;

final class MeasureCheckIns
{
    use AsObject;

    /** @return list<CheckInOutcomeData> */
    public function handle(MetricsWindow $window): array
    {
        $counts = $window->within($window->scope(CheckIn::query()), 'created_at')
            ->toBase()
            ->selectRaw('topic, answer, count(*) as answers')
            ->groupBy('topic', 'answer')
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->topic.':'.$row->answer => (int) $row->answers]);

        return array_map(fn (CheckInTopic $topic): CheckInOutcomeData => new CheckInOutcomeData(
            topic: $topic,
            less: $counts->get($topic->value.':'.CheckInAnswer::Less->value, 0),
            same: $counts->get($topic->value.':'.CheckInAnswer::Same->value, 0),
            more: $counts->get($topic->value.':'.CheckInAnswer::More->value, 0),
            notNow: $counts->get($topic->value.':'.CheckInAnswer::NotNow->value, 0),
        ), CheckInTopic::cases());
    }
}
