<?php

declare(strict_types=1);

namespace App\Actions\Commitments;

use App\Data\CommitmentData;
use App\Data\CommitmentListData;
use App\Models\Commitment;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

final class ListOpenCommitments
{
    use AsObject;

    public function handle(User $user): CommitmentListData
    {
        $open = Commitment::query()->where('user_id', $user->id)->open()->mostPressingFirst()->get();

        return new CommitmentListData(array_values($open->map(fn (Commitment $commitment): CommitmentData => CommitmentData::from($commitment))->all()));
    }
}
