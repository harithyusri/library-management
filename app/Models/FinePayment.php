<?php

namespace App\Models;

use App\Traits\HasLibrary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinePayment extends Model
{
    use HasFactory, SoftDeletes, HasLibrary;

    protected $fillable = [
        'loan_id',
        'user_id',
        'amount',
        'currency',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'status',
        'payment_method',
        'paid_at',
        'library_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the loan that this payment is for.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the user who made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
