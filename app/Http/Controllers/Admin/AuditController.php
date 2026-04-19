<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use OwenIt\Auditing\Models\Audit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AuditController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('view audits');

        $query = Audit::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by event
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        // Filter by auditable type
        if ($request->has('auditable_type')) {
            $query->where('auditable_type', 'like', '%' . $request->auditable_type . '%');
        }

        $audits = $query->paginate(20)->withQueryString();

        return Inertia::render('admins/AuditLogs/Index', [
            'audits' => $audits,
            'filters' => $request->only(['user_id', 'event', 'auditable_type']),
            'events' => Audit::select('event')->distinct()->pluck('event'),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Display a specific audit log.
     */
    public function show(Audit $audit): Response
    {
        Gate::authorize('view audits');
        
        $audit->load('user');

        return Inertia::render('admins/AuditLogs/Show', [
            'audit' => $audit,
        ]);
    }
}
