<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->uuid('barcode')->unique(); // Unique identifier for each copy
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->string('call_number', 50)->nullable(); // Library classification number
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'damaged'])->default('good');
            $table->enum('status', ['available', 'borrowed', 'reserved', 'maintenance', 'lost'])->default('available');
            $table->string('location')->nullable(); // Shelf location
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_price', 8, 2)->nullable();
            $table->string('qr_code_url')->nullable(); // Path to QR code image
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('barcode');
            $table->index(['book_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
