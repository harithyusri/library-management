<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomBooking extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'room_id',
        'user_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'purpose',
        'number_of_attendees',
        'special_requests',
        'notes',
        'approved_by',
        'approved_at',
        'cancellation_reason',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Get the room for this booking.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user who made the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the staff member who approved the booking.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if booking is in the past.
     */
    public function isPast(): bool
    {
        $bookingDateTime = \Carbon\Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . $this->end_time);
        return $bookingDateTime->isPast();
    }

    /**
     * Check if booking is upcoming.
     */
    public function isUpcoming(): bool
    {
        $bookingDateTime = \Carbon\Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . $this->start_time);
        return $bookingDateTime->isFuture();
    }

    /**
     * Check if booking is currently active.
     */
    public function isActive(): bool
    {
        $now = now();
        $bookingDate = $this->booking_date->format('Y-m-d');
        $todayDate = $now->toDateString();

        if ($bookingDate !== $todayDate) {
            return false;
        }

        $currentTime = $now->format('H:i:s');
        return $this->start_time <= $currentTime && $this->end_time >= $currentTime;
    }

    /**
     * Calculate duration in hours.
     */
    public function getDurationInHoursAttribute(): float
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        return $start->diffInHours($end, true);
    }

    /**
     * Calculate total cost.
     */
    public function getTotalCostAttribute(): float
    {
        return $this->duration_in_hours * $this->room->hourly_rate;
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'confirmed' => 'success',
            'cancelled' => 'danger',
            'completed' => 'secondary',
            default => 'default',
        };
    }

    /**
     * Get human-readable status.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending Approval',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope: Active bookings.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Scope: Upcoming bookings.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString())
                     ->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Scope: Past bookings.
     */
    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->where('booking_date', '<', now()->toDateString())
              ->orWhere('status', 'completed');
        });
    }

    /**
     * Scope: For specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('booking_date', $date);
    }

    /**
     * Scope: By status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}