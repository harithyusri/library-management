<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements Auditable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, AuditableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-create member or staff profile based on role
        static::created(function ($user) {
            // This will be handled in the controller after role assignment
        });
    }

    /**
     * Get the member profile for this user.
     */
    public function member()
    {
        return $this->hasOne(Member::class);
    }

    /**
     * Get the staff profile for this user.
     */
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Get all loans for this user.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get all room bookings for this user.
     */
    public function roomBookings()
    {
        return $this->hasMany(RoomBooking::class);
    }

    /**
     * Get active loans for this user.
     */
    public function activeLoans()
    {
        return $this->hasMany(Loan::class)
            ->whereNull('returned_date');
    }

    /**
     * Get overdue loans for this user.
     */
    public function overdueLoans()
    {
        return $this->hasMany(Loan::class)
            ->whereNull('returned_date')
            ->where('due_date', '<', now());
    }

    /**
     * Check if user has any overdue loans.
     */
    public function hasOverdueLoans(): bool
    {
        return $this->overdueLoans()->exists();
    }

    /**
     * Get total unpaid fines for this user.
     */
    public function getTotalUnpaidFines(): float
    {
        return $this->loans()->where('fine_paid', false)->sum(DB::raw('fine_amount - fine_paid_amount'));
    }

    /**
     * Check if user is admin or super admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['Super Admin', 'Admin']);
    }

    /**
     * Check if user is librarian.
     */
    public function isLibrarian(): bool
    {
        return $this->hasRole('Librarian');
    }

    /**
     * Check if user is staff (admin, super-admin, or librarian).
     */
    public function isStaff(): bool
    {
        return $this->hasRole(['Super Admin', 'Admin', 'Librarian']);
    }

    /**
     * Check if user is a member.
     */
    public function isMember(): bool
    {
        return $this->hasRole('Library Members');
    }

    /**
     * Get the appropriate profile (member or staff).
     */
    public function getProfileAttribute()
    {
        if ($this->isMember()) {
            return $this->member;
        }
        
        if ($this->isStaff()) {
            return $this->staff;
        }
        
        return null;
    }

    /**
     * Scope: Active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Members only.
     */
    public function scopeMembers($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('type', 'member');
        });
    }

    /**
     * Scope: Staff only.
     */
    public function scopeStaff($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->where('type', 'staff');
        });
    }
}