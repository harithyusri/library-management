<?php

namespace App\Traits;

use App\Models\Library;
use App\Models\Scopes\LibraryScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasLibrary
{
    /**
     * Boot the trait.
     */
    protected static function bootHasLibrary(): void
    {
        static::addGlobalScope(new LibraryScope());

        static::creating(function ($model) {
            if (auth()->check() && ! $model->library_id) {
                $user = auth()->user();
                $user->loadMissing('staff');
                if ($user->staff && $user->staff->library_id) {
                    $model->library_id = $user->staff->library_id;
                }
            }
        });
    }

    /**
     * Get the library that owns the model.
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    /**
     * Scope a query to only include models of a specific library.
     */
    public function scopeOfLibrary(Builder $query, $libraryId): Builder
    {
        return $query->withoutGlobalScope(LibraryScope::class)->where('library_id', $libraryId);
    }
}
