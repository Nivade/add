<?php

declare(strict_types=1);

namespace App\Actions\Metrics;

use App\Actions\Concerns\ReadsCommandAttributes;
use App\Data\Metrics\CheckInOutcomeData;
use App\Data\Metrics\OutcomesData;
use App\Data\Metrics\RungOutcomeData;
use App\Enums\CommitmentStatus;
use App\Models\Commitment;
use App\Models\User;
use App\Support\Metrics\MetricsWindow;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;

/** For whoever builds the app, never a screen: optimising these in front of the person is the theater the spec forbids. */
#[Signature('metrics:report {--days=28 : How many days back the window reaches} {--user= : One person by id, instead of everyone}')]
#[Description('Report the outcomes spec §36 asks for, as numbers only.')]
final class ReportOutcomes
{
    use AsCommand;
    use AsObject;
    use ReadsCommandAttributes;

    public function handle(MetricsWindow $window): OutcomesData
    {
        return new OutcomesData(
            intentions: MeasureIntentions::run($window),
            sessions: MeasureSessions::run($window),
            recovery: MeasureRecovery::run($window),
            commitmentsKept: $window->within($window->scope(Commitment::query()), 'updated_at')->where('status', CommitmentStatus::Kept)->count(),
            rungs: MeasureRungs::run($window),
            checkIns: MeasureCheckIns::run($window),
        );
    }

    public function asCommand(Command $command): int
    {
        $days = (int) $command->option('days');
        $user = $command->option('user');
        $userId = is_numeric($user) ? (int) $user : null;

        if ($days < 1) {
            $command->error('--days has to be at least 1.');

            return Command::FAILURE;
        }

        if ($user !== null && ($userId === null || ! User::query()->whereKey($userId)->exists())) {
            $command->error('No user has that id.');

            return Command::FAILURE;
        }

        $window = MetricsWindow::lastDays($days, CarbonImmutable::now(), $userId);
        $outcomes = $this->handle($window);

        $command->info("From {$window->from->toDateTimeString()} to {$window->to->toDateTimeString()} UTC, ".($window->userId === null ? 'everyone' : "user {$window->userId}").'.');
        $this->print($command, $outcomes);

        return Command::SUCCESS;
    }

    private function print(Command $command, OutcomesData $outcomes): void
    {
        $intentions = $outcomes->intentions;
        $sessions = $outcomes->sessions;
        $recovery = $outcomes->recovery;

        $command->table(['Intentions created', ''], [
            ['created', (string) $intentions->created],
            ['done', $this->share($intentions->done, $intentions->created)],
            ['set aside', (string) $intentions->setAside],
            ['still open', (string) $intentions->open],
            ['not started yet', (string) $intentions->notStarted],
            ['minutes to first start, median', $this->minutes($intentions->medianMinutesToStart)],
            ['minutes to first start, 75th percentile', $this->minutes($intentions->p75MinutesToStart)],
        ]);

        $command->table(['Sessions ended', ''], [
            ['ended', (string) $sessions->ended],
            ['with a step done', $this->share($sessions->withProgress, $sessions->ended)],
            ['distractions', (string) $sessions->distractions],
            ['a step done after the distraction', $this->share($sessions->backAfterDistraction, $sessions->distractions)],
        ]);

        $command->table(['Recovery', ''], [
            ['sessions landed unfinished', (string) $recovery->landedUnfinished],
            ['picked back up within '.MeasureRecovery::RECOVERY_DAYS.' days, or done', $this->share($recovery->pickedBackUp, $recovery->landedUnfinished)],
            ['steps skipped', (string) $recovery->skippedSteps],
            ['skipped, then done', $this->share($recovery->skippedThenDone, $recovery->skippedSteps)],
            ['commitments kept', (string) $outcomes->commitmentsKept],
        ]);

        $command->table(['Rung behind the start', 'starts', 'done in session', 'skipped in session'], array_map(fn (RungOutcomeData $rung): array => [
            $rung->rung ?? 'not recommended',
            (string) $rung->starts,
            $this->share($rung->doneInSession, $rung->starts),
            $this->share($rung->skippedInSession, $rung->starts),
        ], $outcomes->rungs));

        $command->table(['Check-in', 'less', 'about the same', 'more', 'not now'], array_map(fn (CheckInOutcomeData $checkIn): array => [
            $checkIn->topic->value,
            (string) $checkIn->less,
            (string) $checkIn->same,
            (string) $checkIn->more,
            (string) $checkIn->notNow,
        ], $outcomes->checkIns));
    }

    private function share(int $part, int $whole): string
    {
        return $whole === 0 ? '—' : sprintf('%d of %d (%d%%)', $part, $whole, round($part / $whole * 100));
    }

    private function minutes(?int $minutes): string
    {
        return $minutes === null ? '—' : (string) $minutes;
    }
}
