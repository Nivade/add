<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Home\BuildRail;
use App\Data\RailData;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Override;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    #[Override]
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            // A closure, so a partial reload that does not ask for the rail does not pay for it.
            'rail' => $user instanceof User ? fn (): RailData => BuildRail::run($user) : null,
        ];
    }
}
