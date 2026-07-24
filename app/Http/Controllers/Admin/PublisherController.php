<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Library;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublisherController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(): Response
    {
        return Inertia::render('admins/Publishers/Index', [
            'publishers' => Publisher::query()
                ->with('library:id,name')
                ->latest()
                ->get()
                ->map(fn($publisher) => [
                    'id' => $publisher->id,
                    'name' => $publisher->name,
                    'description' => $publisher->description,
                    'library_id' => $publisher->library_id,
                    'library_name' => $publisher->library?->name,
                    'created_at' => $publisher->created_at->format('M d, Y'),
                ]),
            'libraries' => Library::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'library_id' => 'nullable|exists:libraries,id',
            'name' => 'required|string|max:255|unique:publishers,name',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $publisher = Publisher::create($validated);

        return back()->with([
            'success' => 'Publisher created successfully!',
            'created_publisher' => $publisher,
        ]);
    }

    public function update(Request $request, Publisher $publisher)
    {
        $validated = $request->validate([
            'library_id' => 'nullable|exists:libraries,id',
            'name' => 'required|string|max:255|unique:publishers,name,' . $publisher->id,
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $publisher->update($validated);

        return redirect()->route('admin.publishers.index')->with('success', 'Publisher updated successfully!');
    }

    public function destroy(Publisher $publisher)
    {
        $publisher->delete();

        return redirect()->route('admin.publishers.index')->with('success', 'Publisher deleted successfully!');
    }
}
