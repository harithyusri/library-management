<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Support\ApiFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AnnouncementApiController extends Controller
{
    public function index(): JsonResponse
    {
        $announcements = Announcement::with('creator:id,name')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->latest()
            ->paginate(12);

        return response()->json([
            'data' => collect($announcements->items())->map(fn ($a) => ApiFormatter::announcement($a))->values(),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
            ],
        ]);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        if (! $announcement->is_active || ($announcement->expires_at && $announcement->expires_at->isPast())) {
            return response()->json(['message' => 'Announcement not found.'], 404);
        }

        return response()->json([
            'announcement' => ApiFormatter::announcement($announcement, detailed: true),
        ]);
    }
}
