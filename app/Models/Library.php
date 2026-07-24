<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Library extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'opening_hours',
        'latitude',
        'longitude',
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function bookCopies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function roomBookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function finePayments(): HasMany
    {
        return $this->hasMany(FinePayment::class);
    }

    public function maintenanceReports(): HasMany
    {
        return $this->hasMany(MaintenanceReport::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Scope a query to order by distance from a point.
     * Distance returned in kilometers.
     */
    public function scopeOrderByDistance($query, $latitude, $longitude)
    {
        if (!$latitude || !$longitude) {
            return $query;
        }

        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query->select('*')
            ->selectRaw("$haversine AS distance", [$latitude, $longitude, $latitude])
            ->orderBy('distance');
    }

    /**
     * Scope a query to filter by distance from a point.
     */
    public function scopeWithinDistance($query, $latitude, $longitude, $distanceInKm)
    {
        if (!$latitude || !$longitude || !$distanceInKm) {
            return $query;
        }

        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query->whereRaw("$haversine < ?", [$latitude, $longitude, $latitude, $distanceInKm]);
    }
}
