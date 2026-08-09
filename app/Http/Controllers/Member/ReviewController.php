<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Book $book)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body'   => 'nullable|string|max:1000',
        ]);

        // Only members who have borrowed this book can review it
        $hasBorrowed = $request->user()->loans()
            ->whereHas('bookCopy', fn ($q) => $q->where('book_id', $book->id))
            ->whereNotNull('returned_date')
            ->exists();

        if (!$hasBorrowed) {
            return back()->with('error', 'You can only review books you have borrowed and returned.');
        }

        BookReview::updateOrCreate(
            ['book_id' => $book->id, 'user_id' => $request->user()->id],
            ['rating' => $request->rating, 'body' => $request->body],
        );

        return back()->with('success', 'Your review has been submitted.');
    }

    public function destroy(Request $request, BookReview $review)
    {
        abort_if($review->user_id !== $request->user()->id, 403);
        $review->delete();
        return back()->with('success', 'Review deleted.');
    }
}
