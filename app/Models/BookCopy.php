<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BookCopy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'barcode',
        'book_id',
        'call_number',
        'condition',
        'status',
        'location',
        'acquisition_date',
        'acquisition_price',
        'notes',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bookCopy) {
            if (empty($bookCopy->barcode)) {
                $bookCopy->barcode = (string) Str::uuid();
            }
        });
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function currentLoan()
    {
        return $this->hasOne(Loan::class)->where('status', 'active')->latest();
    }

    public function isAvailable()
    {
        return $this->status === 'available';
    }
}
