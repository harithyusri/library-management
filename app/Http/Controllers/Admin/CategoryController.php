<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Category;
use App\Models\Library;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(): Response
    {
        return Inertia::render('admins/Categories/Index', [
            'categories' => Category::query()
                ->with('library:id,name')
                ->latest()
                ->get()
                ->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'library_id' => $category->library_id,
                    'library_name' => $category->library?->name,
                    'created_at' => $category->created_at->format('M d, Y'),
                ]),
            'libraries' => Library::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'library_id' => 'nullable|exists:libraries,id',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories')->where('library_id', $request->library_id),
            ],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('categories')->where('library_id', $request->library_id),
            ],
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        return back()->with([
                'success' => 'Category created successfully!',
                 'created_category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'library_id' => 'nullable|exists:libraries,id',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories')->where('library_id', $request->library_id)->ignore($category->id),
            ],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('categories')->where('library_id', $request->library_id)->ignore($category->id),
            ],
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully!');

    }
}
