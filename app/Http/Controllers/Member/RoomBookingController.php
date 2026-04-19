<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomBookingController extends Controller
{
    private function formatTime(string $time): string
    {
        return substr($time, 0, 5);
    }

    private function calcDuration(string $start, string $end): float
    {
        $s = Carbon::createFromFormat('H:i', $start);
        $e = Carbon::createFromFormat('H:i', $end);
        return $e->diffInMinutes($s) / 60;
    }

    private function bookingRules(): array
    {
        return [
            'room_id'             => ['required', 'exists:rooms,id'],
            'booking_date'        => ['required', 'date', 'after_or_equal:today'],
            'start_time'          => ['required', 'date_format:H:i'],
            'end_time'            => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose'             => ['nullable', 'string', 'max:255'],
            'number_of_attendees' => ['nullable', 'integer', 'min:1', 'max:500'],
            'special_requests'    => ['nullable', 'string'],
        ];
    }

    private function hasConflict(array $validated): bool
    {
        return RoomBooking::where('room_id', $validated['room_id'])
            ->whereDate('booking_date', $validated['booking_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                  ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $bookings = RoomBooking::with(['room:id,name,room_number,hourly_rate,library_id', 'room.library'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn ($b) => [
                'id'           => $b->id,
                'room'         => $b->room,
                'booking_date' => $b->booking_date->toDateString(),
                'start_time'   => $this->formatTime($b->start_time),
                'end_time'     => $this->formatTime($b->end_time),
                'status'       => $b->status,
                'purpose'      => $b->purpose,
                'duration_hours' => $b->duration_in_hours,
                'total_cost'   => $b->total_cost ?? 0,
            ]);

        return Inertia::render('members/RoomBookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function create(Request $request)
    {
        $lat = $request->get('latitude');
        $lng = $request->get('longitude');

        $roomsQuery = Room::where('status', 'available')
            ->select('id', 'name', 'room_number', 'type', 'capacity', 'floor', 'hourly_rate', 'status', 'description', 'image', 'library_id')
            ->with('library');

        if ($lat && $lng) {
            $roomsQuery->join('libraries', 'rooms.library_id', '=', 'libraries.id')
                ->select('rooms.*')
                ->selectRaw("(6371 * acos(cos(radians(?)) * cos(radians(libraries.latitude)) * cos(radians(libraries.longitude) - radians(?)) + sin(radians(?)) * sin(radians(libraries.latitude)))) AS distance", [$lat, $lng, $lat])
                ->orderBy('distance');
        }

        $rooms = $roomsQuery->get()
            ->map(fn ($r) => array_merge($r->toArray(), [
                'image_url' => $r->image ? asset('storage/' . $r->image) : null,
            ]));

        $libraries = \App\Models\Library::orderBy('name')->get(['id', 'name']);

        $existingBookings = RoomBooking::whereIn('status', ['pending', 'confirmed'])
            ->select('room_id', 'booking_date', 'start_time', 'end_time')
            ->get()
            ->map(fn ($b) => [
                'room_id'      => $b->room_id,
                'booking_date' => $b->booking_date->toDateString(),
                'start_time'   => $this->formatTime($b->start_time),
                'end_time'     => $this->formatTime($b->end_time),
            ]);

        return Inertia::render('members/RoomBookings/Create', [
            'rooms'             => $rooms,
            'libraries'         => $libraries,
            'existingBookings'  => $existingBookings,
            'preselectedRoomId' => $request->integer('room_id') ?: null,
            'preselectedDate'   => $request->string('date')->toString() ?: null,
            'currentUser'       => [
                'id'    => $request->user()->id,
                'name'  => $request->user()->name,
                'email' => $request->user()->email,
                'isStaff' => false,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->bookingRules());

        $room = Room::findOrFail($validated['room_id']);

        if ($room->status !== 'available') {
            return back()->withErrors(['room_id' => 'This room is not available for booking.']);
        }

        if ($this->hasConflict($validated)) {
            return back()->withErrors(['start_time' => 'This time slot is already booked. Please choose a different time.']);
        }

        $duration = $this->calcDuration($validated['start_time'], $validated['end_time']);
        $cost     = round($room->hourly_rate * $duration, 2);

        RoomBooking::create([
            'room_id'             => $validated['room_id'],
            'user_id'             => $request->user()->id,
            'booking_date'        => $validated['booking_date'],
            'start_time'          => $validated['start_time'],
            'end_time'            => $validated['end_time'],
            'status'              => 'pending',
            'purpose'             => $validated['purpose'] ?? null,
            'number_of_attendees' => $validated['number_of_attendees'] ?? null,
            'special_requests'    => $validated['special_requests'] ?? null,
            'total_cost'          => $cost,
            'library_id'          => $room->library_id,
        ]);

        return redirect()->route('member.room-bookings.index')
            ->with('success', 'Booking requested successfully! It will be reviewed by our staff.');
    }
}
