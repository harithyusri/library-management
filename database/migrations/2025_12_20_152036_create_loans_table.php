<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_copy_id')->constrained('book_copies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('librarian_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('borrowed_date');
            $table->date('due_date');
            $table->date('returned_date')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->decimal('fine_amount', 8, 2)->nullable();
            $table->boolean('fine_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('borrowed_date');
            $table->index('due_date');
            $table->index('returned_date');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['book_copy_id', 'returned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
