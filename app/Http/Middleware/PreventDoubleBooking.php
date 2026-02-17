<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDoubleBooking
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        $ticketId = $request->route('id');
        $userId   = $request->user()?->id;

        if ($ticketId && $userId) {
            $exists = Booking::where('user_id', $userId)
                ->where('ticket_id', $ticketId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($exists) {
                return $this->errorResponse(
                    message: 'You already have an active booking for this ticket.',
                    code: 422
                );
            }
        }

        return $next($request);
    }
}