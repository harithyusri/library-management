<?php

namespace App\Http\Controllers;

use App\Models\Genre;
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
        return Inertia::render('Genres/Index', [
            'genres' => Genre::query()
                ->latest()
                ->get()
                ->map(fn($genre) => [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'description' => $genre->description,
                    'created_at' => $genre->created_at->format('M d, Y'),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Genre::create($validated);

        return redirect()->route('genres.index')->with('success', 'Genre created successfully!');
    }

    public function update(Request $request, Genre $genre)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $genre->update($validated);

        return redirect()->route('genres.index')->with('success', 'Genre updated successfully!');
    }

    public function destroy(Genre $genre)
    {
        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'Genre deleted successfully!');
    }
}
