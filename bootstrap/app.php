<?php

use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Http\Middleware\PreventDoubleBooking;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register role middleware alias
        $middleware->alias([
            'role' => CheckRole::class,
            'prevent.double.booking' => PreventDoubleBooking::class,
        ]);

        // Sanctum stateful middleware for cookie-based auth (if needed)
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {

    $exceptions->render(function (ValidationException $e, Request $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        }
    });

    $exceptions->render(function (UniqueConstraintViolationException $e, Request $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active booking for this ticket.',
            ], 422);
        }
    });

    $exceptions->render(function (AuthenticationException $e, Request $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login first.',
            ], 401);
        }
    });

})->create();
