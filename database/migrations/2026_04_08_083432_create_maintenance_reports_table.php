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
        Schema::create('maintenance_reports', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
            $blueprint->string('title');
            $blueprint->string('category'); // Building, Furniture, Books, Electronics, Others
            $blueprint->text('description');
            $blueprint->string('status')->default('pending'); // pending, assigned, in_progress, resolved, rejected
            $blueprint->string('priority')->default('medium'); // low, medium, high
            $blueprint->string('image_path')->nullable();
            $blueprint->text('admin_notes')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_reports');
    }
};
