<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Category extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'library_id',
        'name',
        'code',
        'slug',
        'description',
    ];

    /**
     * Relationship: A category can have many books.
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
}
