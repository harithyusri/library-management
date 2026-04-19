<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Carbon\Carbon;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of active announcements for members.
     */
    public function index()
    {
        $announcements = Announcement::with('creator:id,name')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->latest()
            ->paginate(12);

        return Inertia::render('members/Announcements/Index', [
            'announcements' => $announcements
        ]);
    }

    /**
     * Display the specified announcement.
     */
    public function show(Announcement $announcement)
    {
        // Security: Ensure member can only see active/not expired announcements
        if (!$announcement->is_active || ($announcement->expires_at && $announcement->expires_at->isPast())) {
            abort(404);
        }

        $announcement->load('creator:id,name');

        return Inertia::render('members/Announcements/Show', [
            'announcement' => $announcement
        ]);
    }
}
