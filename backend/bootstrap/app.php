<?php

declare(strict_types=1);

use App\Attributes\RespondsWith;
use App\Attributes\RespondsWithReader;
use App\Exceptions\OutOfDateTransition;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Nvade\AiToolkit\Exceptions\AiUnavailable;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // TLS terminates at Traefik, so without this every asset URL is http and the browser blocks it.
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // No-op while SENTRY_LARAVEL_DSN is empty, which is the default everywhere but the debug profile.
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            $respondsWith = RespondsWithReader::for($e);

            if (! $respondsWith instanceof RespondsWith || ! $request->expectsJson()) {
                return null;
            }

            return new JsonResponse(
                ['message' => $respondsWith->message ?? $e->getMessage()],
                $respondsWith->status,
            );
        });

        // A stale page on the web reloads rather than showing a 409 it cannot act on.
        $exceptions->render(fn (OutOfDateTransition $e, Request $request): ?RedirectResponse => $request->expectsJson() ? null : back());

        // After the attribute renderer, so a consent refusal keeps its own sentence; never the exception's detail.
        $exceptions->render(fn (AiUnavailable $e, Request $request): ?JsonResponse => $request->expectsJson()
            ? new JsonResponse(['message' => 'Reading this needs AI, which is not reachable right now. Try again in a while.'], Response::HTTP_SERVICE_UNAVAILABLE)
            : null);
    })->create();
