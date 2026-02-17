<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Public Routes (no token required) ─────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);
    });

    // ── Public Event Routes ────────────────────────────────────────────────
    Route::get('/events',      [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);

    // ── Protected Routes ───────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me',           [AuthController::class, 'me']);

        // ── Organizer + Admin ──────────────────────────────────────────────
        Route::middleware('role:organizer,admin')->group(function () {
            Route::post('/events',        [EventController::class, 'store']);
            Route::put('/events/{id}',    [EventController::class, 'update']);
            Route::delete('/events/{id}', [EventController::class, 'destroy']);

            Route::post('/events/{event_id}/tickets', [TicketController::class, 'store']);
            Route::put('/tickets/{id}',               [TicketController::class, 'update']);
            Route::delete('/tickets/{id}',            [TicketController::class, 'destroy']);
        });

        // ── Customer only ──────────────────────────────────────────────────
        Route::middleware('role:customer')->group(function () {
            // prevent.double.booking runs before BookingController@store
            Route::post('/tickets/{id}/bookings', [BookingController::class, 'store'])
                ->middleware('prevent.double.booking');

            Route::get('/bookings',             [BookingController::class, 'index']);
            Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
        });

        // ── Payments ──────────────────────────────────────────────────────
        Route::post('/bookings/{id}/payment', [PaymentController::class, 'store']);
        Route::get('/payments/{id}',          [PaymentController::class, 'show']);

        // ── Admin only ────────────────────────────────────────────────────
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/bookings', [BookingController::class, 'adminIndex']);
        });
    });
});