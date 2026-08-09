<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreRoomBookingRequest;
use App\Http\Resources\RoomBookingResource;
use App\Models\Library;
use App\Models\Room;
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

    public function index(Request $request)
    {
        $bookings = $this->repo->forMember($request->user());

        return Inertia::render('members/RoomBookings/Index', [
            'bookings' => RoomBookingResource::collection($bookings)->resolve(),
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
                ->selectRaw('(6371 * acos(cos(radians(?)) * cos(radians(libraries.latitude)) * cos(radians(libraries.longitude) - radians(?)) + sin(radians(?)) * sin(radians(libraries.latitude)))) AS distance', [$lat, $lng, $lat])
                ->orderBy('distance');
        }

        $rooms = $roomsQuery->get()->map(fn ($r) => array_merge($r->toArray(), [
            'image_url' => $r->image ? asset('storage/' . $r->image) : null,
        ]));

        return Inertia::render('members/RoomBookings/Create', [
            'rooms'             => $rooms,
            'libraries'         => Library::orderBy('name')->get(['id', 'name']),
            'existingBookings'  => RoomBookingResource::collection($this->repo->pendingBookingsForCalendar()),
            'preselectedRoomId' => $request->integer('room_id') ?: null,
            'preselectedDate'   => $request->string('date')->toString() ?: null,
            'currentUser'       => $request->user()->only(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreRoomBookingRequest $request)
    {
        try {
            $this->bookingService->createBooking($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['start_time' => $e->getMessage()]);
        }

        return redirect()->route('member.room-bookings.index')
            ->with('success', 'Booking requested successfully! It will be reviewed by our staff.');
    }
}
