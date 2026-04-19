<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Report;
use App\Jobs\GenerateReportJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Display a listing of reports.
     */
    public function index(Request $request): Response
    {
        $routeName = $request->route()->getName();
        $type = ($routeName === 'admin.loan-reports.index') ? Report::TYPE_LOAN : Report::TYPE_ROOM_RESERVATION;
        
        $reports = Report::where('type', $type)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $component = ($type === Report::TYPE_LOAN) ? 'admins/Reports/Loans/Index' : 'admins/Reports/RoomReservations/Index';

        return Inertia::render($component, [
            'reports' => $reports,
            'reportType' => $type,
        ]);
    }

    /**
     * Store a newly created report request in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:loan,room_reservation',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $report = Report::create([
            'generated_by' => auth()->id(),
            'type' => $request->type,
            'status' => Report::STATUS_PENDING,
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);

        GenerateReportJob::dispatch($report);

        return back()->with('success', 'Report generation started in the background.');
    }

    /**
     * Get the status of a specific report.
     */
    public function status(Report $report)
    {
        return response()->json([
            'id' => $report->id,
            'status' => $report->status,
            'file_path' => $report->file_path ? Storage::disk('public')->url($report->file_path) : null,
            'error_message' => $report->error_message,
        ]);
    }

    /**
     * Download the report file.
     */
    public function download(Report $report)
    {
        if ($report->status !== Report::STATUS_COMPLETED || !$report->file_path) {
            return abort(404, 'Report not ready or file missing.');
        }

        if (!Storage::disk('public')->exists($report->file_path)) {
            return abort(404, 'File not found on server.');
        }

        return Storage::disk('public')->download($report->file_path);
    }
}
