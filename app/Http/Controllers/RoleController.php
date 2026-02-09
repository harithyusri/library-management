<?php

namespace App\Http\Controllers;

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
            ->orderBy('name')
            ->get();

        return Inertia::render('Roles/Index', [
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

        return Inertia::render('Roles/Show', [
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

        return Inertia::render('Roles/Create', [
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
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create(['name' => $validated['name']]);
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

        return Inertia::render('Roles/Edit', [
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
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // Sync permissions
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
            // Extract category from permission name
            // e.g., "view users" -> "users", "create books" -> "books"
            $parts = explode(' ', $permission->name);
            
            if (count($parts) >= 2) {
                $category = $parts[1]; // users, books, loans, etc.
            } else {
                $category = 'other';
            }

            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][] = $permission;
        }

        // Sort categories
        ksort($grouped);

        return $grouped;
    }
}