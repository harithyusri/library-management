<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Library;

class MaintenanceReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = MaintenanceReport::with('library:id,name', 'user:id,name')
    
        ->when($request->library_id, fn($q, $l) => $q->where('library_id', $l))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admins/Maintenance/Index', [
            'reports' => $reports,
            'priorities' => $this->getPriorities(),
            'libraries' => Library::orderBy('name')->get(['id', 'name']),
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

    public function update(Request $request, MaintenanceReport $maintenanceReport)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        $maintenanceReport->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
        ]);

        return back()->with('success', 'Report updated successfully.');
    }

    public function destroy(Request $request, MaintenanceReport $maintenanceReport)
    {
        $maintenanceReport->delete();

        return back()->with('success', 'Report deleted.');
    }
}
