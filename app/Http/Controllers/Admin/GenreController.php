<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Genre;
use App\Models\Library;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GenreController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(): Response
    {
        return Inertia::render('admins/Genres/Index', [
            'genres' => Genre::query()
                ->with('library:id,name')
                ->orderBy('name', 'asc')
                ->get()
                ->map(fn($genre) => [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'description' => $genre->description,
                    'library_id' => $genre->library_id,
                    'library_name' => $genre->library?->name,
                    'created_at' => $genre->created_at->format('M d, Y'),
                ]),
            'libraries' => Library::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'library_id' => 'nullable|exists:libraries,id',
            'name' => 'required|string|max:255|unique:genres,name',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $genre = Genre::create($validated);

        return back()->with([
            'success' => 'Genre created successfully!',
            'created_genre' => $genre,
        ]);
    }

    public function update(Request $request, Genre $genre)
    {
        $validated = $request->validate([
            'library_id' => 'nullable|exists:libraries,id',
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $genre->update($validated);

        return redirect()->route('admin.genres.index')->with('success', 'Genre updated successfully!');
    }

    public function destroy(Genre $genre)
    {
        $genre->delete();

        return redirect()->route('admin.genres.index')->with('success', 'Genre deleted successfully!');
    }
}
