<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author_name')->nullable();
            $table->string('isbn')->unique()->nullable();
            $table->integer('genre_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('category_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('publisher_id')->nullable()->constrained()->nullOnDelete();
            $table->date('published_year')->nullable();
            $table->enum('format', ['hardcover', 'paperback', 'ebook', 'audiobook'])->default('paperback');
            $table->integer('pages')->nullable();
            $table->string('language')->default('English');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
