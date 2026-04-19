<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Loan;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $now   = Carbon::now();

        $announcements = Announcement::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->latest()
            ->limit(3)
            ->get();

        $stats = [
            'active_loans'      => $user->activeLoans()->count(),
            'overdue_loans'     => $user->overdueLoans()->count(),
            'available_rooms'   => Room::where('status', 'available')->count(),
            'total_fines'       => $user->getTotalUnpaidFines(),
            'completed_loans'   => $user->loans()->where('status', Loan::STATUS_RETURNED)->count(),
            'next_due_date'     => $user->activeLoans()->orderBy('due_date')->first()?->due_date,
        ];

        // Member-specific activities
        $recentLoans = $user->loans()
            ->with(['bookCopy.book:id,title'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->id,
                'type'       => 'loan',
                'title'      => $l->bookCopy?->book?->title ?? 'Unknown Book',
                'user'       => $user->name,
                'date'       => $l->created_at->diffForHumans(),
                'status'     => $l->status,
            ]);

        $recentBookings = $user->roomBookings()
            ->with(['room:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'type'       => 'room_booking',
                'title'      => $b->room?->name ?? 'Unknown Room',
                'user'       => $user->name,
                'date'       => $b->created_at->diffForHumans(),
                'status'     => $b->status,
            ]);

        $recentActivities = $recentLoans->merge($recentBookings)->values();

        return Inertia::render('members/Dashboard', [
            'is_member'         => true,
            'user'              => $user,
            'stats'             => $stats,
            'recent_activities' => $recentActivities,
            'announcements'     => $announcements,
        ]);
    }
}
