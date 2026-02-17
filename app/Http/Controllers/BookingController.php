<?php

namespace App\Http\Controllers;

use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Ticket;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/bookings
     * Customer: view their own bookings
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with([
                'ticket.event:id,title,date,location',
                'payment',
            ])
            ->forUser(auth()->id())
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 10);

        return $this->paginatedResponse($bookings, 'Bookings retrieved successfully.');
    }

    /**
     * POST /api/v1/tickets/{id}/bookings
     * Customer: book a ticket
     */
    public function store(StoreBookingRequest $request, int $ticketId): JsonResponse
    {
        $ticket = Ticket::with('event')->findOrFail($ticketId);

        // Check ticket availability
        if (!$ticket->is_available) {
            return $this->errorResponse(
                message: 'Sorry, this ticket is sold out.',
                code: 422
            );
        }

        // Check requested quantity
        if ($request->quantity > $ticket->available_quantity) {
            return $this->errorResponse(
                message: "Only {$ticket->available_quantity} tickets available.",
                code: 422
            );
        }

        // Use DB transaction to prevent race conditions
        $booking = DB::transaction(function () use ($request, $ticket) {
            $booking = Booking::create([
                'user_id'      => auth()->id(),
                'ticket_id'    => $ticket->id,
                'quantity'     => $request->quantity,
                'total_amount' => $ticket->price * $request->quantity,
                'status'       => 'pending',
            ]);

            // Increment sold count atomically
            $ticket->increment('quantity_sold', $request->quantity);

            return $booking;
        });

        return $this->successResponse(
            data: $booking->load(['ticket.event:id,title,date,location']),
            message: 'Booking created successfully. Please proceed to payment.',
            code: 201
        );
    }

    /**
     * PUT /api/v1/bookings/{id}/cancel
     * Customer: cancel their own booking
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = Booking::forUser(auth()->id())->findOrFail($id);

        if ($booking->isCancelled()) {
            return $this->errorResponse(
                message: 'Booking is already cancelled.',
                code: 422
            );
        }

        if ($booking->isConfirmed()) {
            return $this->errorResponse(
                message: 'Confirmed bookings cannot be cancelled. Please contact support.',
                code: 422
            );
        }

        DB::transaction(function () use ($booking, $request) {
            // Restore ticket quantity
            $booking->ticket->decrement('quantity_sold', $booking->quantity);

            // Cancel booking
            $booking->cancel($request->reason ?? 'Cancelled by customer');
        });

        return $this->successResponse(
            data: $booking->fresh(),
            message: 'Booking cancelled successfully.'
        );
    }

    /**
     * GET /api/v1/admin/bookings
     * Admin: view all bookings
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $bookings = Booking::with([
                'user:id,name,email',
                'ticket.event:id,title',
                'payment',
            ])
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15);

        return $this->paginatedResponse($bookings, 'All bookings retrieved successfully.');
    }
}
