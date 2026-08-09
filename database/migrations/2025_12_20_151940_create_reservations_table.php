<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_copy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('library_id')->nullable()->constrained()->nullOnDelete();
            $table->date('reserved_date');
            $table->date('expiry_date');
            $table->enum('status', ['pending', 'ready', 'fulfilled', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index('library_id');
            $table->index(['user_id', 'status']);
            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
