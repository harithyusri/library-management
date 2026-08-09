<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMaintenanceReportRequest;
use App\Models\Library;
use App\Models\MaintenanceReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = MaintenanceReport::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('members/Maintenance/Index', [
            'reports'    => $reports,
            'categories' => $this->categories(),
            'statuses'   => $this->statuses(),
        ]);
    }

    public function create()
    {
        return Inertia::render('members/Maintenance/Create', [
            'categories' => $this->categories(),
            'priorities' => $this->priorities(),
            'libraries'  => Library::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreMaintenanceReportRequest $request)
    {
        $validated  = $request->validated();
        $imagePath  = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('maintenance_reports', 'public');
        }

        MaintenanceReport::create([
            'user_id'     => $request->user()->id,
            'title'       => $validated['title'],
            'category'    => $validated['category'],
            'description' => $validated['description'],
            'priority'    => $validated['priority'],
            'image_path'  => $imagePath,
            'status'      => MaintenanceReport::STATUS_PENDING,
            'library_id'  => $validated['library_id'],
        ]);

        return redirect()->route('member.maintenance.index')
            ->with('success', 'Thank you for your report! We will look into it shortly.');
    }

    private function categories(): array
    {
        return [
            MaintenanceReport::CATEGORY_BUILDING,
            MaintenanceReport::CATEGORY_FURNITURE,
            MaintenanceReport::CATEGORY_BOOKS,
            MaintenanceReport::CATEGORY_ELECTRONICS,
            MaintenanceReport::CATEGORY_OTHERS,
        ];
    }

    private function statuses(): array
    {
        return [
            'pending'     => 'Pending',
            'assigned'    => 'Assigned',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
            'rejected'    => 'Rejected',
        ];
    }

    private function priorities(): array
    {
        return [
            MaintenanceReport::PRIORITY_LOW    => 'Low',
            MaintenanceReport::PRIORITY_MEDIUM => 'Medium',
            MaintenanceReport::PRIORITY_HIGH   => 'High',
        ];
    }
}
