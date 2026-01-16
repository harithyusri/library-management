<?php

use App\Http\Controllers\Api\BookCopyApiController;

use App\Http\Controllers\StaffController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PublisherController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::inertia('dashboard', 'Dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware(['auth', 'verified'])->group(function () {

    // --- Book Resource Routes ---
    Route::get('books', [BookController::class, 'index'])->name('books.index');
    // If the create page needs *no* data from the DB, you could use Route::inertia here instead.
    // Assuming it stays in controller for now:
    Route::get('books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('books', [BookController::class, 'store'])->name('books.store');
    Route::get('books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::get('books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::match(['put', 'patch'], 'books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    Route::prefix('books/{book}/copies')->name('books.copies.')->group(function () {
        Route::post('/', [BookController::class, 'storeCopy'])->name('store');
        Route::put('/{copy}', [BookController::class, 'updateCopy'])->name('update');
        Route::post('/{copy}/generate-qr', [BookController::class, 'generateCopyQRCode'])->name('generate-qr');
        Route::delete('/{copy}', [BookController::class, 'destroyCopy'])->name('destroy');
    });

    Route::get('scan/{barcode}', [BookController::class, 'scanBarcode'])->name('books.scan');

    // --- Additional book routes ---
    Route::get('my-books', [BookController::class, 'myBooks'])->name('books.my-books');
    Route::post('books/{book}/borrow', [BookController::class, 'borrow'])->name('books.borrow');
    Route::post('books/{book}/return', [BookController::class, 'returnBook'])->name('books.return');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories/store', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/update/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/publishers', [PublisherController::class, 'index'])->name('publishers.index');
    Route::post('publishers/store', [PublisherController::class, 'store'])->name('publishers.store');
    Route::put('publishers/update/{publisher}', [PublisherController::class, 'update'])->name('publishers.update');
    Route::delete('publishers/{publisher}', [PublisherController::class, 'destroy'])->name('publishers.destroy');

    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
    Route::post('genres/store', [GenreController::class, 'store'])->name('genres.store');
    Route::put('genres/update/{genre}', [GenreController::class, 'update'])->name('genres.update');
    Route::delete('genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');

    // --- Loan/Borrow Routes ---
    Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::get('loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
    Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
    Route::put('loans/{loan}/return', [LoanController::class, 'return'])->name('loans.return');

    Route::prefix('admins')->name('admins.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/create', [StaffController::class, 'create'])->name('create');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::get('/{user}', [StaffController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [StaffController::class, 'edit'])->name('edit');
        Route::put('/{user}', [StaffController::class, 'update'])->name('update');
        Route::delete('/{user}', [StaffController::class, 'destroy'])->name('destroy');
    });

    // Members
    Route::prefix('members')->name('members.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::get('/create', [MemberController::class, 'create'])->name('create');
        Route::post('/', [MemberController::class, 'store'])->name('store');
        Route::get('/{user}', [MemberController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [MemberController::class, 'edit'])->name('edit');
        Route::put('/{user}', [MemberController::class, 'update'])->name('update');
        Route::delete('/{user}', [MemberController::class, 'destroy'])->name('destroy');
    });
});

// API routes for book copy search
Route::prefix('api')->name('api.')->group(function () {
    Route::get('book-copies/search', [BookCopyApiController::class, 'search'])->name('book-copies.search');
});

require __DIR__.'/settings.php';
