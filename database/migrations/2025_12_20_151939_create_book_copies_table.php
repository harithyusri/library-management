<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->uuid('barcode')->unique()->default(DB::raw('(UUID())')); // Unique identifier for each copy
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('call_number')->nullable(); // Library classification number
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'damaged'])->default('good');
            $table->enum('status', ['available', 'borrowed', 'reserved', 'maintenance', 'lost'])->default('available');
            $table->string('location')->nullable(); // Shelf location
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_price', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('barcode');
            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
