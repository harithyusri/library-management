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
            if ($user->isStaff() && $user->staff && $user->staff->library_id) {
                // Announcements can be global (library_id is null)
                if ($model instanceof \App\Models\Announcement) {
                    $builder->where(function ($query) use ($user) {
                        $query->where('library_id', $user->staff->library_id)
                              ->orWhereNull('library_id');
                    });
                } else {
                    $builder->where('library_id', $user->staff->library_id);
                }
            }
            
            // Note: Members can see any library, so we don't apply the scope for them 
            // unless we want to filter by "selected" library, which is better handled 
            // via manual scopes or a different mechanism.
        }
    }
}
