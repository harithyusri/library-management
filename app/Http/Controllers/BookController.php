<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     */
    public function index(Request $request)
    {
        $query = Book::query()->with('currentBorrowers:id,name,email');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->status($request->status);
        }

        // Filter by genre
        if ($request->has('genre') && $request->genre !== 'all') {
            $query->genre($request->genre);
        }

        // Filter by format
        if ($request->has('format') && $request->format !== 'all') {
            $query->format($request->format);
        }

        // Filter available books
        if ($request->has('available_only') && $request->available_only) {
            $query->available();
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $books = $query->paginate(12)->withQueryString();

        // Get unique genres for filter
        $genres = Book::select('genre')
            ->distinct()
            ->whereNotNull('genre')
            ->pluck('genre')
            ->sort()
            ->values();

        return Inertia::render('Books/Index', [
            'books' => $books,
            'filters' => $request->only(['search', 'status', 'genre', 'format', 'available_only', 'sort_by', 'sort_order']),
            'genres' => $genres,
        ]);
    }

    /**
     * Show the form for creating a new book.
     */
    public function create()
    {
        return Inertia::render('Books/Create');
    }

    /**
     * Store a newly created book in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|unique:books,isbn|max:20',
            'description' => 'nullable|string',
            'publisher' => 'nullable|string|max:255',
            'published_date' => 'nullable|date',
            'pages' => 'nullable|integer|min:1',
            'language' => 'required|string|max:50',
            'format' => 'required|in:hardcover,paperback,ebook,audiobook',
            'price' => 'nullable|numeric|min:0',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:available,borrowed,reserved,maintenance',
            'quantity' => 'required|integer|min:1',
            'genre' => 'nullable|string|max:100',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('book-covers', 'public');
        }

        // Set available quantity equal to quantity for new books
        $validated['available_quantity'] = $validated['quantity'];

        Book::create($validated);

        return redirect()->route('books.index')
            ->with('success', 'Book created successfully.');
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book)
    {
        $book->load(['currentBorrowers:id,name,email', 'users' => function ($query) {
            $query->orderBy('book_user.created_at', 'desc')->limit(10);
        }]);

        // Check if current user has borrowed this book
        $userHasBorrowed = Auth::check()
            ? $book->users()
                ->wherePivot('user_id', Auth::id())
                ->wherePivot('status', 'borrowed')
                ->wherePivotNull('returned_date')
                ->exists()
            : false;

        return Inertia::render('Books/Show', [
            'book' => $book,
            'userHasBorrowed' => $userHasBorrowed,
        ]);
    }

    /**
     * Show the form for editing the specified book.
     */
    public function edit(Book $book)
    {
        return Inertia::render('Books/Edit', [
            'book' => $book,
        ]);
    }

    /**
     * Update the specified book in storage.
     */
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:books,isbn,' . $book->id,
            'description' => 'nullable|string',
            'publisher' => 'nullable|string|max:255',
            'published_date' => 'nullable|date',
            'pages' => 'nullable|integer|min:1',
            'language' => 'required|string|max:50',
            'format' => 'required|in:hardcover,paperback,ebook,audiobook',
            'price' => 'nullable|numeric|min:0',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:available,borrowed,reserved,maintenance',
            'quantity' => 'required|integer|min:1',
            'genre' => 'nullable|string|max:100',
            'rating' => 'nullable|numeric|min:0|max:5',
        ]);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old image if exists
            if ($book->cover_image && !filter_var($book->cover_image, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('book-covers', 'public');
        }

        // Adjust available quantity if total quantity changed
        $quantityDiff = $validated['quantity'] - $book->quantity;
        $validated['available_quantity'] = max(0, $book->available_quantity + $quantityDiff);

        $book->update($validated);

        return redirect()->route('books.index')
            ->with('success', 'Book updated successfully.');
    }

    /**
     * Remove the specified book from storage.
     */
    public function destroy(Book $book)
    {
        // Delete cover image if exists
        if ($book->cover_image && !filter_var($book->cover_image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($book->cover_image);
        }

        $book->delete();

        return redirect()->route('books.index')
            ->with('success', 'Book deleted successfully.');
    }

    /**
     * Borrow a book.
     */
    public function borrow(Request $request, Book $book)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $days = $request->input('days', 14);

        if (!$book->is_available) {
            return back()->with('error', 'This book is not available for borrowing.');
        }

        $success = $book->borrow(Auth::user(), $days);

        if ($success) {
            return back()->with('success', 'Book borrowed successfully.');
        }

        return back()->with('error', 'Failed to borrow the book.');
    }

    /**
     * Return a borrowed book.
     */
    public function returnBook(Book $book)
    {
        $success = $book->returnBook(Auth::user());

        if ($success) {
            return back()->with('success', 'Book returned successfully.');
        }

        return back()->with('error', 'Failed to return the book or you haven\'t borrowed it.');
    }

    /**
     * Get user's borrowed books.
     */
    public function myBooks()
    {
        $user = Auth::user();

        $borrowedBooks = $user->books()
            ->wherePivot('status', 'borrowed')
            ->wherePivotNull('returned_date')
            ->withPivot(['borrowed_date', 'due_date', 'status'])
            ->get();

        $borrowHistory = $user->books()
            ->wherePivot('status', 'returned')
            ->withPivot(['borrowed_date', 'due_date', 'returned_date', 'status'])
            ->orderBy('book_user.returned_date', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Books/MyBooks', [
            'borrowedBooks' => $borrowedBooks,
            'borrowHistory' => $borrowHistory,
        ]);
    }
}
