<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Models\MaintenanceReport;
use App\Support\ApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reports = MaintenanceReport::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => collect($reports->items())->map(fn ($r) => ApiFormatter::maintenanceReport($r))->values(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
            'categories' => ApiFormatter::maintenanceCategories(),
            'statuses' => ApiFormatter::maintenanceStatuses(),
        ]);
    }

    public function createData(): JsonResponse
    {
        return response()->json([
            'categories' => ApiFormatter::maintenanceCategories(),
            'priorities' => ApiFormatter::maintenancePriorities(),
            'libraries' => Library::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
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

        $report = MaintenanceReport::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'image_path' => $imagePath,
            'status' => MaintenanceReport::STATUS_PENDING,
            'library_id' => $validated['library_id'],
        ]);

        return response()->json([
            'message' => 'Thank you for your report! We will look into it shortly.',
            'report' => ApiFormatter::maintenanceReport($report),
        ], 201);
    }
}
