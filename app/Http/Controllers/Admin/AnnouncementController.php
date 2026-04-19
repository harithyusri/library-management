<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index()
    {
        // For general viewing, and admins get management capability if they have the permission
        $announcements = Announcement::with('creator:id,name')
            ->latest()
            ->paginate(10);

        return Inertia::render('admins/Announcements/Index', [
            'announcements' => $announcements,
            'libraries' => \App\Models\Library::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return Inertia::render('admins/Announcements/Create', [
            'libraries' => \App\Models\Library::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|max:5120', // cover image
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'library_id' => 'nullable|exists:libraries,id', // null means global
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        $validated['created_by'] = Auth::id();

        Announcement::create($validated);

        return redirect()->route('announcements.index')->with('success', 'Announcement created successfully.');
    }

    public function show(Announcement $announcement)
    {
        $announcement->load('creator:id,name');
        return Inertia::render('admins/Announcements/Show', [
            'announcement' => $announcement
        ]);
    }

    public function edit(Announcement $announcement)
    {
        return Inertia::render('admins/Announcements/Edit', [
            'announcement' => $announcement,
            'libraries' => \App\Models\Library::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'library_id' => 'nullable|exists:libraries,id',
        ]);

        if ($request->hasFile('image')) {
            if ($announcement->image_path) {
                Storage::disk('public')->delete($announcement->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($validated);

        return redirect()->route('announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        if ($announcement->image_path) {
            Storage::disk('public')->delete($announcement->image_path);
        }
        
        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted successfully.');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $path = $request->file('image')->store('announcements/inline', 'public');

        return response()->json([
            'url' => Storage::url($path)
        ]);
    }
}
