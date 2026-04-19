<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Report extends Model implements Auditable
{
    use AuditableTrait;
    protected $fillable = [
        'generated_by',
        'type',
        'filters',
        'status',
        'file_path',
        'file_name',
        'error_message',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const TYPE_LOAN = 'loan';
    const TYPE_ROOM_RESERVATION = 'room_reservation';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING || $this->status === self::STATUS_PENDING;
    }
}
