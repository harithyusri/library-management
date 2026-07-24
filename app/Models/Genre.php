<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Genre extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = ['library_id', 'name'];

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function books()
    {
        return $this->belongsToMany(Book::class);
    }
}
