<?php

use App\Http\Middleware\PreventIndexingOutsideProduction;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Angemeldete, die /anmelden oder /registrieren aufrufen, landen im Konto
        $middleware->redirectUsersTo(fn () => route('account.show'));

        $middleware->web(append: [
            PreventIndexingOutsideProduction::class,
        ]);
        $middleware->api(append: [
            PreventIndexingOutsideProduction::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Testserver: Team (Redaktion/Admin) sieht bei 500ern Meldung, Datei und Zeile direkt im Browser –
        // wir testen nur auf dem Hosting. Besucher und Produktion sehen nur die normale Fehlerseite; keine .env-Werte.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (app()->isProduction() || $request->expectsJson()
                || $e instanceof HttpExceptionInterface
                || $e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException) {
                return null;
            }

            try {
                $canSee = $request->user()?->role->canEditContent() ?? false;
            } catch (Throwable) {
                $canSee = false;
            }
            if (! $canSee) {
                return null;
            }

            $base = base_path().DIRECTORY_SEPARATOR;
            $trace = collect($e->getTrace())
                ->filter(fn ($frame) => isset($frame['file']) && ! str_contains($frame['file'], DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR))
                ->take(8)
                ->map(fn ($frame) => str_replace($base, '', $frame['file']).':'.$frame['line'])
                ->values();

            return response()->view('errors.debug', [
                'exception' => $e,
                'file' => str_replace($base, '', $e->getFile()),
                'trace' => $trace,
                'url' => $request->fullUrl(),
            ], 500);
        });
    })->create();
