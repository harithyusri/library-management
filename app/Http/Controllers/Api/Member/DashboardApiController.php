<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Loan;
use App\Models\Room;
use App\Support\ApiFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = Carbon::now();

        $announcements = Announcement::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($a) => ApiFormatter::announcement($a));

        $recentLoans = $user->loans()
            ->with(['bookCopy.book:id,title'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'type' => 'loan',
                'title' => $l->bookCopy?->book?->title ?? 'Unknown Book',
                'date' => $l->created_at->diffForHumans(),
                'status' => $l->status,
            ]);

        $recentBookings = $user->roomBookings()
            ->with(['room:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'type' => 'room_booking',
                'title' => $b->room?->name ?? 'Unknown Room',
                'date' => $b->created_at->diffForHumans(),
                'status' => $b->status,
            ]);

        return response()->json([
            'stats' => [
                'active_loans' => $user->activeLoans()->count(),
                'overdue_loans' => $user->overdueLoans()->count(),
                'available_rooms' => Room::where('status', 'available')->count(),
                'total_fines' => $user->getTotalUnpaidFines(),
                'completed_loans' => $user->loans()->where('status', Loan::STATUS_RETURNED)->count(),
                'next_due_date' => $user->activeLoans()->orderBy('due_date')->first()?->due_date?->toDateString(),
            ],
            'recent_activities' => $recentLoans->merge($recentBookings)->values(),
            'announcements' => $announcements,
        ]);
    }
}
