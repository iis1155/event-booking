<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Event;
use App\Models\Ticket;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/events/{event_id}/tickets
     * Organizer (own events) / Admin
     */
    public function store(StoreTicketRequest $request, int $eventId): JsonResponse
    {
        $event = Event::findOrFail($eventId);

        // Organizer ownership check
        if (auth()->user()->isOrganizer() && $event->created_by !== auth()->id()) {
            return $this->errorResponse(
                message: 'Forbidden. You can only add tickets to your own events.',
                code: 403
            );
        }

        $ticket = Ticket::create([
            ...$request->validated(),
            'event_id' => $eventId,
        ]);

        return $this->successResponse(
            data: $ticket->load('event:id,title'),
            message: 'Ticket created successfully.',
            code: 201
        );
    }

    /**
     * PUT /api/v1/tickets/{id}
     * Organizer (own event tickets) / Admin
     */
    public function update(UpdateTicketRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::with('event')->findOrFail($id);

        // Organizer ownership check
        if (auth()->user()->isOrganizer() && $ticket->event->created_by !== auth()->id()) {
            return $this->errorResponse(
                message: 'Forbidden. You can only update tickets for your own events.',
                code: 403
            );
        }

        $ticket->update($request->validated());

        return $this->successResponse(
            data: $ticket->fresh('event:id,title'),
            message: 'Ticket updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/tickets/{id}
     * Organizer (own event tickets) / Admin
     */
    public function destroy(int $id): JsonResponse
    {
        $ticket = Ticket::with('event')->findOrFail($id);

        // Organizer ownership check
        if (auth()->user()->isOrganizer() && $ticket->event->created_by !== auth()->id()) {
            return $this->errorResponse(
                message: 'Forbidden. You can only delete tickets for your own events.',
                code: 403
            );
        }

        // Prevent deleting ticket that has active bookings
        $activeBookings = $ticket->bookings()->active()->count();
        if ($activeBookings > 0) {
            return $this->errorResponse(
                message: 'Cannot delete ticket with active bookings.',
                code: 422
            );
        }

        $ticket->delete();

        return $this->successResponse(
            data: null,
            message: 'Ticket deleted successfully.'
        );
    }
}
