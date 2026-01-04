<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'author_name',
        'isbn',
        'genre_ids',
        'category_id',
        'publisher_id',
        'publication_year',
        'format',
        'pages',
        'language',
        'description',
        'cover_image_url',
    ];

    protected $casts = [
        'genre_ids' => 'array',
        'publication_year' => 'integer',
        'pages' => 'integer',
    ];

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
