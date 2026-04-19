<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Loan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FineController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Loan::with(['bookCopy.book', 'user'])
            ->whereNotNull('fine_amount')
            ->where('fine_amount', '>', 0);

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $fines = $query->orderBy('due_date', 'desc')->get()->map(function ($loan) {
            return [
                'id' => $loan->id,
                'member_name' => $loan->user->name,
                'book_title' => $loan->bookCopy->book->title,
                'due_date' => $loan->due_date->toDateString(),
                'returned_date' => $loan->returned_date ? $loan->returned_date->toDateString() : null,
                'fine_amount' => $loan->fine_amount,
                'fine_paid' => (bool) $loan->fine_paid,
                'fine_paid_amount' => $loan->fine_paid_amount,
                'fine_receipt_path' => $loan->fine_receipt_path ? asset('storage/' . $loan->fine_receipt_path) : null,
                'status' => $loan->status,
            ];
        });

        return Inertia::render('admins/Fines/Index', [
            'fines' => $fines,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function markAsPaid(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'fine_paid_amount' => 'required|numeric|min:0.01',
            'fine_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $totalPaid = ($loan->fine_paid_amount ?? 0) + $validated['fine_paid_amount'];
        $isPaid = $totalPaid >= $loan->fine_amount;

        $updateData = [
            'fine_paid' => $isPaid,
            'fine_paid_amount' => $totalPaid,
        ];

        if ($request->hasFile('fine_receipt')) {
            $path = $request->file('fine_receipt')->store('receipts', 'public');
            $updateData['fine_receipt_path'] = $path;
        }

        $loan->update($updateData);

        $message = $isPaid ? 'Fine fully paid.' : 'Partial payment recorded.';
        return back()->with('success', $message);
    }

    public function downloadReceipt(Loan $loan)
    {
        if (!$loan->fine_paid) {
            return back()->with('error', 'Receipt only available for settled fines.');
        }

        $loan->load(['user', 'bookCopy.book']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('fines.receipt', compact('loan'));
        
        return $pdf->download('receipt-' . $loan->id . '.pdf');
    }
}
