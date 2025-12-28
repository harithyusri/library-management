<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reservation extends Model
{
    protected $fillable = [
        'book_id',
        'user_id',
        'reserved_date',
        'expiry_date',
        'status',
        'book_copy_id',
        'notified_at',
    ];

    protected $casts = [
        'reserved_date' => 'date',
        'expiry_date' => 'date',
        'notified_at' => 'datetime',
    ];

    // Relationships
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'ready']);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('expiry_date', '<', now())
                  ->whereIn('status', ['pending', 'ready']);
            });
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForBook($query, $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    // Helper Methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isReady()
    {
        return $this->status === 'ready';
    }

    public function isFulfilled()
    {
        return $this->status === 'fulfilled';
    }

    public function isExpired()
    {
        return $this->status === 'expired' ||
               ($this->expiry_date && $this->expiry_date->isPast() && $this->status !== 'fulfilled');
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isActive()
    {
        return in_array($this->status, ['pending', 'ready']);
    }

    /**
     * Mark reservation as ready and assign a book copy
     */
    public function markAsReady(BookCopy $bookCopy)
    {
        $this->update([
            'status' => 'ready',
            'book_copy_id' => $bookCopy->id,
            'notified_at' => now(),
        ]);

        // Update book copy status
        $bookCopy->update(['status' => 'reserved']);

        // TODO: Send notification to user
        // $this->user->notify(new ReservationReadyNotification($this));

        return $this;
    }

    /**
     * Mark reservation as fulfilled (book has been borrowed)
     */
    public function markAsFulfilled()
    {
        $this->update(['status' => 'fulfilled']);

        return $this;
    }

    /**
     * Mark reservation as expired
     */
    public function markAsExpired()
    {
        $this->update(['status' => 'expired']);

        // Release the book copy if it was assigned
        if ($this->book_copy_id) {
            $this->bookCopy->update(['status' => 'available']);
        }

        return $this;
    }

    /**
     * Cancel the reservation
     */
    public function cancel()
    {
        $this->update(['status' => 'cancelled']);

        // Release the book copy if it was assigned
        if ($this->book_copy_id) {
            $this->bookCopy->update(['status' => 'available']);
        }

        return $this;
    }

    /**
     * Get the position in queue for this reservation
     */
    public function getQueuePosition()
    {
        if (!$this->isPending()) {
            return null;
        }

        return Reservation::where('book_id', $this->book_id)
            ->where('status', 'pending')
            ->where('reserved_date', '<', $this->reserved_date)
            ->count() + 1;
    }

    /**
     * Get estimated wait days
     */
    public function getEstimatedWaitDays()
    {
        if (!$this->isPending()) {
            return null;
        }

        $position = $this->getQueuePosition();
        $averageLoanDays = 14; // Default loan period

        return $position * $averageLoanDays;
    }

    /**
     * Check if notification should be sent
     */
    public function shouldNotify()
    {
        return $this->isReady() && !$this->notified_at;
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    // Boot method for automatic status updates
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            // Set default reserved_date if not provided
            if (!$reservation->reserved_date) {
                $reservation->reserved_date = now();
            }

            // Set default expiry_date (7 days from reserved_date)
            if (!$reservation->expiry_date) {
                $reservation->expiry_date = Carbon::parse($reservation->reserved_date)->addDays(7);
            }
        });

        // When a reservation is marked as ready, check for expired ones
        static::updated(function ($reservation) {
            if ($reservation->isDirty('status') && $reservation->status === 'ready') {
                // Check if there are other pending reservations that should be processed
                static::where('book_id', $reservation->book_id)
                    ->where('status', 'pending')
                    ->where('id', '!=', $reservation->id)
                    ->oldest('reserved_date')
                    ->get()
                    ->each(function ($pendingReservation) {
                        // Process queue logic here if needed
                    });
            }
        });
    }
}
