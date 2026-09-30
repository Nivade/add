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

        $count = fn (CheckInTopic $topic, CheckInAnswer $answer): int => $counts->get($topic->value.':'.$answer->value, 0);

        return array_map(fn (CheckInTopic $topic): CheckInOutcomeData => new CheckInOutcomeData(
            topic: $topic,
            less: $count($topic, CheckInAnswer::Less),
            same: $count($topic, CheckInAnswer::Same),
            more: $count($topic, CheckInAnswer::More),
            notNow: $count($topic, CheckInAnswer::NotNow),
        ), CheckInTopic::cases());
    }
}
