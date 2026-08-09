<?php

namespace App\Models;

use App\Traits\HasLibrary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Loan extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait, HasLibrary;

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
        'fine_receipt_path',
        'fine_paid_amount',
        'notes',
        'library_id',
        'renewals_count',
    ];

    protected $casts = [
        'borrowed_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
        'fine_amount' => 'decimal:2',
        'fine_paid_amount' => 'decimal:2',
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

        $days = $this->due_date->diffInDays(now());
        return $days > 0 ? $days : 0;
    }

    /**
     * Get the fine payments made for this loan.
     */
    public function payments()
    {
        return $this->hasMany(FinePayment::class);
    }
}
