<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    /**
     * Display a listing of admin/staff users.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('view users')) {
            abort(403, 'Unauthorized');
        }

        // Only get users with admin, super-admin, or librarian roles
        $query = User::with(['roles', 'staff'])
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['super-admin', 'admin', 'librarian']);
            });

        // Search by name, email, or employee ID
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhereHas('staff', function ($staffQuery) use ($request) {
                      $staffQuery->where('employee_id', 'like', "%{$request->search}%");
                  });
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->role($request->role);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by department
        if ($request->has('department') && $request->department !== 'all') {
            $query->whereHas('staff', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        $query->orderBy('created_at', 'desc');

        $admins = $query->paginate(15)->withQueryString();

        // Get unique departments for filter
        $departments = Staff::select('department')
            ->distinct()
            ->whereNotNull('department')
            ->pluck('department')
            ->toArray();

        return Inertia::render('Admins/Index', [
            'admins' => $admins,
            'departments' => $departments,
            'filters' => $request->only(['search', 'role', 'status', 'department']),
            'can' => [
                'createUsers' => $request->user()->can('create users'),
                'editUsers' => $request->user()->can('edit users'),
                'deleteUsers' => $request->user()->can('delete users'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new admin/staff user.
     */
    public function create(Request $request)
    {
        if (!$request->user()->can('create users')) {
            abort(403, 'Unauthorized');
        }

        // Only staff roles
        $roles = Role::whereIn('name', ['super-admin', 'admin', 'librarian'])->get();

        // Get unique departments for suggestions
        $departments = Staff::select('department')
            ->distinct()
            ->whereNotNull('department')
            ->pluck('department')
            ->toArray();

        return Inertia::render('Admins/Create', [
            'roles' => $roles,
            'departments' => $departments,
        ]);
    }

    /**
     * Store a newly created admin/staff user.
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('create users')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            // User fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'role' => ['required', 'in:super-admin,admin,librarian'],
            
            // Staff profile fields
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:staff'],
            'hire_date' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'work_hours' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
                'email_verified_at' => now(),
            ]);

            // Assign role
            $user->assignRole($validated['role']);

            // Create staff profile
            $user->staff()->create([
                'employee_id' => $validated['employee_id'] ?? null,
                'hire_date' => $validated['hire_date'] ?? now(),
                'department' => $validated['department'] ?? null,
                'position' => $validated['position'] ?? null,
                'work_hours' => $validated['work_hours'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('admins.show', $user->id)
                ->with('success', 'Staff account created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create staff account: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified admin/staff user.
     */
    public function show(Request $request, User $user)
    {
        if (!$request->user()->can('view users')) {
            abort(403, 'Unauthorized');
        }

        // Ensure user is staff
        if (!$user->isStaff()) {
            abort(404);
        }

        $user->load(['roles', 'staff', 'staff.issuedLoans', 'staff.returnedLoans']);

        return Inertia::render('Admins/Show', [
            'user' => $user,
            'staffProfile' => $user->staff,
            'stats' => [
                'years_of_service' => $user->staff?->years_of_service ?? 0,
                'months_of_service' => $user->staff?->months_of_service ?? 0,
                'service_duration' => $user->staff?->service_duration ?? 'N/A',
                'total_loans_processed' => $user->staff?->total_loans_processed ?? 0,
                'total_returns_processed' => $user->staff?->total_returns_processed ?? 0,
            ],
            'can' => [
                'editUsers' => $request->user()->can('edit users'),
                'deleteUsers' => $request->user()->can('delete users'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified admin/staff user.
     */
    public function edit(Request $request, User $user)
    {
        if (!$request->user()->can('edit users')) {
            abort(403, 'Unauthorized');
        }

        // Ensure user is staff
        if (!$user->isStaff()) {
            abort(404);
        }

        $user->load(['roles', 'staff']);
        $roles = Role::whereIn('name', ['super-admin', 'admin', 'librarian'])->get();

        // Get unique departments for suggestions
        $departments = Staff::select('department')
            ->distinct()
            ->whereNotNull('department')
            ->pluck('department')
            ->toArray();

        return Inertia::render('Admins/Edit', [
            'user' => $user,
            'staffProfile' => $user->staff,
            'roles' => $roles,
            'departments' => $departments,
        ]);
    }

    /**
     * Update the specified admin/staff user.
     */
    public function update(Request $request, User $user)
    {
        if (!$request->user()->can('edit users')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            // User fields
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'role' => ['required', 'in:super-admin,admin,librarian'],
            
            // Staff profile fields
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:staff,employee_id,' . ($user->staff->id ?? 'NULL')],
            'hire_date' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'work_hours' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();

        try {
            // Update user
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
            ]);

            // Update password if provided
            if (!empty($validated['password'])) {
                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);
            }

            // Update role
            $user->syncRoles([$validated['role']]);

            // Update or create staff profile
            if ($user->staff) {
                $user->staff->update([
                    'employee_id' => $validated['employee_id'] ?? $user->staff->employee_id,
                    'hire_date' => $validated['hire_date'] ?? $user->staff->hire_date,
                    'department' => $validated['department'] ?? null,
                    'position' => $validated['position'] ?? null,
                    'work_hours' => $validated['work_hours'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            } else {
                $user->staff()->create([
                    'employee_id' => $validated['employee_id'] ?? null,
                    'hire_date' => $validated['hire_date'] ?? now(),
                    'department' => $validated['department'] ?? null,
                    'position' => $validated['position'] ?? null,
                    'work_hours' => $validated['work_hours'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('admins.show', $user->id)
                ->with('success', 'Staff account updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update staff account: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified admin/staff user.
     */
    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->can('delete users')) {
            abort(403, 'Unauthorized');
        }

        // Prevent deleting yourself
        if ($request->user()->id === $user->id) {
            return redirect()->back()
                ->with('error', 'You cannot delete yourself.');
        }

        // Ensure user is staff
        if (!$user->isStaff()) {
            abort(404);
        }

        // Check if staff has processed any loans
        $processedLoans = $user->staff?->total_loans_processed ?? 0;
        
        if ($processedLoans > 0) {
            return redirect()->back()
                ->with('error', "Cannot delete staff member who has processed {$processedLoans} loans. Consider deactivating instead.");
        }

        $user->delete();

        return redirect()->route('admins.index')
            ->with('success', 'Staff account deleted successfully!');
    }

    /**
     * Get staff statistics.
     */
    public function statistics(Request $request)
    {
        if (!$request->user()->can('view users')) {
            abort(403, 'Unauthorized');
        }

        $stats = [
            'total_staff' => User::staff()->count(),
            'active_staff' => User::staff()->where('status', 'active')->count(),
            'by_role' => [
                'super_admins' => User::role('super-admin')->count(),
                'admins' => User::role('admin')->count(),
                'librarians' => User::role('librarian')->count(),
            ],
            'by_department' => Staff::select('department', DB::raw('count(*) as count'))
                ->whereNotNull('department')
                ->groupBy('department')
                ->get()
                ->pluck('count', 'department')
                ->toArray(),
            'recent_hires' => Staff::with('user')
                ->whereHas('user', function ($q) {
                    $q->where('status', 'active');
                })
                ->orderBy('hire_date', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }
}