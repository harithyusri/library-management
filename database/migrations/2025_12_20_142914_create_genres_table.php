<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
            $table->timestamps();

            $table->index('library_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
