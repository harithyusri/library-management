<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BookCopy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'book_id',
        'barcode',
        'call_number',
        'condition',
        'status',
        'location',
        'acquisition_date',
        'acquisition_price',
        'qr_code_url',
        'notes',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate UUID barcode on creation
        static::creating(function ($copy) {
            if (empty($copy->barcode)) {
                $copy->barcode = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the book that owns this copy.
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the user who currently has this copy borrowed.
     */
    public function borrowedBy()
    {
        return $this->belongsTo(User::class, 'borrowed_by_user_id');
    }

    /**
     * Get the active borrow record for this copy.
     */
    public function activeBorrow()
    {
        return $this->hasOne(Borrow::class)
            ->whereNull('returned_at')
            ->latest();
    }

    /**
     * Get all borrow history for this copy.
     */
    public function borrows()
    {
        return $this->hasMany(Borrow::class)->orderBy('borrowed_at', 'desc');
    }

    /**
     * Check if copy is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if copy is borrowed.
     */
    public function isBorrowed(): bool
    {
        return $this->status === 'borrowed';
    }

    /**
     * Mark copy as borrowed.
     */
    public function markAsBorrowed($userId)
    {
        $this->update([
            'status' => 'borrowed',
        ]);
    }

    /**
     * Mark copy as returned.
     */
    public function markAsReturned()
    {
        $this->update([
            'status' => 'available',
        ]);
    }
}
