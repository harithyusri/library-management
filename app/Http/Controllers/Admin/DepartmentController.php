<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('manage roles')) { // Using manage roles permission as a proxy for settings management
             abort(403, 'Unauthorized');
        }

        $query = Department::withCount('staff');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $departments = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('admins/Departments/Index', [
            'departments' => $departments,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('manage roles')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', 'unique:departments'],
            'description' => ['nullable', 'string'],
        ]);

        $department = Department::create($validated);

        return redirect()->back()->with([
            'success' => 'Department created successfully!',
            'created_department' => $department,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        if (!$request->user()->can('manage roles')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20', 'unique:departments,code,' . $department->id],
            'description' => ['nullable', 'string'],
        ]);

        $department->update($validated);

        return redirect()->back()->with('success', 'Department updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Department $department)
    {
        if (!$request->user()->can('manage roles')) {
            abort(403, 'Unauthorized');
        }

        if ($department->staff()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete department with active staff members.');
        }

        $department->delete();

        return redirect()->back()->with('success', 'Department deleted successfully!');
    }
}
