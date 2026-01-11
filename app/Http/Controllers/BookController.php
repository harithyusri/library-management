<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     */
    public function index(Request $request)
    {
        $query = Book::query()
            ->with(['genres', 'category', 'publisher']);

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('author_name', 'like', '%' . $request->search . '%')
                    ->orWhere('isbn', 'like', '%' . $request->search . '%');
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
        $allowedSorts = ['created_at', 'title', 'publication_year'];
        $sortBy = in_array($request->sort_by, $allowedSorts)
            ? $request->sort_by
            : 'created_at';

        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        $books = $query->paginate(12)->withQueryString();

        return Inertia::render('Books/Index', [
            'books' => $books,
            'filters' => $request->only([
                'search',
                'genre',
                'category',
                'format',
                'language',
                'sort_by',
                'sort_order'
            ]),
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'formatOptions' => Book::getFormatOptions(),
            'languageOptions' => Book::getLanguageOptions(),
        ]);
    }

    /**
     * Show the form for creating a new book.
     */
    public function create()
    {
        return Inertia::render('Books/Create', [
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::orderBy('name')->get(['id', 'name', 'country']),
            'formatOptions' => Book::getFormatOptions(),
            'languageOptions' => Book::getLanguageOptions(),
        ]);
    }

    /**
     * Store a newly created book in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'title' => 'required|string|max:255',
                'author_name' => 'required|string|max:255',
                'isbn' => 'required|string|max:20|unique:books,isbn',

                'genre_ids'   => ['required', 'array', 'min:1'],
                'genre_ids.*' => ['exists:genres,id'],

                'category_id' => 'required|exists:categories,id',
                'publisher_id' => 'required|exists:publishers,id',

                'publication_year' => 'nullable|integer|min:1000|max:' . date('Y'),
                'format' => 'required|in:hardcover,paperback,ebook,audiobook',
                'pages' => 'required|integer|min:1',
                'language' => 'required|string|max:50',
                'description' => 'required|string',

                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            ],
            [
                // Basic info
                'title.required' => 'Please enter the book title.',
                'author_name.required' => 'Please enter the author name.',
                'isbn.required' => 'Please enter the ISBN.',
                'isbn.unique' => 'This ISBN already exists in the library.',

                // Genres
                'genre_ids.required' => 'Please select at least one genre.',
                'genre_ids.array' => 'Invalid genre selection.',
                'genre_ids.min' => 'Please select at least one genre.',
                'genre_ids.*.exists' => 'One or more selected genres are invalid.',

                // Category & publisher
                'category_id.required' => 'Please select a category.',
                'category_id.exists' => 'The selected category is invalid.',

                'publisher_id.required' => 'Please select a publisher.',
                'publisher_id.exists' => 'The selected publisher is invalid.',

                // Publication
                'publication_year.integer' => 'Publication year must be a valid number.',
                'publication_year.min' => 'Publication year is too early.',
                'publication_year.max' => 'Publication year cannot be in the future.',

                // Book details
                'format.required' => 'Please select a book format.',
                'format.in' => 'Selected book format is invalid.',

                'pages.required' => 'Please enter the number of pages.',
                'pages.integer' => 'Number of pages must be a number.',
                'pages.min' => 'Number of pages must be at least 1.',

                'language.required' => 'Please select a language.',
                'description.required' => 'Please enter a book description.',

                // Cover image
                'cover_image.image' => 'The cover image must be an image file.',
                'cover_image.mimes' => 'Cover image must be JPG, PNG, GIF, or WEBP.',
                'cover_image.max' => 'Cover image size must not exceed 10MB.',
            ]
        );

        $genreIds = $validated['genre_ids'];
        unset($validated['genre_ids']);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('book-covers', 'public');
            $validated['cover_image_url'] = '/storage/' . $path;
        }

        $book = Book::create($validated);

        $book->genres()->sync($genreIds);

        return redirect()->route('books.index')->with('success', 'Book created successfully!');
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
                $query->with('borrowedBy:id,name,email')
                    ->orderBy('created_at', 'desc');
            }
        ]);

        return Inertia::render('Books/Show', [
            'book' => $book,
        ]);
    }

    /**
     * Show the form for editing the specified book.
     */
    public function edit(Book $book)
    {
        $book->load('genres:id,name');

        return Inertia::render('Books/Edit', [
            'book' => $book,
            'genres' => Genre::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::orderBy('name')->get(['id', 'name', 'country']),
            'formatOptions' => Book::getFormatOptions(),
            'languageOptions' => Book::getLanguageOptions(),
        ]);
    }

    /**
     * Update the specified book in storage.
     */
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate(
            [
                'title' => 'required|string|max:255',
                'author_name' => 'required|string|max:255',
                'isbn' => 'required|string|max:20|unique:books,isbn,' . $book->id,

                'genre_ids'   => ['required', 'array', 'min:1'],
                'genre_ids.*' => ['exists:genres,id'],

                'category_id' => 'required|exists:categories,id',
                'publisher_id' => 'required|exists:publishers,id',

                'publication_year' => 'nullable|integer|min:1000|max:' . date('Y'),
                'format' => 'required|in:hardcover,paperback,ebook,audiobook',
                'pages' => 'required|integer|min:1',
                'language' => 'required|string|max:50',
                'description' => 'required|string',

                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            ],
            [
                // Basic info
                'title.required' => 'Please enter the book title.',
                'author_name.required' => 'Please enter the author name.',
                'isbn.required' => 'Please enter the ISBN.',
                'isbn.unique' => 'This ISBN already exists in the library.',

                // Genres
                'genre_ids.required' => 'Please select at least one genre.',
                'genre_ids.array' => 'Invalid genre selection.',
                'genre_ids.min' => 'Please select at least one genre.',
                'genre_ids.*.exists' => 'One or more selected genres are invalid.',

                // Category & publisher
                'category_id.required' => 'Please select a category.',
                'category_id.exists' => 'The selected category is invalid.',

                'publisher_id.required' => 'Please select a publisher.',
                'publisher_id.exists' => 'The selected publisher is invalid.',

                // Publication
                'publication_year.integer' => 'Publication year must be a valid number.',
                'publication_year.min' => 'Publication year is too early.',
                'publication_year.max' => 'Publication year cannot be in the future.',

                // Book details
                'format.required' => 'Please select a book format.',
                'format.in' => 'Selected book format is invalid.',

                'pages.required' => 'Please enter the number of pages.',
                'pages.integer' => 'Number of pages must be a number.',
                'pages.min' => 'Number of pages must be at least 1.',

                'language.required' => 'Please select a language.',
                'description.required' => 'Please enter a book description.',

                // Cover image
                'cover_image.image' => 'The cover image must be an image file.',
                'cover_image.mimes' => 'Cover image must be JPG, PNG, GIF, or WEBP.',
                'cover_image.max' => 'Cover image size must not exceed 10MB.',
            ]
        );

        $genreIds = $validated['genre_ids'];
        unset($validated['genre_ids']);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old image if exists
            if ($book->cover_image && !filter_var($book->cover_image, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $path = $request->file('cover_image')->store('book-covers', 'public');
            $validated['cover_image_url'] = '/storage/' . $path;
        }

        $book->update($validated);

        $book->genres()->sync($genreIds);

        return redirect()->route('books.index')->with('success', 'Book updated successfully.');
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

    /**
     * Store a new book copy.
     */
    public function storeCopy(Book $book)
    {

        // Create copy with UUID barcode
        $copy = $book->copies()->create([
            'barcode' => (string) Str::uuid(),
            'condition' => 'good',
            'status' => 'available',
            'call_number' => $this->generateCallNumber($book),
            'location' => 'Main Library',
        ]);

        return redirect()->back()->with('success', 'Book copy added successfully!');
    }

    /**
     * Update a book copy.
     */
    public function updateCopy(Request $request, Book $book, BookCopy $copy)
    {
        $validated = $request->validate([
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

    /**
     * Generate QR code for a book copy.
     */
    public function generateCopyQRCode(Book $book, BookCopy $copy)
    {
        $this->generateQRCode($book, $copy);

        return redirect()->back()->with('success', 'QR code generated successfully!');
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

    private function generateQRCode(Book $book, BookCopy $copy)
    {
        // 1. Prepare Data
        $qrData = json_encode([
            'barcode' => $copy->barcode,
            'book_id' => $book->id,
            'copy_id' => $copy->id,
            'title'   => $book->title,
            'author'  => $book->author_name,
            'isbn'    => $book->isbn,
            'call_number' => $copy->call_number,
        ]);

        // 2. Configure Options
        $options = new QROptions([
            'version'             => QRCode::VERSION_AUTO,
            'outputType'          => QROutputInterface::GDIMAGE_PNG, // Correct for v5
            'eccLevel'            => QRCode::ECC_H,
            'scale'               => 10,
            'imageBase64'         => false, // Returns raw binary
            'bgColor'             => [255, 255, 255],
            'imageTransparent'    => false,
        ]);

        // 3. Generate
        $qrcode = new QRCode($options);
        $qrCodeBinary = $qrcode->render($qrData);

        // 4. Save to Storage
        $filename = "qr-codes/{$copy->barcode}.png";

        // Create directory if missing
        if (!Storage::disk('public')->exists('qr-codes')) {
            Storage::disk('public')->makeDirectory('qr-codes');
        }

        Storage::disk('public')->put($filename, $qrCodeBinary);

        // 5. Update DB
        $copy->update([
            'qr_code_url' => Storage::url($filename),
        ]);
    }

    private function generateCallNumber(Book $book): string
    {
        $categoryName = $book->category->name ?? 'General';
        $categoryCode = strtoupper(substr($categoryName, 0, 3));

        // Get author code (first 3 letters of last name)
        $nameParts = explode(' ', $book->author_name);
        $lastName = end($nameParts);
        $authorCode = strtoupper(substr($lastName, 0, 3));

        // Copy number for this specific book
        $copyCount = $book->copies()->count() + 1;
        $copyNumber = str_pad($copyCount, 3, '0', STR_PAD_LEFT);

        return "{$categoryCode}-{$authorCode}-{$copyNumber}";
    }
}
