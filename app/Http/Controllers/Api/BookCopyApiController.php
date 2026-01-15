<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookCopy;
use Illuminate\Http\Request;

class BookCopyApiController extends Controller
{
    /**
     * Search for available book copies.
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        // FIXED: Put all search conditions inside one where() group
        $copies = BookCopy::with('book:id,title,author_name,isbn')
            ->where('status', 'available') // First filter: only available
            ->where(function ($q) use ($query) {
                // Then search in ANY of these fields
                $q->where('barcode', 'like', "%{$query}%")
                  ->orWhere('call_number', 'like', "%{$query}%")
                  // OR search in book relationship
                  ->orWhereHas('book', function ($bookQuery) use ($query) {
                      $bookQuery->where('title', 'like', "%{$query}%")
                                ->orWhere('author_name', 'like', "%{$query}%")
                                ->orWhere('isbn', 'like', "%{$query}%");
                  });
            })
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $copies,
        ]);
    }
}