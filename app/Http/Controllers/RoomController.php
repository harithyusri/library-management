<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms.
     */
    public function index(Request $request)
    {
        $query = Room::query();

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('room_number', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by capacity
        if ($request->has('min_capacity') && $request->min_capacity) {
            $query->where('capacity', '>=', $request->min_capacity);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'room_number');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $rooms = $query->paginate(15)->withQueryString();

        return Inertia::render('Rooms/Index', [
            'rooms' => $rooms,
            'filters' => $request->only(['search', 'type', 'status', 'min_capacity', 'sort_by', 'sort_order']),
            'types' => [
                'study_room' => 'Study Room',
                'meeting_room' => 'Meeting Room',
                'computer_lab' => 'Computer Lab',
                'reading_room' => 'Reading Room',
                'conference_room' => 'Conference Room',
            ],
            'statuses' => [
                'available' => 'Available',
                'maintenance' => 'Under Maintenance',
                'unavailable' => 'Unavailable',
            ],
            'can' => [
                'createRooms' => $request->user()->can('create rooms'),
                'editRooms' => $request->user()->can('edit rooms'),
                'deleteRooms' => $request->user()->can('delete rooms'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new room.
     */
    public function create(Request $request)
    {
        if (!$request->user()->can('create rooms')) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Rooms/Create', [
            'types' => [
                'study_room' => 'Study Room',
                'meeting_room' => 'Meeting Room',
                'computer_lab' => 'Computer Lab',
                'reading_room' => 'Reading Room',
                'conference_room' => 'Conference Room',
            ],
            'amenitiesList' => [
                'wifi' => 'WiFi',
                'projector' => 'Projector',
                'whiteboard' => 'Whiteboard',
                'computers' => 'Computers',
                'air_conditioning' => 'Air Conditioning',
                'printer' => 'Printer',
                'phone' => 'Conference Phone',
                'tv' => 'TV/Monitor',
            ],
        ]);
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create rooms')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'room_number' => ['required', 'string', 'max:50', 'unique:rooms'],
            'type' => ['required', 'in:study_room,meeting_room,computer_lab,reading_room,conference_room'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'description' => ['nullable', 'string'],
            'amenities' => ['nullable', 'array'],
            'floor' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:available,maintenance,unavailable'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'image' => ['nullable', 'image', 'max:2048'], // 2MB max
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('rooms', 'public');
        }

        $room = Room::create($validated);

        return redirect()->route('rooms.show', $room->id)
            ->with('success', 'Room created successfully!');
    }

    /**
     * Display the specified room.
     */
    public function show(Request $request, Room $room)
    {
        $room->load(['bookings' => function ($query) {
            $query->where('booking_date', '>=', now()->toDateString())
                  ->whereIn('status', ['pending', 'confirmed'])
                  ->orderBy('booking_date')
                  ->orderBy('start_time')
                  ->limit(10);
        }, 'bookings.user']);

        return Inertia::render('Rooms/Show', [
            'room' => $room,
            'upcomingBookings' => $room->bookings,
            'can' => [
                'editRooms' => $request->user()->can('edit rooms'),
                'deleteRooms' => $request->user()->can('delete rooms'),
                'bookRooms' => $request->user()->can('create room bookings'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified room.
     */
    public function edit(Request $request, Room $room)
    {
        if (!$request->user()->can('edit rooms')) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('Rooms/Edit', [
            'room' => $room,
            'types' => [
                'study_room' => 'Study Room',
                'meeting_room' => 'Meeting Room',
                'computer_lab' => 'Computer Lab',
                'reading_room' => 'Reading Room',
                'conference_room' => 'Conference Room',
            ],
            'amenitiesList' => [
                'wifi' => 'WiFi',
                'projector' => 'Projector',
                'whiteboard' => 'Whiteboard',
                'computers' => 'Computers',
                'air_conditioning' => 'Air Conditioning',
                'printer' => 'Printer',
                'phone' => 'Conference Phone',
                'tv' => 'TV/Monitor',
            ],
        ]);
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, Room $room)
    {
        if (!$request->user()->can('edit rooms')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'room_number' => ['required', 'string', 'max:50', 'unique:rooms,room_number,' . $room->id],
            'type' => ['required', 'in:study_room,meeting_room,computer_lab,reading_room,conference_room'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'description' => ['nullable', 'string'],
            'amenities' => ['nullable', 'array'],
            'floor' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'in:available,maintenance,unavailable'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($room->image) {
                Storage::disk('public')->delete($room->image);
            }
            $validated['image'] = $request->file('image')->store('rooms', 'public');
        }

        $room->update($validated);

        return redirect()->route('rooms.show', $room->id)
            ->with('success', 'Room updated successfully!');
    }

    /**
     * Remove the specified room.
     */
    public function destroy(Request $request, Room $room)
    {
        if (!$request->user()->can('delete rooms')) {
            abort(403, 'Unauthorized');
        }

        // Check if room has active bookings
        $activeBookings = $room->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('booking_date', '>=', now()->toDateString())
            ->count();

        if ($activeBookings > 0) {
            return redirect()->back()
                ->with('error', "Cannot delete room with {$activeBookings} active bookings.");
        }

        // Delete image
        if ($room->image) {
            Storage::disk('public')->delete($room->image);
        }

        $room->delete();

        return redirect()->route('rooms.index')
            ->with('success', 'Room deleted successfully!');
    }
}