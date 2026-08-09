<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Library;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    /**
     * Display a listing of the libraries.
     */
    public function index(): Response
    {
        return Inertia::render('admins/Libraries/Index', [
            'libraries' => Library::query()
                ->latest()
                ->get()
                ->map(fn($library) => [
                    'id' => $library->id,
                    'name' => $library->name,
                    'address' => $library->address,
                    'phone' => $library->phone,
                    'email' => $library->email,
                    'opening_hours' => $library->opening_hours,
                    'latitude' => $library->latitude,
                    'longitude' => $library->longitude,
                    'is_active' => $library->is_active,
                    'max_borrow_limit' => $library->max_borrow_limit,
                    'created_at' => $library->created_at->format('M d, Y'),
                ]),
        ]);
    }

    /**
     * Show the form for creating a new library.
     */
    public function create(): Response
    {
        return Inertia::render('admins/Libraries/Create');
    }

    /**
     * Store a newly created library in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'opening_hours' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'max_borrow_limit' => 'integer|min:1|max:50',
        ]);

        $library = Library::create($validated);

        return redirect()->route('admin.libraries.index')->with('success', 'Library created successfully!');
    }

    public function edit(Library $library): Response
    {
        return Inertia::render('admins/Libraries/Edit', [
            'library' => [
                'id' => $library->id,
                'name' => $library->name,
                'address' => $library->address,
                'phone' => $library->phone,
                'email' => $library->email,
                'opening_hours' => $library->opening_hours,
                'latitude' => $library->latitude,
                'longitude' => $library->longitude,
                'is_active' => $library->is_active,
                'max_borrow_limit' => $library->max_borrow_limit,
            ]
        ]);
    }

    /**
     * Update the specified library in storage.
     */
    public function update(Request $request, Library $library)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'opening_hours' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'max_borrow_limit' => 'integer|min:1|max:50',
        ]);

        $library->update($validated);

        return redirect()->route('admin.libraries.index')->with('success', 'Library updated successfully!');
    }

    /**
     * Remove the specified library from storage.
     */
    public function destroy(Library $library)
    {
        // Check if library has items before deleting
        if ($library->bookCopies()->exists() || $library->rooms()->exists()) {
            return back()->with('error', 'Cannot delete library with associated books or rooms.');
        }

        $library->delete();

        return redirect()->route('admin.libraries.index')->with('success', 'Library deleted successfully!');
    }

    /**
     * Resolve a Google Maps short/full link and extract lat/lng coordinates.
     */
    public function resolveMapLink(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        try {
            // Follow redirects to get the final expanded URL
            $ch = curl_init($request->url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; LibraryApp/1.0)');
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD-only, we only need the final URL
            curl_exec($ch);
            $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);

            // Extract @lat,lng,zoom from the final Google Maps URL
            // e.g. https://www.google.com/maps/place/.../@3.08942,101.53321,17z/...
            if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $finalUrl, $matches)) {
                return response()->json([
                    'latitude'  => $matches[1],
                    'longitude' => $matches[2],
                ]);
            }

            return response()->json(['error' => 'Could not extract coordinates from the link. Make sure it is a valid Google Maps URL.'], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to resolve the link. Please try again.'], 500);
        }
    }
}
