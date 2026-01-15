<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    /**
     * Display a listing of admin/staff users.
     */
    public function index(Request $request)
    {
        // if (!$request->user()->can('view users')) {
        //     abort(403, 'Unauthorized');
        // }

        // Only get users with admin, super-admin, or librarian roles
        $query = User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['super-admin', 'admin', 'librarian']);
            });

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
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

        $query->orderBy('created_at', 'desc');

        $admins = $query->paginate(15)->withQueryString();

        return Inertia::render('Admins/Index', [
            'admins' => $admins,
            'filters' => $request->only(['search', 'role', 'status']),
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

        return Inertia::render('Admins/Create', [
            'roles' => $roles,
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'role' => ['required', 'in:super-admin,admin,librarian'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admins.show', $user->id)
            ->with('success', 'Staff account created successfully!');
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

        $user->load('roles');

        return Inertia::render('Admins/Show', [
            'user' => $user,
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

        $user->load('roles');
        $roles = Role::whereIn('name', ['super-admin', 'admin', 'librarian'])->get();

        return Inertia::render('Admins/Edit', [
            'user' => $user,
            'roles' => $roles,
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'role' => ['required', 'in:super-admin,admin,librarian'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admins.show', $user->id)
            ->with('success', 'Staff account updated successfully!');
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

        $user->delete();

        return redirect()->route('admins.index')
            ->with('success', 'Staff account deleted successfully!');
    }
}