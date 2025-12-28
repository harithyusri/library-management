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
            $table->foreignId('author_id')->nullable()->constrained()->nullOnDelete();
            $table->string('isbn')->unique()->nullable();
            $table->text('description')->nullable();
            $table->foreignId('publisher_id')->nullable()->constrained()->nullOnDelete();
            $table->date('published_date')->nullable();
            $table->integer('pages')->nullable();
            $table->string('language')->default('English');
            $table->enum('format', ['hardcover', 'paperback', 'ebook', 'audiobook'])->default('paperback');
            $table->decimal('price', 8, 2)->nullable();
            $table->string('cover_image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shelf_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('title');
            $table->index('isbn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
