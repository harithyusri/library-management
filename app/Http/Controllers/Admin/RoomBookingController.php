<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    private function serializeBooking(RoomBooking $b): array
    {
        return [
            'id'                   => $b->id,
            'room_id'              => $b->room_id,
            'user_id'              => $b->user_id,
            'approved_by'         => $b->approved_by,
            'booking_date'        => $b->booking_date->toDateString(),
            'start_time'          => $this->formatTime($b->start_time),
            'end_time'            => $this->formatTime($b->end_time),
            'status'              => $b->status,
            'purpose'             => $b->purpose,
            'number_of_attendees' => $b->number_of_attendees,
            'special_requests'    => $b->special_requests,
            'notes'               => $b->notes,
            'cancellation_reason' => $b->cancellation_reason,
            'cancelled_at'        => $b->cancelled_at,
            'approved_at'         => $b->approved_at,
            'duration_hours'      => $b->duration_in_hours,
            'total_cost'          => $b->total_cost,
            'created_at'          => $b->created_at,
            'room' => $b->relationLoaded('room') ? array_merge($b->room->toArray(), [
                'image_url' => $b->room->image ? asset('storage/' . $b->room->image) : null,
            ]) : null,
            'user'         => $b->relationLoaded('user') ? $b->user : null,
            'approvedBy'   => $b->relationLoaded('approvedBy') ? $b->approvedBy : null,
        ];
    }

    private function bookingRules(bool $isUpdate = false): array
    {
        return [
            'room_id'             => ['required', 'exists:rooms,id'],
            'booking_date'        => ['required', 'date', 'after_or_equal:today'],
            'start_time'          => ['required', 'date_format:H:i'],
            'end_time'            => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose'             => ['nullable', 'string', 'max:255'],
            'number_of_attendees' => ['nullable', 'integer', 'min:1', 'max:500'],
            'special_requests'    => ['nullable', 'string'],
            'notes'               => ['nullable', 'string'],
            'user_id'             => ['nullable', 'exists:users,id'],
        ];
    }

    private function hasConflict(array $validated, ?int $excludeId = null): bool
    {
        return RoomBooking::where('room_id', $validated['room_id'])
            ->whereDate('booking_date', $validated['booking_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                  ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();
    }

    public function index(Request $request)
    {
        $query = RoomBooking::with(['room:id,name,room_number,hourly_rate', 'user:id,name']);

        $bookings = $query->orderBy('booking_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($b) => [
                'id'           => $b->id,
                'room'         => $b->room,
                'user'         => $b->user,
                'booking_date' => $b->booking_date->toDateString(),
                'start_time'   => $this->formatTime($b->start_time),
                'end_time'     => $this->formatTime($b->end_time),
                'status'       => $b->status,
                'purpose'      => $b->purpose,
                'duration_hours' => $b->duration_in_hours,
                'total_cost'   => $b->total_cost ?? 0,
            ]);

        return Inertia::render('admins/RoomBookings/Index', [
            'bookings' => $bookings,
            'can' => [
                'createBookings' => $request->user()->can('create room bookings'),
                'editBookings'   => $request->user()->can('edit room bookings'),
                'deleteBookings' => $request->user()->can('delete room bookings'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $rooms = Room::where('status', 'available')
            ->select('id', 'name', 'room_number', 'type', 'capacity', 'floor', 'hourly_rate', 'status', 'description', 'image')
            ->get()
            ->map(fn ($r) => array_merge($r->toArray(), [
                'image_url' => $r->image ? asset('storage/' . $r->image) : null,
            ]));

        $existingBookings = RoomBooking::whereIn('status', ['pending', 'confirmed'])
            ->select('room_id', 'booking_date', 'start_time', 'end_time')
            ->get()
            ->map(fn ($b) => [
                'room_id'      => $b->room_id,
                'booking_date' => $b->booking_date->toDateString(),
                'start_time'   => $this->formatTime($b->start_time),
                'end_time'     => $this->formatTime($b->end_time),
            ]);

        return Inertia::render('admins/RoomBookings/Create', [
            'rooms'             => $rooms,
            'existingBookings'  => $existingBookings,
            'preselectedRoomId' => $request->integer('room_id') ?: null,
            'preselectedDate'   => $request->string('date')->toString() ?: null,
            'currentUser'       => [
                'id'    => $request->user()->id,
                'name'  => $request->user()->name,
                'email' => $request->user()->email,
                'isStaff' => $request->user()->isStaff(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->can('create room bookings')) {
            abort(403, 'Unauthorized');
        }

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

        $userId = $request->user()->id;
        if (!empty($validated['user_id'])) {
            $userId = $validated['user_id'];
        }

        RoomBooking::create([
            'room_id'             => $validated['room_id'],
            'user_id'             => $userId,
            'booking_date'        => $validated['booking_date'],
            'start_time'          => $validated['start_time'],
            'end_time'            => $validated['end_time'],
            'status'              => 'pending',
            'purpose'             => $validated['purpose'] ?? null,
            'number_of_attendees' => $validated['number_of_attendees'] ?? null,
            'special_requests'    => $validated['special_requests'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'total_cost'          => $cost,
            'library_id'          => $room->library_id,
        ]);

        return redirect()->route('admin.room-bookings.index')
            ->with('success', 'Booking created successfully!');
    }

    public function show(Request $request, RoomBooking $roomBooking)
    {
        $roomBooking->load(['room', 'user', 'approver']);

        return Inertia::render('admins/RoomBookings/Show', [
            'booking' => $this->serializeBooking($roomBooking),
            'can' => [
                'editBookings'   => $request->user()->can('edit room bookings'),
                'deleteBookings' => $request->user()->can('delete room bookings'),
            ],
        ]);
    }

    public function edit(Request $request, RoomBooking $roomBooking)
    {
        if (!$request->user()->can('edit room bookings')) {
            abort(403, 'Unauthorized');
        }

        if ($roomBooking->status !== 'pending') {
            return redirect()->route('admin.room-bookings.show', $roomBooking->id)
                ->with('error', 'Only pending bookings can be edited.');
        }

        $roomBooking->load(['room', 'user']);

        $rooms = Room::select('id', 'name', 'room_number', 'type', 'capacity', 'floor', 'hourly_rate', 'status')
            ->get()
            ->map(fn ($r) => array_merge($r->toArray(), [
                'image_url' => $r->image ? asset('storage/' . $r->image) : null,
            ]));

        $existingBookings = RoomBooking::where('id', '!=', $roomBooking->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->select('id', 'room_id', 'booking_date', 'start_time', 'end_time')
            ->get()
            ->map(fn ($b) => [
                'id'           => $b->id,
                'room_id'      => $b->room_id,
                'booking_date' => $b->booking_date->toDateString(),
                'start_time'   => $this->formatTime($b->start_time),
                'end_time'     => $this->formatTime($b->end_time),
            ]);

        return Inertia::render('admins/RoomBookings/Edit', [
            'booking'          => $this->serializeBooking($roomBooking),
            'rooms'            => $rooms,
            'existingBookings' => $existingBookings,
            'currentUser'      => [
                'id'    => $request->user()->id,
                'name'  => $request->user()->name,
                'isStaff' => $request->user()->isStaff(),
            ],
        ]);
    }

    public function update(Request $request, RoomBooking $roomBooking)
    {
        if (!$request->user()->can('edit room bookings')) {
            abort(403, 'Unauthorized');
        }

        if ($roomBooking->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending bookings can be edited.']);
        }

        $validated = $request->validate($this->bookingRules(isUpdate: true));

        if ($this->hasConflict($validated, $roomBooking->id)) {
            return back()->withErrors(['start_time' => 'This time slot is already booked. Please choose a different time.']);
        }

        $room     = Room::findOrFail($validated['room_id']);
        $duration = $this->calcDuration($validated['start_time'], $validated['end_time']);
        $cost     = round($room->hourly_rate * $duration, 2);

        $userId = $roomBooking->user_id;
        if (!empty($validated['user_id'])) {
            $userId = $validated['user_id'];
        }

        $roomBooking->update([
            'room_id'             => $validated['room_id'],
            'user_id'             => $userId,
            'booking_date'        => $validated['booking_date'],
            'start_time'          => $validated['start_time'],
            'end_time'            => $validated['end_time'],
            'purpose'             => $validated['purpose'] ?? null,
            'number_of_attendees' => $validated['number_of_attendees'] ?? null,
            'special_requests'    => $validated['special_requests'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'total_cost'          => $cost,
            'library_id'          => $room->library_id,
        ]);

        return redirect()->route('admin.room-bookings.show', $roomBooking->id)
            ->with('success', 'Booking updated successfully!');
    }

    public function updateStatus(Request $request, RoomBooking $roomBooking)
    {
        if (!$request->user()->can('edit room bookings')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'status'              => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'cancellation_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:500'],
        ]);

        $allowed = match ($roomBooking->status) {
            'pending'   => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'cancelled' => ['pending'],
            'completed' => [],
            default     => [],
        };

        if (!in_array($validated['status'], $allowed)) {
            return back()->withErrors([
                'status' => "Cannot change status from '{$roomBooking->status}' to '{$validated['status']}'.",
            ]);
        }

        $updates = ['status' => $validated['status']];

        if ($validated['status'] === 'confirmed') {
            $updates['approved_by'] = $request->user()->id;
            $updates['approved_at'] = now();
        }

        if ($validated['status'] === 'cancelled') {
            $updates['cancellation_reason'] = $validated['cancellation_reason'] ?? null;
            $updates['cancelled_at']        = now();
        }

        if ($validated['status'] === 'pending') {
            $updates['cancellation_reason'] = null;
            $updates['cancelled_at']        = null;
        }

        $roomBooking->update($updates);

        return back()->with('success', 'Booking status updated.');
    }

    public function destroy(Request $request, RoomBooking $roomBooking)
    {
        if (!$request->user()->can('delete room bookings')) {
            abort(403, 'Unauthorized');
        }

        if (
            in_array($roomBooking->status, ['confirmed', 'pending']) &&
            $roomBooking->booking_date >= now()->toDateString()
        ) {
            return back()->with('error', 'Cancel the booking before deleting it.');
        }

        $roomBooking->delete();

        return redirect()->route('admin.room-bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }
}
