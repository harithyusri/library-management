<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoomBookingRequest;
use App\Http\Requests\Admin\UpdateBookingStatusRequest;
use App\Http\Requests\Admin\UpdateRoomBookingRequest;
use App\Http\Resources\RoomBookingResource;
use App\Models\Library;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Repositories\RoomBookingRepository;
use App\Services\RoomBookingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomBookingController extends Controller
{
    public function __construct(
        private RoomBookingService $bookingService,
        private RoomBookingRepository $repo,
    ) {}
    // Remove private helpers — now in RoomBookingService
    private function serializeBooking(RoomBooking $b): array
    {
        return (new RoomBookingResource($b->loadMissing(['room', 'user', 'approver'])))->resolve();
    }

    public function index(Request $request)
    {
        $user          = $request->user();
        $isSuperAdmin  = $user->hasRole('Super Admin');
        $libraries     = [];
        $selectedLibraryId = null;

        if ($isSuperAdmin) {
            $libraries         = Library::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $selectedLibraryId = $request->integer('library_id') ?: null;
        }

        $bookings = $this->repo->forAdmin($isSuperAdmin ? $selectedLibraryId : null);

        return Inertia::render('admins/RoomBookings/Index', [
            'bookings'            => RoomBookingResource::collection($bookings),
            'libraries'           => $libraries,
            'selected_library_id' => $selectedLibraryId,
            'is_super_admin'      => $isSuperAdmin,
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

    public function store(StoreRoomBookingRequest $request)
    {
        try {
            $this->bookingService->createBooking($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['start_time' => $e->getMessage()]);
        }

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

    public function update(UpdateRoomBookingRequest $request, RoomBooking $roomBooking)
    {
        try {
            $this->bookingService->updateBooking($roomBooking, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['start_time' => $e->getMessage()]);
        }

        return redirect()->route('admin.room-bookings.show', $roomBooking->id)
            ->with('success', 'Booking updated successfully!');
    }

    public function updateStatus(UpdateBookingStatusRequest $request, RoomBooking $roomBooking)
    {
        try {
            $this->bookingService->updateStatus(
                $roomBooking,
                $request->validated('status'),
                $request->user(),
                $request->validated('cancellation_reason')
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

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
