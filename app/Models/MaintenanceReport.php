<?php

namespace App\Models;

use App\Traits\HasLibrary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class MaintenanceReport extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasLibrary;

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'description',
        'status',
        'priority',
        'image_path',
        'admin_notes',
        'library_id',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_REJECTED = 'rejected';

    const CATEGORY_BUILDING = 'Building';
    const CATEGORY_FURNITURE = 'Furniture';
    const CATEGORY_BOOKS = 'Books';
    const CATEGORY_ELECTRONICS = 'Electronics';
    const CATEGORY_OTHERS = 'Others';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
