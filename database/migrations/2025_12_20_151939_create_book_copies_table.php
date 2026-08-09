<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('barcode')->unique();
            $table->string('call_number', 50)->nullable();
            $table->enum('condition', ['excellent', 'good', 'fair', 'poor', 'damaged'])->default('good');
            $table->enum('status', ['available', 'borrowed', 'reserved', 'maintenance', 'lost'])->default('available');
            $table->string('location')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_price', 8, 2)->nullable();
            $table->string('qr_code_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('barcode');
            $table->index('library_id');
            $table->index(['book_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
