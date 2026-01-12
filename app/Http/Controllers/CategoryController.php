<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
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
        return Inertia::render('Categories/Index', [
            'categories' => Category::query()
                ->latest()
                ->get()
                ->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'code' => $category->code,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'created_at' => $category->created_at->format('M d, Y'),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'code' => 'required|string|max:50|unique:categories,code',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        return back()->with([
                'success' => 'Category updated successfully!',
                 'created_category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'code' => 'required|string|max:50|unique:categories,code,' . $category->id,
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully!');

    }
}
