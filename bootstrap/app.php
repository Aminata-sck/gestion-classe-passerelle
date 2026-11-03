<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureAccountIsUsable;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SecurityHeaders::class,
        ]);
        $middleware->alias([
            'permission' => CheckPermission::class,
            'compte' => EnsureAccountIsUsable::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Erreurs 403 / 404 / 419 affichées dans l'interface Vue, sans trace technique.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();
            if ($status === 419) {
                return back()->with('error', 'La page a expiré. Veuillez réessayer.');
            }
            $afficher = in_array($status, [403, 404], true)
                || (in_array($status, [500, 503], true) && ! config('app.debug'));
            if ($afficher && ! $request->expectsJson()) {
                return Inertia::render('Erreur', ['statut' => $status])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })->create();
