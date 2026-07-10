<?php

namespace App\Models;

use App\Traits\HasLibrary;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Room extends Model implements Auditable
{
    use AuditableTrait, HasLibrary;

    protected $fillable = [
        'name',
        'room_number',
        'type',
        'capacity',
        'description',
        'amenities',
        'floor',
        'status',
        'hourly_rate',
        'image',
        'library_id',
    ];

    protected $appends = ['type_display', 'status_display', 'image_url'];

    protected $casts = [
        'amenities' => 'array',
        'capacity' => 'integer',
        'hourly_rate' => 'decimal:2',
    ];

    protected $attributes = [
    ];

    private $roomTypes = [
        'study_room' => 'Study Room',
        'meeting_room' => 'Meeting Room',
        'computer_lab' => 'Computer Lab',
        'reading_room' => 'Reading Room',
        'conference_room' => 'Conference Room',
    ];

    private $amenitiesList = [
        'wifi' => 'WiFi',
        'projector' => 'Projector',
        'whiteboard' => 'Whiteboard',
        'computers' => 'Computers',
        'air_conditioning' => 'Air Conditioning',
        'printer' => 'Printer',
        'phone' => 'Conference Phone',
        'tv' => 'TV/Monitor',
    ];

    private $statusesList = [
        'available' => 'Available',
        'maintenance' => 'Under Maintenance',
        'unavailable' => 'Unavailable',
    ];

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function getTypeDisplayAttribute()
    {
        return $this->roomTypes[$this->type] ?? $this->type;
    }

    public function getStatusDisplayAttribute()
    {
        return $this->statusesList[$this->status] ?? $this->status;
    }

    public function getRoomTypesAttribute()
    {
        return $this->roomTypes;
    }

    public function getAmenitiesListAttribute()
    {
        return $this->amenitiesList;
    }

    public function getStatusesAttribute()
    {
        return $this->statusesList;
    }

    public function bookings()
    {
        return $this->hasMany(RoomBooking::class);
    }
}
