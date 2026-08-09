<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $reservations = Reservation::with(['book', 'bookCopy'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('members/Reservations/Index', [
            'reservations' => $reservations,
        ]);
    }

    public function store(Request $request, Book $book)
    {
        $user = $request->user();

        // Prevent duplicate active reservation
        $exists = Reservation::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->whereIn('status', ['pending', 'ready'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'You already have an active reservation for this book.');
        }

        Reservation::create([
            'user_id'    => $user->id,
            'book_id'    => $book->id,
            'library_id' => $book->library_id,
        ]);

        return back()->with('success', "You've been added to the waitlist for \"{$book->title}\".");
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        abort_if($reservation->user_id !== $request->user()->id, 403);

        if (!$reservation->isActive()) {
            return back()->with('error', 'This reservation cannot be cancelled.');
        }

        $reservation->cancel();

        return back()->with('success', 'Reservation cancelled.');
    }
}
