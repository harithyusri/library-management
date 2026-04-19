<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        // if (!$request->user()->can('manage roles')) {
        //     abort(403, 'Unauthorized');
        // }

        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('id')
            ->get();

        return Inertia::render('admins/Roles/Index', [
            'roles' => $roles,
            'can' => [
                'manageRoles' => $request->user()->can('manage roles'),
                'managePermissions' => $request->user()->can('manage permissions'),
            ],
        ]);
    }

    /**
     * Display the specified role.
     */
    public function show(Request $request, Role $role)
    {
        // if (!$request->user()->can('manage roles')) {
        //     abort(403, 'Unauthorized');
        // }

        $role->load(['permissions', 'users']);

        return Inertia::render('admins/Roles/Show', [
            'role' => $role,
            'can' => [
                'manageRoles' => $request->user()->can('manage roles'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        // if (!$request->user()->can('manage roles')) {
        //     abort(403, 'Unauthorized');
        // }

        $allPermissions = $this->groupPermissionsByCategory();

        return Inertia::render('admins/Roles/Create', [
            'allPermissions' => $allPermissions,
        ]);
    }

    public function store(Request $request)
    {
        // if (!$request->user()->can('manage roles')) {
        //     abort(403, 'Unauthorized');
        // }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'type' => ['required', 'string', 'in:staff,member'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'guard_name' => 'web'
        ]);
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully!');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Request $request, Role $role)
    {
        // if (!$request->user()->can('manage roles')) {
        //     abort(403, 'Unauthorized');
        // }

        $role->load('permissions');

        // Group permissions by category
        $allPermissions = $this->groupPermissionsByCategory();

        return Inertia::render('admins/Roles/Edit', [
            'role' => $role,
            'allPermissions' => $allPermissions,
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        if (!$request->user()->can('manage roles')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'type' => ['required', 'string', 'in:staff,member'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // Update role name and type
        $role->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);
        $role->syncPermissions($validated['permissions']);

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('roles.show', $role->id)
            ->with('success', 'Role permissions updated successfully!');
    }

    /**
     * Group permissions by category.
     */
    private function groupPermissionsByCategory(): array
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            // Remove the first word (the action: view/create/edit/delete/etc.)
            // Everything after is the category, e.g. "book copies", "room bookings"
            $parts = explode(' ', $permission->name, 2); // limit to 2 parts only

            $category = count($parts) >= 2 ? $parts[1] : 'other';

            // Normalize to snake_case key for consistent grouping
            $key = str_replace(' ', '_', $category); // "book copies" -> "book_copies"

            $grouped[$key][] = $permission;
        }

        ksort($grouped);

        return $grouped;
    }
}