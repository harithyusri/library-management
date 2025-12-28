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
            $table->foreignId('book_id')->constrained()->cascadeOnDelete(); // Reserve any copy of this book
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('reserved_date');
            $table->date('expiry_date');
            $table->enum('status', ['pending', 'ready', 'fulfilled', 'expired', 'cancelled'])->default('pending');
            $table->foreignId('book_copy_id')->nullable()->constrained()->nullOnDelete(); // Assigned when ready
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
