<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\IssueAccessToken;
use App\Data\AccessTokenData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTokenRequest;

final class StoreTokenController extends Controller
{
    public function __invoke(StoreTokenRequest $request): AccessTokenData
    {
        $deviceName = $request->string('device_name')->toString();

        return new AccessTokenData(
            IssueAccessToken::run(
                $request->string('email')->toString(),
                $request->string('password')->toString(),
                $deviceName,
                $request->string('code')->toString() ?: null,
            ),
            $deviceName,
        );
    }
}
