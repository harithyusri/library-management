<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Room;
use App\Models\RoomBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $now   = Carbon::now();
        $today = $now->toDateString();

        $announcements = Announcement::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->latest()
            ->limit(3)
            ->get();

        $stats = [
            'total_books'            => Book::count(),
            'total_members'          => Member::count(),
            'active_loans'           => Loan::where('status', Loan::STATUS_ACTIVE)->count(),
            'available_rooms'        => Room::where('status', 'available')->count(),
            'total_rooms'            => Room::count(),
            'overdue_loans'          => Loan::where('status', Loan::STATUS_OVERDUE)->count(),
            'bookings_today'         => RoomBooking::where('booking_date', $today)
                                            ->whereIn('status', ['confirmed', 'pending'])
                                            ->count(),
            'returned_this_month'    => Loan::where('status', Loan::STATUS_RETURNED)
                                            ->whereMonth('returned_date', $now->month)
                                            ->whereYear('returned_date', $now->year)
                                            ->count(),
            'new_members_this_month' => Member::whereMonth('created_at', $now->month)
                                            ->whereYear('created_at', $now->year)
                                            ->count(),
        ];

        // ── Monthly trends (last 7 months) ─────────────────────
        $months       = collect(range(6, 0))->map(fn ($i) => $now->copy()->subMonths($i));
        $loanTrends   = $months->map(function (Carbon $m) {
            return [
                'month'    => $m->format('M'),
                'loans'    => Loan::whereMonth('borrowed_date', $m->month)
                                 ->whereYear('borrowed_date', $m->year)
                                 ->count(),
                'returned' => Loan::where('status', Loan::STATUS_RETURNED)
                                 ->whereMonth('returned_date', $m->month)
                                 ->whereYear('returned_date', $m->year)
                                 ->count(),
            ];
        })->values();

        $bookingTrends = $months->map(function (Carbon $m) {
            $base = RoomBooking::whereMonth('booking_date', $m->month)->whereYear('booking_date', $m->year);
            return [
                'month'     => $m->format('M'),
                'bookings'  => (clone $base)->count(),
                'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
                'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            ];
        })->values();

        // ── Status breakdowns ───────────────────────────────────
        $loanBreakdown = [
            'active'   => Loan::where('status', Loan::STATUS_ACTIVE)->count(),
            'overdue'  => Loan::where('status', Loan::STATUS_OVERDUE)->count(),
            'returned' => Loan::where('status', Loan::STATUS_RETURNED)->count(),
        ];

        $bookingBreakdown = [
            'confirmed' => RoomBooking::where('status', 'confirmed')->count(),
            'pending'   => RoomBooking::where('status', 'pending')->count(),
            'cancelled' => RoomBooking::where('status', 'cancelled')->count(),
            'completed' => RoomBooking::where('status', 'completed')->count(),
        ];

        // ── Top borrowed books (top 5) ──────────────────────────
        $topBooks = Loan::selectRaw('book_copy_id, count(*) as loan_count')
            ->groupBy('book_copy_id')
            ->orderByDesc('loan_count')
            ->limit(5)
            ->with('bookCopy.book:id,title,author')
            ->get()
            ->map(fn ($loan) => [
                'title'  => $loan->bookCopy?->book?->title ?? 'Unknown',
                'author' => $loan->bookCopy?->book?->author ?? '—',
                'count'  => $loan->loan_count,
            ]);

        // ── Room utilization this month ─────────────────────────
        $roomUtilization = Room::with(['bookings' => function ($q) use ($now) {
            $q->whereMonth('booking_date', $now->month)
              ->whereYear('booking_date', $now->year)
              ->whereIn('status', ['confirmed', 'completed']);
        }])->get()->map(function (Room $room) use ($now) {
            $bookings  = $room->bookings->count();
            $daysInMonth = $now->daysInMonth;
            $utilization = min(100, (int) round(($bookings / max($daysInMonth, 1)) * 100));
            return [
                'name'        => $room->name,
                'room_number' => $room->room_number,
                'bookings'    => $bookings,
                'utilization' => $utilization,
            ];
        })->sortByDesc('bookings')->values();

        // ── Recent activity (last 30 days, top 30) ───────────────────────────
        $thirtyDaysAgo = now()->subDays(30);

        $recentLoans = Loan::with(['bookCopy.book:id,title', 'user:id,name'])
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->id,
                'type'       => 'loan',
                'title'      => $l->bookCopy?->book?->title ?? 'Unknown Book',
                'user'       => $l->user?->name ?? 'Unknown',
                'created_at' => $l->created_at, // keep for sorting
                'date'       => $l->created_at->diffForHumans(),
                'status'     => $l->status,
            ]);

        $recentBookings = RoomBooking::with(['room:id,name', 'user:id,name'])
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'type'       => 'room_booking',
                'title'      => $b->room?->name ?? 'Unknown Room',
                'user'       => $b->user?->name ?? 'Unknown',
                'created_at' => $b->created_at, // keep for sorting
                'date'       => $b->created_at->diffForHumans(),
                'status'     => $b->status,
            ]);

        $recentActivities = $recentLoans
            ->merge($recentBookings)
            ->sortByDesc('created_at')
            ->take(30)
            ->map(function ($a) {
                // remove raw created_at before sending to frontend
                unset($a['created_at']);
                return $a;
            })
            ->values();

        return Inertia::render('admins/Dashboard', [
            'is_member'                 => false,
            'user'                      => $user,
            'stats'                     => $stats,
            'recent_activities'         => $recentActivities,
            'loan_trends'               => $loanTrends,
            'booking_trends'            => $bookingTrends,
            'loan_status_breakdown'     => $loanBreakdown,
            'booking_status_breakdown'  => $bookingBreakdown,
            'top_books'                 => $topBooks,
            'room_utilization'          => $roomUtilization,
            'announcements'             => $announcements,
        ]);
    }
}
