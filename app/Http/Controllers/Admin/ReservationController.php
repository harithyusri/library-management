<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookCopy;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $reservations = Reservation::with(['book', 'user', 'bookCopy'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->whereHas('book', fn ($b) =>
                $b->where('title', 'like', "%{$request->search}%")
            ))
            ->latest()
            ->paginate(15);

        return Inertia::render('admins/Reservations/Index', [
            'reservations' => $reservations,
            'filters'      => $request->only(['status', 'search']),
        ]);
    }

    public function markReady(Request $request, Reservation $reservation)
    {
        $request->validate(['book_copy_id' => 'required|exists:book_copies,id']);

        $copy = BookCopy::findOrFail($request->book_copy_id);

        if ($copy->status !== 'available') {
            return back()->with('error', 'Selected copy is not available.');
        }

        $reservation->markAsReady($copy);

        return back()->with('success', 'Reservation marked as ready. Member has been notified.');
    }

    public function cancel(Reservation $reservation)
    {
        if (!$reservation->isActive()) {
            return back()->with('error', 'This reservation cannot be cancelled.');
        }

        $reservation->cancel();

        return back()->with('success', 'Reservation cancelled.');
    }
}
