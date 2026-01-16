<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'address',
        'library_card_number',
        'membership_start_date',
        'membership_expiry_date',
        'membership_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'notes',
        'max_books_allowed',
        'max_days_allowed',
        'receive_notifications',
        'receive_newsletters',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'membership_start_date' => 'date',
            'membership_expiry_date' => 'date',
            'receive_notifications' => 'boolean',
            'receive_newsletters' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate library card number
        static::creating(function ($member) {
            if (empty($member->library_card_number)) {
                $member->library_card_number = 'LIB' . str_pad(
                    (Member::max('id') ?? 0) + 1, 
                    6, 
                    '0', 
                    STR_PAD_LEFT
                );
            }
            
            // Set membership start date if not set
            if (empty($member->membership_start_date)) {
                $member->membership_start_date = now();
            }
            
            // Set membership expiry (1 year from start)
            if (empty($member->membership_expiry_date)) {
                $member->membership_expiry_date = now()->addYear();
            }
        });
    }

    /**
     * Get the user that owns this member profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all loans for this member.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class, 'user_id', 'user_id');
    }

    /**
     * Get active loans for this member.
     */
    public function activeLoans()
    {
        return $this->hasMany(Loan::class, 'user_id', 'user_id')
            ->whereNull('returned_date');
    }

    /**
     * Get overdue loans for this member.
     */
    public function overdueLoans()
    {
        return $this->hasMany(Loan::class, 'user_id', 'user_id')
            ->whereNull('returned_date')
            ->where('due_date', '<', now());
    }

    /**
     * Check if membership is expired.
     */
    public function isMembershipExpired(): bool
    {
        return $this->membership_expiry_date < now();
    }

    /**
     * Get days until membership expires.
     */
    public function getDaysUntilExpiry(): int
    {
        return now()->diffInDays($this->membership_expiry_date, false);
    }

    /**
     * Get age from date of birth.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        return $this->date_of_birth->age;
    }

    /**
     * Get full emergency contact info.
     */
    public function getEmergencyContactAttribute(): ?string
    {
        if (!$this->emergency_contact_name) {
            return null;
        }
        
        $contact = $this->emergency_contact_name;
        
        if ($this->emergency_contact_phone) {
            $contact .= ' (' . $this->emergency_contact_phone . ')';
        }
        
        if ($this->emergency_contact_relationship) {
            $contact .= ' - ' . $this->emergency_contact_relationship;
        }
        
        return $contact;
    }

    /**
     * Check if member can borrow more books.
     */
    public function canBorrowMore(): bool
    {
        $activeLoansCount = $this->activeLoans()->count();
        return $activeLoansCount < $this->max_books_allowed;
    }

    /**
     * Get remaining borrowing capacity.
     */
    public function getRemainingBooksAttribute(): int
    {
        $activeLoansCount = $this->activeLoans()->count();
        return max(0, $this->max_books_allowed - $activeLoansCount);
    }

    /**
     * Scope: Expired memberships.
     */
    public function scopeExpired($query)
    {
        return $query->where('membership_expiry_date', '<', now());
    }

    /**
     * Scope: Expiring soon (within 30 days).
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('membership_expiry_date', '<=', now()->addDays($days))
                    ->where('membership_expiry_date', '>', now());
    }

    /**
     * Scope: By membership type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('membership_type', $type);
    }
}