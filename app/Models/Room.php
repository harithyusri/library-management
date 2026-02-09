<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'description',
    ];

    public function bookings()
    {
        return $this->hasMany(RoomBooking::class);
    }
}
