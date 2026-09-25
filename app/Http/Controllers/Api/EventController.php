<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of all active events with pagination.
     */
    public function index(Request $request)
    {
        $query = Event::where('status', 1);

        // Search by title
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('title', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 20);
        $events = $query->paginate($perPage);

        return EventResource::collection($events);
    }

    /**
     * Display upcoming/featured events.
     */
    public function upcoming()
    {
        $events = Event::where('status', 1)
            ->latest()
            ->take(8)
            ->get();

        return EventResource::collection($events);
    }

    /**
     * Display a specific event.
     */
    public function show(Event $event)
    {
        // Check if event is active
        if ($event->status != 1) {
            return response()->json([
                'message' => 'Event not found.',
            ], 404);
        }

        return new EventResource($event);
    }

    /**
     * Search events by query.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
        ]);

        $search = $request->get('q');
        
        $events = Event::where('status', 1)
            ->where(function($query) use ($search) {
                $query->where('title', 'like', "%$search%")
                      ->orWhere('subtitle', 'like', "%$search%")
                      ->orWhere('description', 'like', "%$search%");
            })
            ->latest()
            ->paginate(10);

        return EventResource::collection($events);
    }

    /**
     * Get event statistics for dashboard.
     */
    public function stats()
    {
        return response()->json([
            'total_events' => Event::count(),
            'active_events' => Event::where('status', 1)->count(),
            'inactive_events' => Event::where('status', 0)->count(),
            'latest_event' => new EventResource(Event::latest()->first()),
        ]);
    }
}

