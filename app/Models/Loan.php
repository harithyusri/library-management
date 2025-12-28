<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
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

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function librarian()
    {
        return $this->belongsTo(User::class, 'librarian_id');
    }

    public function isOverdue()
    {
        return !$this->returned_date && $this->due_date->isPast();
    }

    public function calculateFine($finePerDay = 1.00)
    {
        if ($this->isOverdue()) {
            $daysOverdue = now()->diffInDays($this->due_date);
            return $daysOverdue * $finePerDay;
        }
        return 0;
    }
}
