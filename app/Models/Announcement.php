<?php

namespace App\Models;

use App\Traits\HasLibrary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Announcement extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasLibrary;

    protected $fillable = [
        'title',
        'content',
        'image_path',
        'is_active',
        'expires_at',
        'created_by',
        'library_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
