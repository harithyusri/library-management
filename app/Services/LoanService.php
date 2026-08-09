<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanRenewedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LoanService
{
    public const FINE_RATE_PER_DAY = 1.00;
    public const DEFAULT_LOAN_DAYS = 14;
    public const MAX_RENEWALS = 2;

    /**
     * Borrow the first available copy of a book for a member.
     */
    public function activeLoanCount(User $user): int
    {
        return $user->loans()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->count();
    }

    public function borrowBook(User $user, Book $book, ?int $libraryId = null): Loan
    {
        $query = $book->copies()->where('status', 'available');
        if ($libraryId) {
            $query->where('library_id', $libraryId);
        }
        $copy = $query->firstOrFail();

        $library = $copy->library;
        $limit   = $library ? $library->getBorrowLimit() : \App\Models\Library::DEFAULT_BORROW_LIMIT;

        if ($this->activeLoanCount($user) >= $limit) {
            throw new \RuntimeException("Borrow limit reached. You can only have {$limit} active loans at a time.");
        }

        return DB::transaction(function () use ($user, $copy) {
            $loan = Loan::create([
                'book_copy_id'  => $copy->id,
                'user_id'       => $user->id,
                'borrowed_date' => now(),
                'due_date'      => now()->addDays(self::DEFAULT_LOAN_DAYS),
                'status'        => Loan::STATUS_ACTIVE,
                'library_id'    => $copy->library_id,
            ]);

            $copy->update(['status' => 'borrowed']);

            return $loan;
        });
    }

    /**
     * Issue a loan manually (admin/librarian).
     */
    public function issueLoan(array $data, int $librarianId): Loan
    {
        $copy = BookCopy::findOrFail($data['book_copy_id']);

        if ($copy->status !== 'available') {
            throw new \RuntimeException('This book copy is not available for borrowing.');
        }

        return DB::transaction(function () use ($data, $copy, $librarianId) {
            $loan = Loan::create([
                'user_id'       => $data['user_id'],
                'book_copy_id'  => $data['book_copy_id'],
                'librarian_id'  => $librarianId,
                'borrowed_date' => $data['borrowed_date'],
                'due_date'      => $data['due_date'],
                'notes'         => $data['notes'] ?? null,
                'status'        => Loan::STATUS_ACTIVE,
                'library_id'    => $copy->library_id,
            ]);

            $copy->update(['status' => 'borrowed']);

            return $loan;
        });
    }

    /**
     * Renew a loan by extending the due date.
     */
    public function renewLoan(Loan $loan): Loan
    {
        if ($loan->returned_date) {
            throw new \RuntimeException('Cannot renew a returned loan.');
        }

        if ($loan->renewals_count >= self::MAX_RENEWALS) {
            throw new \RuntimeException('Maximum renewals reached. You can only renew a loan ' . self::MAX_RENEWALS . ' times.');
        }

        $loan->update([
            'due_date'       => $loan->due_date->addDays(self::DEFAULT_LOAN_DAYS),
            'renewals_count' => $loan->renewals_count + 1,
            'status'         => Loan::STATUS_ACTIVE,
        ]);

        $loan->refresh();
        $loan->user->notify(new LoanRenewedNotification($loan));

        return $loan;
    }

    /**
     * Return a borrowed book and calculate any fine.
     */
    public function returnBook(Loan $loan, array $data): Loan
    {
        if ($loan->returned_date) {
            throw new \RuntimeException('This book has already been returned.');
        }

        return DB::transaction(function () use ($loan, $data) {
            $returnedDate = $data['returned_date'];
            $notes = $loan->notes . (isset($data['condition_notes']) ? "\n\nReturn notes: " . $data['condition_notes'] : '');

            $loan->update([
                'returned_date' => $returnedDate,
                'status'        => Loan::STATUS_RETURNED,
                'notes'         => $notes,
            ]);

            $loan->bookCopy->update(['status' => 'available']);

            $fine = $this->calculateFine($loan->due_date, $returnedDate);
            if ($fine > 0) {
                $loan->update([
                    'fine_amount' => $fine,
                    'fine_paid'   => false,
                ]);
            }

            return $loan->fresh();
        });
    }

    /**
     * Calculate fine amount based on due date and return date.
     */
    public function calculateFine(\DateTimeInterface|string $dueDate, \DateTimeInterface|string $returnedDate): float
    {
        $due      = \Carbon\Carbon::parse($dueDate);
        $returned = \Carbon\Carbon::parse($returnedDate);

        if ($returned->lte($due)) {
            return 0.0;
        }

        $daysOverdue = $returned->diffInDays($due);
        return round($daysOverdue * self::FINE_RATE_PER_DAY, 2);
    }

    /**
     * Generate and store a QR code for a book copy.
     */
    public function generateQrCode(Book $book, BookCopy $copy): string
    {
        $qrData = json_encode([
            'barcode'     => $copy->barcode,
            'book_id'     => $book->id,
            'copy_id'     => $copy->id,
            'title'       => $book->title,
            'author'      => $book->author_name,
            'isbn'        => $book->isbn,
            'call_number' => $copy->call_number,
        ]);

        $options = new \chillerlan\QRCode\QROptions([
            'version'        => \chillerlan\QRCode\QRCode::VERSION_AUTO,
            'outputType'     => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
            'eccLevel'       => \chillerlan\QRCode\QRCode::ECC_H,
            'scale'          => 10,
            'imageBase64'    => false,
            'bgColor'        => [255, 255, 255],
            'imageTransparent' => false,
        ]);

        $binary   = (new \chillerlan\QRCode\QRCode($options))->render($qrData);
        $filename = "qr-codes/{$copy->barcode}.png";

        if (! Storage::disk('public')->exists('qr-codes')) {
            Storage::disk('public')->makeDirectory('qr-codes');
        }

        Storage::disk('public')->put($filename, $binary);

        $url = Storage::url($filename);
        $copy->update(['qr_code_url' => $url]);

        return $url;
    }

    /**
     * Generate a call number for a new book copy.
     */
    public function generateCallNumber(Book $book): string
    {
        $book->loadMissing('category');
        $categoryCode = strtoupper(substr($book->category->name ?? 'General', 0, 3));

        $nameParts  = explode(' ', trim($book->author_name ?? ''));
        $authorCode = strtoupper(substr(end($nameParts) ?: 'UNK', 0, 3));

        $copyNumber = str_pad($book->copies()->count() + 1, 3, '0', STR_PAD_LEFT);

        return "{$categoryCode}-{$authorCode}-{$copyNumber}";
    }
}
