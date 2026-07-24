<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

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

        // Only get users with roles of type 'staff'
        $query = User::with(['roles', 'staff.department', 'staff.library'])
            ->whereHas('roles', function ($q) {
                $q->where('type', 'staff');
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
        if ($request->has('department_id') && $request->department_id !== 'all') {
            $query->whereHas('staff', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        // Filter by library
        if ($request->has('library_id') && $request->library_id !== 'all') {
            $query->whereHas('staff', function ($q) use ($request) {
                $q->where('library_id', $request->library_id);
            });
        }

        $query->orderBy('created_at', 'desc');

        $staffs = $query->paginate(15)->withQueryString();

        // Get all departments for filter
        $departments = \App\Models\Department::orderBy('name')->get();
        $libraries = \App\Models\Library::orderBy('name')->get();

        return Inertia::render('admins/Staffs/Index', [
            'staffs' => $staffs,
            'departments' => $departments,
            'libraries' => $libraries,
            'filters' => $request->only(['search', 'role', 'status', 'library_id']),
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
        $roles = Role::where('type', 'staff')->get();

        // Get all departments for dropdown (without library scope for Super Admin)
        $departments = \App\Models\Department::withoutGlobalScope(\App\Models\Scopes\LibraryScope::class)
            ->orderBy('name')->get(['id', 'name', 'library_id']);
        $libraries = \App\Models\Library::orderBy('name')->get();

        return Inertia::render('admins/Staffs/Create', [
            'roles' => $roles,
            'departments' => $departments,
            'libraries' => $libraries,
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
            'role' => [
                'required', 
                'string',
                function ($attribute, $value, $fail) {
                    if (!\Spatie\Permission\Models\Role::where('name', $value)->where('type', 'staff')->exists()) {
                        $fail('The selected role is invalid or not a staff role.');
                    }
                }
            ],
            
            // Staff profile fields
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:staff'],
            'hire_date' => ['nullable', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'library_id' => ['required', 'exists:libraries,id'],
            'position' => ['nullable', 'string', 'max:100'],
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
                'department_id' => $validated['department_id'] ?? null,
                'library_id' => $validated['library_id'],
                'position' => $validated['position'] ?? null,
                'work_hours' => $validated['work_hours'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('admin.staffs.show', $user->id)
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

        $user->load(['roles', 'staff.department', 'staff.issuedLoans', 'staff.returnedLoans']);

        return Inertia::render('admins/Staffs/Show', [
            'admin' => $user,
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

        $user->load(['roles', 'staff.department', 'staff.library']);
        $roles = \Spatie\Permission\Models\Role::where('type', 'staff')->get();
        $departments = \App\Models\Department::withoutGlobalScope(\App\Models\Scopes\LibraryScope::class)
            ->orderBy('name')->get(['id', 'name', 'library_id']);
        $libraries = \App\Models\Library::orderBy('name')->get();

        return Inertia::render('admins/Staffs/Edit', [
            'staff' => $user,
            'staffProfile' => $user->staff,
            'roles' => $roles,
            'departments' => $departments,
            'libraries' => $libraries,
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
            'role' => [
                'required', 
                'string',
                function ($attribute, $value, $fail) {
                    if (!\Spatie\Permission\Models\Role::where('name', $value)->where('type', 'staff')->exists()) {
                        $fail('The selected role is invalid or not a staff role.');
                    }
                }
            ],
            
            // Staff profile fields
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:staff,employee_id,' . ($user->staff->id ?? 'NULL')],
            'hire_date' => ['nullable', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'library_id' => ['required', 'exists:libraries,id'],
            'position' => ['nullable', 'string', 'max:100'],
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
                    'department_id' => $validated['department_id'] ?? null,
                    'library_id' => $validated['library_id'],
                    'position' => $validated['position'] ?? null,
                ]);
            } else {
                $user->staff()->create([
                    'employee_id' => $validated['employee_id'] ?? null,
                    'hire_date' => $validated['hire_date'] ?? now(),
                    'department_id' => $validated['department_id'] ?? null,
                    'position' => $validated['position'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.staffs.show', $user->id)
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

        return redirect()->route('staffs.index')
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
            'by_department' => \App\Models\Department::withCount('staff')
                ->get()
                ->pluck('staff_count', 'name')
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