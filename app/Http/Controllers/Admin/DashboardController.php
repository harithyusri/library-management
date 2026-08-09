<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Book;
use App\Models\Library;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\Scopes\LibraryScope;
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
        $isSuperAdmin = $user->hasRole('Super Admin');

        // ── Library scoping ─────────────────────────────────────
        // Super admin: can filter by a specific library via ?library_id=
        // Regular staff: LibraryScope global scope handles it automatically
        $selectedLibraryId = null;
        $libraries = [];

        if ($isSuperAdmin) {
            $libraries = Library::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $selectedLibraryId = $request->integer('library_id') ?: null;
        }

        // Helper: apply library filter for super admin queries (bypasses global scope)
        $scope = function ($query) use ($isSuperAdmin, $selectedLibraryId) {
            if ($isSuperAdmin && $selectedLibraryId) {
                $query->withoutGlobalScope(LibraryScope::class)
                      ->where('library_id', $selectedLibraryId);
            }
            return $query;
        };

        $announcements = Announcement::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->latest()
            ->limit(3)
            ->get();

        $stats = [
            'total_books'            => $scope(Book::query())->count(),
            'total_members'          => Member::count(), // members are not library-scoped
            'active_loans'           => $scope(Loan::where('status', Loan::STATUS_ACTIVE))->count(),
            'available_rooms'        => $scope(Room::where('status', 'available'))->count(),
            'total_rooms'            => $scope(Room::query())->count(),
            'overdue_loans'          => $scope(Loan::where('status', Loan::STATUS_OVERDUE))->count(),
            'bookings_today'         => $scope(RoomBooking::where('booking_date', $today)
                                            ->whereIn('status', ['confirmed', 'pending']))->count(),
            'returned_this_month'    => $scope(Loan::where('status', Loan::STATUS_RETURNED)
                                            ->whereMonth('returned_date', $now->month)
                                            ->whereYear('returned_date', $now->year))->count(),
            'new_members_this_month' => Member::whereMonth('created_at', $now->month)
                                            ->whereYear('created_at', $now->year)
                                            ->count(),
        ];

        // ── Monthly trends (last 7 months) ─────────────────────
        $months     = collect(range(6, 0))->map(fn ($i) => $now->copy()->subMonths($i));
        $loanTrends = $months->map(function (Carbon $m) use ($scope) {
            return [
                'month'    => $m->format('M'),
                'loans'    => $scope(Loan::whereMonth('borrowed_date', $m->month)
                                 ->whereYear('borrowed_date', $m->year))->count(),
                'returned' => $scope(Loan::where('status', Loan::STATUS_RETURNED)
                                 ->whereMonth('returned_date', $m->month)
                                 ->whereYear('returned_date', $m->year))->count(),
            ];
        })->values();

        $bookingTrends = $months->map(function (Carbon $m) use ($scope) {
            return [
                'month'     => $m->format('M'),
                'bookings'  => $scope(RoomBooking::whereMonth('booking_date', $m->month)->whereYear('booking_date', $m->year))->count(),
                'confirmed' => $scope(RoomBooking::whereMonth('booking_date', $m->month)->whereYear('booking_date', $m->year)->where('status', 'confirmed'))->count(),
                'cancelled' => $scope(RoomBooking::whereMonth('booking_date', $m->month)->whereYear('booking_date', $m->year)->where('status', 'cancelled'))->count(),
            ];
        })->values();

        // ── Status breakdowns ───────────────────────────────────
        $loanBreakdown = [
            'active'   => $scope(Loan::where('status', Loan::STATUS_ACTIVE))->count(),
            'overdue'  => $scope(Loan::where('status', Loan::STATUS_OVERDUE))->count(),
            'returned' => $scope(Loan::where('status', Loan::STATUS_RETURNED))->count(),
        ];

        $bookingBreakdown = [
            'confirmed' => $scope(RoomBooking::where('status', 'confirmed'))->count(),
            'pending'   => $scope(RoomBooking::where('status', 'pending'))->count(),
            'cancelled' => $scope(RoomBooking::where('status', 'cancelled'))->count(),
            'completed' => $scope(RoomBooking::where('status', 'completed'))->count(),
        ];

        // ── Top borrowed books (top 5) ──────────────────────────
        $topBooks = $scope(Loan::selectRaw('book_copy_id, count(*) as loan_count')
            ->groupBy('book_copy_id')
            ->orderByDesc('loan_count')
            ->limit(5))
            ->with('bookCopy.book:id,title,author')
            ->get()
            ->map(fn ($loan) => [
                'title'  => $loan->bookCopy?->book?->title ?? 'Unknown',
                'author' => $loan->bookCopy?->book?->author ?? '—',
                'count'  => $loan->loan_count,
            ]);

        // ── Room utilization this month ─────────────────────────
        $roomUtilization = $scope(Room::query())
            ->with(['bookings' => function ($q) use ($now) {
                $q->whereMonth('booking_date', $now->month)
                  ->whereYear('booking_date', $now->year)
                  ->whereIn('status', ['confirmed', 'completed']);
            }])->get()->map(function (Room $room) use ($now) {
                $bookings    = $room->bookings->count();
                $daysInMonth = $now->daysInMonth;
                $utilization = min(100, (int) round(($bookings / max($daysInMonth, 1)) * 100));
                return [
                    'name'        => $room->name,
                    'room_number' => $room->room_number,
                    'bookings'    => $bookings,
                    'utilization' => $utilization,
                ];
            })->sortByDesc('bookings')->values();

        // ── Recent activity (last 30 days) ──────────────────────
        $thirtyDaysAgo = now()->subDays(30);

        $recentLoans = $scope(Loan::with(['bookCopy.book:id,title', 'user:id,name'])
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->latest()
            ->limit(20))
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->id,
                'type'       => 'loan',
                'title'      => $l->bookCopy?->book?->title ?? 'Unknown Book',
                'user'       => $l->user?->name ?? 'Unknown',
                'created_at' => $l->created_at,
                'date'       => $l->created_at->diffForHumans(),
                'status'     => $l->status,
            ]);

        $recentBookings = $scope(RoomBooking::with(['room:id,name', 'user:id,name'])
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->latest()
            ->limit(20))
            ->get()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'type'       => 'room_booking',
                'title'      => $b->room?->name ?? 'Unknown Room',
                'user'       => $b->user?->name ?? 'Unknown',
                'created_at' => $b->created_at,
                'date'       => $b->created_at->diffForHumans(),
                'status'     => $b->status,
            ]);

        $recentActivities = $recentLoans
            ->merge($recentBookings)
            ->sortByDesc('created_at')
            ->take(30)
            ->map(function ($a) {
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
            'libraries'                 => $libraries,
            'selected_library_id'       => $selectedLibraryId,
            'is_super_admin'            => $isSuperAdmin,
        ]);
    }
}
