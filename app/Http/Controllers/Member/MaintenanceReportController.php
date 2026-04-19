<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $reports = MaintenanceReport::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('members/Maintenance/Index', [
            'reports' => $reports,
            'categories' => $this->getCategories(),
            'statuses' => $this->getStatuses()
        ]);
    }

    private function getCategories()
    {
        return [
            MaintenanceReport::CATEGORY_BUILDING,
            MaintenanceReport::CATEGORY_FURNITURE,
            MaintenanceReport::CATEGORY_BOOKS,
            MaintenanceReport::CATEGORY_ELECTRONICS,
            MaintenanceReport::CATEGORY_OTHERS,
        ];
    }

    private function getStatuses()
    {
        return [
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'rejected' => 'Rejected',
        ];
    }

    private function getPriorities()
    {
        return [
            MaintenanceReport::PRIORITY_LOW => 'Low',
            MaintenanceReport::PRIORITY_MEDIUM => 'Medium',
            MaintenanceReport::PRIORITY_HIGH => 'High',
        ];
    }

    public function create()
    {
        return Inertia::render('members/Maintenance/Create', [
            'categories' => $this->getCategories(),
            'priorities' => $this->getPriorities(),
            'libraries' => \App\Models\Library::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'required|string',
            'priority' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'library_id' => 'required|exists:libraries,id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('maintenance_reports', 'public');
        }

        MaintenanceReport::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'image_path' => $imagePath,
            'status' => MaintenanceReport::STATUS_PENDING,
            'library_id' => $validated['library_id'],
        ]);

        return redirect()->route('member.maintenance.index')
            ->with('success', 'Thank you for your report! We will look into it shortly.');
    }
}
