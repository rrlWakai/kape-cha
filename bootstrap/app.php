<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
        |----------------------------------------------------------------------
        | Nothing technical reaches a browser
        |----------------------------------------------------------------------
        |
        | A raw SQLSTATE, PDOException or "Call to a member function" is a
        | development message. Shown to a customer it is at best confusing and
        | at worst a disclosure of the schema, so every database failure is
        | logged with full detail and answered with a human sentence.
        |
        | The two audiences get different wording: an operator needs to know
        | whether anything was saved, a customer only needs to know it did
        | not work and what to do next.
        */

        $exceptions->render(function (QueryException $e, Request $request) {
            Log::error('Database error surfaced to a user.', [
                'url' => $request->fullUrl(),
                'exception' => $e,
            ]);

            $message = $request->user()?->isAdmin() ?? false
                ? 'The database rejected that change. Nothing was saved. Please try again.'
                : 'We could not complete that request. Please try again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $message);
        });

        // A CSRF token that no longer matches means the form sat open too long.
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has expired.'], 419);
            }

            return response()->view('errors.419', [], 419);
        });

        /*
        | Any remaining unexpected exception. The framework already presents
        | several exception types properly — a validation failure redirects
        | back with its error bag, an auth failure redirects to the login
        | screen, an abort keeps its own status and page — so those are handed
        | straight back to it. Only a genuine bug gets masked here.
        */
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof HttpExceptionInterface) {
                return null;
            }

            Log::error('Unhandled exception rendered to a user.', [
                'url' => $request->fullUrl(),
                'exception' => $e,
            ]);

            $message = 'We could not complete that request. Nothing was changed. Please try again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return response()->view('errors.500', [], 500);
        });

        // A 404 with no page of our own still gets our own words.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'The page you are looking for does not exist.'], 404);
            }

            return response()->view('errors.404', [], 404);
        });
    })->create();
