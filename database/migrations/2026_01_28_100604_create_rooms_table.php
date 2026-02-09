<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('room_number')->nullable();
            $table->string('type');
            $table->unsignedInteger('capacity')->default(1);
            $table->text('description')->nullable();
            $table->json('amenities')->nullable();
            $table->unsignedInteger('floor')->nullable();

            $table->enum('status', [
                'available',
                'maintenance',
                'unavailable',
            ])->default('available');

            $table->decimal('hourly_rate', 8, 2)->default(0);

            $table->string('image')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
            $table->index('capacity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
