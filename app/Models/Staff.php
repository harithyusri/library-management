<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'staff';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'employee_id',
        'hire_date',
        'department',
        'position',
        'work_hours',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'work_hours' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate employee ID
        static::creating(function ($staff) {
            if (empty($staff->employee_id)) {
                $lastStaff = Staff::withTrashed()->orderBy('id', 'desc')->first();
                $nextId = $lastStaff ? $lastStaff->id + 1 : 1;
                
                $staff->employee_id = 'EMP' . str_pad(
                    $nextId, 
                    5, 
                    '0', 
                    STR_PAD_LEFT
                );
            }
            
            // Set hire date if not set
            if (empty($staff->hire_date)) {
                $staff->hire_date = now();
            }
        });
    }

    /**
     * Get the user that owns this staff profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get loans issued by this staff member (as librarian).
     */
    public function issuedLoans()
    {
        return $this->hasMany(Loan::class, 'librarian_id', 'user_id');
    }

    /**
     * Get books returned to this staff member.
     */
    public function returnedLoans()
    {
        return $this->hasMany(Loan::class, 'librarian_id', 'user_id')
            ->whereNotNull('returned_date');
    }

    /**
     * Get years of service.
     */
    public function getYearsOfServiceAttribute(): int
    {
        if (!$this->hire_date) {
            return 0;
        }
        
        return $this->hire_date->diffInYears(now());
    }

    /**
     * Get months of service.
     */
    public function getMonthsOfServiceAttribute(): int
    {
        if (!$this->hire_date) {
            return 0;
        }
        
        return $this->hire_date->diffInMonths(now());
    }

    /**
     * Get days of service.
     */
    public function getDaysOfServiceAttribute(): int
    {
        if (!$this->hire_date) {
            return 0;
        }
        
        return $this->hire_date->diffInDays(now());
    }

    /**
     * Get formatted service duration.
     */
    public function getServiceDurationAttribute(): string
    {
        if (!$this->hire_date) {
            return 'N/A';
        }
        
        $years = $this->years_of_service;
        $months = $this->hire_date->diffInMonths(now()) % 12;
        
        if ($years === 0) {
            return "{$months} " . ($months === 1 ? 'month' : 'months');
        }
        
        if ($months === 0) {
            return "{$years} " . ($years === 1 ? 'year' : 'years');
        }
        
        return "{$years} " . ($years === 1 ? 'year' : 'years') . ", {$months} " . ($months === 1 ? 'month' : 'months');
    }

    /**
     * Get total loans processed by this staff member.
     */
    public function getTotalLoansProcessedAttribute(): int
    {
        return $this->issuedLoans()->count();
    }

    /**
     * Get total returns processed by this staff member.
     */
    public function getTotalReturnsProcessedAttribute(): int
    {
        return $this->returnedLoans()->count();
    }

    /**
     * Check if staff member works on a specific day.
     */
    public function worksOn(string $day): bool
    {
        if (!$this->work_hours || !is_array($this->work_hours)) {
            return false;
        }
        
        return isset($this->work_hours[strtolower($day)]);
    }

    /**
     * Get work hours for a specific day.
     */
    public function getWorkHoursForDay(string $day): ?string
    {
        if (!$this->worksOn($day)) {
            return null;
        }
        
        return $this->work_hours[strtolower($day)];
    }

    /**
     * Scope: By department.
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope: By position.
     */
    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope: Hired after date.
     */
    public function scopeHiredAfter($query, $date)
    {
        return $query->where('hire_date', '>=', $date);
    }

    /**
     * Scope: Hired before date.
     */
    public function scopeHiredBefore($query, $date)
    {
        return $query->where('hire_date', '<=', $date);
    }

    /**
     * Scope: Active staff (user status is active).
     */
    public function scopeActive($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('status', 'active');
        });
    }
}