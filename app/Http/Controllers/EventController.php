<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/events
     * Public: pagination, search by title, filter by date/location
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'events_' . md5(json_encode($request->all()));

        $events = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            return Event::with('organizer:id,name,email')
                ->withCount('tickets')
                ->published()
                ->searchByTitle($request->search)
                ->filterByDate($request->date_from, $request->date_to)
                ->filterByLocation($request->location)
                ->orderBy('date', 'asc')
                ->paginate($request->per_page ?? 10);
        });

        return $this->paginatedResponse($events, 'Events retrieved successfully.');
    }

    /**
     * GET /api/v1/events/{id}
     * Show single event with tickets
     */
    public function show(int $id): JsonResponse
    {
        $event = Event::with([
            'organizer:id,name,email',
            'tickets' => fn($q) => $q->available(),
        ])->findOrFail($id);

        return $this->successResponse(
            data: $event,
            message: 'Event retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/events
     * Organizer/Admin only
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        Cache::flush(); // invalidate events cache

        return $this->successResponse(
            data: $event->load('organizer:id,name,email'),
            message: 'Event created successfully.',
            code: 201
        );
    }

    /**
     * PUT /api/v1/events/{id}
     * Organizer (own events) / Admin (all events)
     */
    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        // Organizer can only update their own events
        if (auth()->user()->isOrganizer() && $event->created_by !== auth()->id()) {
            return $this->errorResponse(
                message: 'Forbidden. You can only update your own events.',
                code: 403
            );
        }

        $event->update($request->validated());

        Cache::flush();

        return $this->successResponse(
            data: $event->fresh('organizer:id,name,email'),
            message: 'Event updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/events/{id}
     * Organizer (own events) / Admin (all events)
     */
    public function destroy(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        // Organizer can only delete their own events
        if (auth()->user()->isOrganizer() && $event->created_by !== auth()->id()) {
            return $this->errorResponse(
                message: 'Forbidden. You can only delete your own events.',
                code: 403
            );
        }

        $event->delete();

        Cache::flush();

        return $this->successResponse(
            data: null,
            message: 'Event deleted successfully.'
        );
    }
}
