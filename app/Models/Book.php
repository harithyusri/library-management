<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'author_id',
        'isbn',
        'description',
        'publisher_id',
        'published_date',
        'pages',
        'language',
        'format',
        'price',
        'cover_image',
        'category_id',
        'rating',
    ];

    protected $casts = [
        'published_date' => 'date',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public function availableCopies()
    {
        return $this->hasMany(BookCopy::class)->where('status', 'available');
    }

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Helper methods
    public function getTotalCopiesAttribute()
    {
        return $this->copies()->count();
    }

    public function getAvailableCopiesCountAttribute()
    {
        return $this->copies()->where('status', 'available')->count();
    }
}
