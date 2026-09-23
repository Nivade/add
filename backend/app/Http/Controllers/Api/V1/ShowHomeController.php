<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Home\BuildHome;
use App\Data\HomeData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

final class ShowHomeController extends Controller
{
    public function __invoke(Request $request): HomeData
    {
        return BuildHome::run($this->user($request));
    }
}
