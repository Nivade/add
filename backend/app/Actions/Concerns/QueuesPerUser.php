<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** One person per job: a slow provider must not hold up everybody else's minute. */
trait QueuesPerUser
{
    /** Chunked, because the whole user table does not belong in memory to queue off it. */
    private const int QUEUE_CHUNK = 200;

    public function asCommand(Command $command): void
    {
        $queued = 0;

        // The job carries only the key and loads the person itself, so the chunk needs nothing else.
        User::query()
            ->select('id')
            ->when($command->argument('user'), fn (Builder $query, array|bool|float|int|string $id) => $query->whereKey($id))
            ->tap(fn (Builder $query) => $this->constrainQueued($query))
            ->chunkById(self::QUEUE_CHUNK, function (Collection $users) use (&$queued): void {
                foreach ($users as $user) {
                    self::dispatch($user);
                    $queued++;
                }
            });

        $command->info($queued.' queued.');
    }

    /** @param  Builder<User>  $query */
    protected function constrainQueued(Builder $query): void {}
}
