<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LibraryScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            // Super Admin can see everything
            if ($user->hasRole('Super Admin')) {
                return;
            }

            // If the user is staff, only show items from their library
            if ($user->isStaff()) {
                $user->loadMissing('staff');
                
                $staff = $user->staff;
                if ($staff && $staff->library_id) {
                    $table = $model->getTable();

                    // null library_id = shared/global record visible to all libraries
                    // (Announcements, Categories, Genres, Publishers)
                    $sharedModels = [
                        \App\Models\Announcement::class,
                        \App\Models\Category::class,
                        \App\Models\Genre::class,
                        \App\Models\Publisher::class,
                    ];

                    // Books: staff can see all books (copies may exist at any library)
                    if ($model instanceof \App\Models\Book) {
                        return;
                    }

                    if (in_array(get_class($model), $sharedModels)) {
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
