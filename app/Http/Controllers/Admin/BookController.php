<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Library;
use App\Models\Publisher;
use App\Models\Scopes\LibraryScope;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BookController extends Controller
{
    public function __construct(private LoanService $loanService) {}
    /**
     * Display a listing of the books.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $libraries = [];
        $selectedLibraryId = null;

        if ($isSuperAdmin) {
            $libraries = Library::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $selectedLibraryId = $request->integer('library_id') ?: null;
        }

        $query = Book::query()
            ->with(['genres', 'category', 'publisher', 'library'])
            ->when($isSuperAdmin && $selectedLibraryId, fn ($q) =>
                $q->withoutGlobalScope(LibraryScope::class)->where('library_id', $selectedLibraryId)
            );

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('author_name', 'like', '%'.$request->search.'%')
                    ->orWhere('isbn', 'like', '%'.$request->search.'%');
            });
        }

        // Filter by genre (many-to-many)
        if ($request->filled('genre') && $request->genre !== 'all') {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre);
            });
        }

        // Filter by category
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        // Filter by format
        if ($request->filled('format') && $request->format !== 'all') {
            $query->where('format', $request->format);
        }

        // Filter by language
        if ($request->filled('language') && $request->language !== 'all') {
            $query->where('language', $request->language);
        }

        // Sorting (safe)
        $allowedSorts = ['created_at', 'title', 'published_year'];
        $sortBy = in_array($request->sort_by, $allowedSorts)
            ? $request->sort_by
            : 'created_at';

        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        $books = $query->paginate(12)->withQueryString();

        return Inertia::render('admins/Books/Index', [
            'books'               => $books,
            'filters'             => $request->only(['search', 'genre', 'category', 'format', 'language', 'sort_by', 'sort_order']),
            'genres'              => Genre::orderBy('name')->get(['id', 'name']),
            'categories'          => Category::orderBy('name')->get(['id', 'name']),
            'formatOptions'       => Book::getFormatOptions(),
            'languageOptions'     => Book::getLanguageOptions(),
            'libraries'           => $libraries,
            'selected_library_id' => $selectedLibraryId,
            'is_super_admin'      => $isSuperAdmin,
        ]);
    }

    /**
     * Show the form for creating a new book.
     */
    public function create()
    {
        return Inertia::render('admins/Books/Create', [
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::orderBy('name')->get(['id', 'name', 'country']),
            'libraries' => \App\Models\Library::orderBy('name')->get(['id', 'name']),
            'formatOptions' => Book::getFormatOptions(),
            'languageOptions' => Book::getLanguageOptions(),
        ]);
    }

    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();
        $genreIds  = $validated['genre_ids'];
        unset($validated['genre_ids']);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('book-covers', 'public');
            $validated['cover_image'] = '/storage/' . $path;
        }

        $book = Book::create($validated);
        $book->genres()->sync($genreIds);

        return redirect()->route('admin.books.show')->with('success', 'Book created successfully!');
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book)
    {
        $book->load([
            'genres:id,name',
            'category:id,name',
            'publisher:id,name',
            'copies' => function ($query) {
                $query->with(['borrowedBy:id,name,email', 'library:id,name'])
                    ->orderBy('created_at', 'desc');
            },
        ]);

        return Inertia::render('admins/Books/Show', [
            'book' => $book,
            'libraries' => \App\Models\Library::orderBy('name')->get(['id', 'name']),
            'audits' => $book->audits()->with('user:id,name')->latest()->get(),
        ]);
    }

    /**
     * Show the form for editing the specified book.
     */
    public function edit(Book $book)
    {
        $book->load('genres:id,name');

        return Inertia::render('admins/Books/Edit', [
            'book' => $book,
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::orderBy('name')->get(['id', 'name', 'country']),
            'libraries' => \App\Models\Library::orderBy('name')->get(['id', 'name']),
            'formatOptions' => Book::getFormatOptions(),
            'languageOptions' => Book::getLanguageOptions(),
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $validated = $request->validated();
        $genreIds  = $validated['genre_ids'];
        unset($validated['genre_ids']);

        if ($request->hasFile('cover_image')) {
            if ($book->cover_image && ! filter_var($book->cover_image, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $book->cover_image));
            }
            $path = $request->file('cover_image')->store('book-covers', 'public');
            $validated['cover_image'] = '/storage/' . $path;
        } else {
            unset($validated['cover_image']);
        }

        $book->update($validated);
        $book->genres()->sync($genreIds);

        return redirect()->route('admin.books.show')->with('success', 'Book updated successfully.');
    }

    /**
     * Remove the specified book from storage.
     */
    public function destroy(Book $book)
    {
        // Delete cover image if exists
        if ($book->cover_image && ! filter_var($book->cover_image, FILTER_VALIDATE_URL)) {
            $oldPath = str_replace('/storage/', '', $book->cover_image);
            Storage::disk('public')->delete($oldPath);
        }

        $book->delete();

        return redirect()->route('books.index')
            ->with('success', 'Book deleted successfully.');
    }

    /**
     * Borrow a book.
     */
    // public function borrow(Request $request, Book $book)
    // {
    //     $request->validate([
    //         'days' => 'nullable|integer|min:1|max:90',
    //     ]);

    //     $days = $request->input('days', 14);

    //     if (!$book->is_available) {
    //         return back()->with('error', 'This book is not available for borrowing.');
    //     }

    //     $success = $book->borrow(Auth::user(), $days);

    //     if ($success) {
    //         return back()->with('success', 'Book borrowed successfully.');
    //     }

    //     return back()->with('error', 'Failed to borrow the book.');
    // }

    /**
     * Return a borrowed book.
     */
    // public function returnBook(Book $book)
    // {
    //     $success = $book->returnBook(Auth::user());

    //     if ($success) {
    //         return back()->with('success', 'Book returned successfully.');
    //     }

    //     return back()->with('error', 'Failed to return the book or you haven\'t borrowed it.');
    // }

    // /**
    //  * Get user's borrowed books.
    //  */
    // public function myBooks()
    // {
    //     $user = Auth::user();

    //     $borrowedBooks = $user->books()
    //         ->wherePivot('status', 'borrowed')
    //         ->wherePivotNull('returned_date')
    //         ->withPivot(['borrowed_date', 'due_date', 'status'])
    //         ->get();

    //     $borrowHistory = $user->books()
    //         ->wherePivot('status', 'returned')
    //         ->withPivot(['borrowed_date', 'due_date', 'returned_date', 'status'])
    //         ->orderBy('book_user.returned_date', 'desc')
    //         ->limit(20)
    //         ->get();

    //     return Inertia::render('Books/MyBooks', [
    //         'borrowedBooks' => $borrowedBooks,
    //         'borrowHistory' => $borrowHistory,
    //     ]);
    // }

    public function storeCopy(Request $request, Book $book)
    {
        $validated = $request->validate([
            'library_id' => 'required|exists:libraries,id',
            'condition'  => 'required|in:excellent,good,fair,poor,damaged',
            'location'   => 'nullable|string|max:255',
        ]);

        $book->copies()->create([
            'barcode'     => (string) Str::uuid(),
            'condition'   => $validated['condition'],
            'status'      => 'available',
            'call_number' => $this->loanService->generateCallNumber($book),
            'location'    => $validated['location'] ?? null,
            'library_id'  => $validated['library_id'],
        ]);

        return back()->with('success', 'Book copy added successfully!');
    }

    /**
     * Update a book copy.
     */
    public function updateCopy(Request $request, Book $book, BookCopy $copy)
    {
        $validated = $request->validate([
            'library_id' => 'required|exists:libraries,id',
            'call_number' => 'nullable|string|max:50',
            'condition' => 'required|in:excellent,good,fair,poor,damaged',
            'status' => 'required|in:available,borrowed,reserved,maintenance,lost',
            'location' => 'nullable|string|max:255',
            'acquisition_date' => 'nullable|date',
            'acquisition_price' => 'nullable|numeric|min:0|max:99999.99',
            'notes' => 'nullable|string|max:1000',
        ]);

        $copy->update($validated);

        return redirect()->back()->with('success', 'Book copy updated successfully!');
    }

    public function generateCopyQRCode(Book $book, BookCopy $copy)
    {
        $this->loanService->generateQrCode($book, $copy);
        return back()->with('success', 'QR code generated successfully!');
    }

    /**
     * Scan a barcode and return the book copy details as JSON.
     */
    public function scanBarcode(Request $request, string $barcode)
    {
        $copy = BookCopy::with('book:id,title,author_name,isbn')
            ->where('barcode', $barcode)
            ->first();

        if (! $copy) {
            return response()->json(['error' => 'Book copy not found for this barcode.'], 404);
        }

        return response()->json([
            'id'          => $copy->id,
            'barcode'     => $copy->barcode,
            'call_number' => $copy->call_number,
            'status'      => $copy->status,
            'condition'   => $copy->condition,
            'location'    => $copy->location,
            'book'        => [
                'id'          => $copy->book->id,
                'title'       => $copy->book->title,
                'author_name' => $copy->book->author_name,
                'isbn'        => $copy->book->isbn,
            ],
        ]);
    }

    /**
     * Delete a book copy.
     */
    public function destroyCopy(Book $book, BookCopy $copy)
    {
        // Check if copy is currently borrowed
        if ($copy->status === 'borrowed') {
            return redirect()->back()->with('error', 'Cannot delete a borrowed copy!');
        }

        // Delete QR code file if exists
        if ($copy->qr_code_url) {
            $path = str_replace('/storage/', '', $copy->qr_code_url);
            Storage::disk('public')->delete($path);
        }

        $copy->delete();

        return redirect()->back()->with('success', 'Book copy deleted successfully!');
    }

    /**
     * Helper method to generate QR code.
     */
    private function generateCallNumber(Book $book): string { return ''; } // kept for BC — use LoanService::generateCallNumber
}
