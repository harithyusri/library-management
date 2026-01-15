<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'book_copy_id',
        'user_id',
        'librarian_id',
        'borrowed_date',
        'due_date',
        'returned_date',
        'status',
        'fine_amount',
        'fine_paid',
        'notes',
    ];

    protected $casts = [
        'borrowed_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
        'fine_amount' => 'decimal:2',
        'fine_paid' => 'boolean',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_OVERDUE = 'overdue';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_OVERDUE => 'Overdue',
            self::STATUS_RETURNED => 'Returned',
        ];
    }

    /**
     * Get the book copy that was loaned.
     */
    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    /**
     * Get the user who borrowed the book.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the librarian who issued the loan.
     */
    public function librarian()
    {
        return $this->belongsTo(User::class, 'librarian_id');
    }

    /**
     * Check if loan is overdue.
     */
    public function isOverdue(): bool
    {
        return !$this->returned_date && $this->due_date < now();
    }

    /**
     * Check if loan is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->returned_date;
    }

    /**
     * Get days until due or overdue.
     */
    public function getDaysUntilDue(): int
    {
        if ($this->returned_date) {
            return 0;
        }

        return now()->diffInDays($this->due_date, false);
    }
}
