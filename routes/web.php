<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\PublisherController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Use the Route::inertia helper for simple pages.
// Argument 1: The URL URI
// Argument 2: The JavaScript Page Component name
// Argument 3 (optional): An array of props to pass to the page

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
});

require __DIR__.'/settings.php';
