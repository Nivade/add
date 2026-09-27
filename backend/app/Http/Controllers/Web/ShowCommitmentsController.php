<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Commitments\ListOpenCommitments;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Response;

final class ShowCommitmentsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return inertia('commitments', ['list' => ListOpenCommitments::run($this->user($request))]);
    }
}
