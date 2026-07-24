<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class LibraryScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Super Admin can see everything
            if ($user->hasRole('Super Admin')) {
                return;
            }

            // If the user is staff, only show items from their library
            if ($user->isStaff()) {
                // Load the staff relationship if not already loaded
                if (!$user->relationLoaded('staff')) {
                    $user->load('staff');
                }
                
                $staff = $user->staff;
                if ($staff && $staff->library_id) {
                    $table = $model->getTable();

                    // Announcements can be global (library_id is null)
                    if ($model instanceof \App\Models\Announcement) {
                        $builder->where(function ($query) use ($staff, $table) {
                            $query->where("{$table}.library_id", $staff->library_id)
                                  ->orWhereNull("{$table}.library_id");
                        });
                    } else {
                        $builder->where("{$table}.library_id", $staff->library_id);
                    }
                }
            }
            
            // Members can see any library
        }
    }
}
