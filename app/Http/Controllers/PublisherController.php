<?php

namespace App\Http\Controllers;

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
        return Inertia::render('Publishers/Index', [
            'publishers' => Publisher::query()
                ->latest()
                ->get()
                ->map(fn($publisher) => [
                    'id' => $publisher->id,
                    'name' => $publisher->name,
                    'description' => $publisher->description,
                    'created_at' => $publisher->created_at->format('M d, Y'),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:publishers,name',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Publisher::create($validated);

        return redirect()->route('publishers.index')->with('success', 'Publisher created successfully!');
    }

    public function update(Request $request, Publisher $publisher)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:publishers,name,' . $publisher->id,
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $publisher->update($validated);

        return redirect()->route('publishers.index')->with('success', 'Publisher updated successfully!');
    }

    public function destroy(Publisher $publisher)
    {
        $publisher->delete();

        return redirect()->route('publishers.index')->with('success', 'Publisher deleted successfully!');
    }
}
