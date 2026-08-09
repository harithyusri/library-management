<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Models\MaintenanceReport;
use App\Models\Scopes\LibraryScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MaintenanceReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');

        $libraries = [];
        $selectedLibraryId = null;

        if ($isSuperAdmin) {
            $libraries = Library::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $selectedLibraryId = $request->integer('library_id') ?: null;
        }

        $reports = MaintenanceReport::with('library:id,name', 'user:id,name')
            ->when($isSuperAdmin && $selectedLibraryId, fn ($q) =>
                $q->withoutGlobalScope(LibraryScope::class)->where('library_id', $selectedLibraryId)
            )
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->category, fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admins/Maintenance/Index', [
            'reports'             => $reports,
            'filters'             => $request->only(['search', 'status', 'category']),
            'categories'          => $this->getCategories(),
            'statuses'            => $this->getStatuses(),
            'priorities'          => $this->getPriorities(),
            'libraries'           => $libraries,
            'selected_library_id' => $selectedLibraryId,
            'is_super_admin'      => $isSuperAdmin,
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
            'pending'     => 'Pending',
            'assigned'    => 'Assigned',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
            'rejected'    => 'Rejected',
        ];
    }

    private function getPriorities()
    {
        return [
            MaintenanceReport::PRIORITY_LOW    => 'Low',
            MaintenanceReport::PRIORITY_MEDIUM => 'Medium',
            MaintenanceReport::PRIORITY_HIGH   => 'High',
        ];
    }

    public function update(Request $request, MaintenanceReport $maintenanceReport)
    {
        $validated = $request->validate([
            'status'      => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        $maintenanceReport->update([
            'status'      => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
        ]);

        return back()->with('success', 'Report updated successfully.');
    }

    public function destroy(Request $request, MaintenanceReport $maintenanceReport)
    {
        if ($maintenanceReport->image_path) {
            Storage::disk('public')->delete($maintenanceReport->image_path);
        }

        $maintenanceReport->delete();

        return back()->with('success', 'Report deleted.');
    }
}
