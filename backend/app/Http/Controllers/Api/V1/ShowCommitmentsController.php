<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Commitments\ListOpenCommitments;
use App\Data\CommitmentListData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class ShowCommitmentsController extends Controller
{
    public function __invoke(Request $request): CommitmentListData
    {
        return ListOpenCommitments::run($this->user($request));
    }
}
