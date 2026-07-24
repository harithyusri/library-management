<?php

namespace App\Models;

use App\Traits\HasLibrary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Book extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait, HasLibrary;

    protected $fillable = [
        'title',
        'author_name',
        'isbn',
        'genre_ids',
        'category_id',
        'publisher_id',
        'published_year',
        'format',
        'pages',
        'language',
        'description',
        'cover_image',
        'library_id',
    ];

    protected $casts = [
        'genre_ids' => 'array',
        'published_year' => 'integer',
        'pages' => 'integer',
    ];

    protected $appends = ['cover_image_url'];

    /**
     * Get the full URL for the book cover image.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (filter_var($this->cover_image, FILTER_VALIDATE_URL)) {
            return $this->cover_image;
        }

        $path = ltrim($this->cover_image, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return asset('storage/'.$path);
    }

    private static function formatOptions()
    {
        return [
            'hardcover' => 'Hardcover',
            'paperback' => 'Paperback',
            'ebook' => 'E-book',
            'audiobook' => 'Audiobook',
        ];
    }

    private static function languageOptions()
    {
        return [
            'english' => 'English',
            'spanish' => 'Spanish',
            'french' => 'French',
            'german' => 'German',
            'chinese' => 'Chinese',
            'japanese' => 'Japanese',
            'russian' => 'Russian',
            'italian' => 'Italian',
            'portuguese' => 'Portuguese',
            'arabic' => 'Arabic',
        ];
    }

    /**
     * Get the genres that own the book.
     */
   public function genres()
    {
        return $this->belongsToMany(Genre::class, 'book_genre', 'book_id', 'genre_id');
    }

    /**
     * Get the category that owns the book.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the publisher that owns the book.
     */
    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public static function getFormatOptions()
    {
        return self::formatOptions();
    }

    public static function getLanguageOptions()
    {
        return self::languageOptions();
    }

    /**
     * Scope a query to search by title or author.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('author_name', 'like', "%{$search}%")
              ->orWhere('isbn', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to filter by genre ID.
     */
    public function scopeByGenre($query, $genreId)
    {
        return $query->where('genre_id', $genreId);
    }

    /**
     * Scope a query to filter by category ID.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by format.
     */
    public function scopeByFormat($query, $format)
    {
        return $query->where('format', $format);
    }

    /**
     * Scope a query to filter by language.
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }
}
