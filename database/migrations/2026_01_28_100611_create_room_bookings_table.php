<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Booking time
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');

            // Booking details
            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled',
                'completed',
            ])->default('pending');

            $table->string('purpose')->nullable();
            $table->unsignedInteger('number_of_attendees')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();

            // Approval & cancellation
            $table->timestamp('approved_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes (important for availability checks)
            $table->index(['room_id', 'booking_date']);
            $table->index('status');
            $table->index('booking_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_bookings');
    }
};
