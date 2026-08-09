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
            $table->foreignId('book_copy_id')->constrained('book_copies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('librarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('library_id')->nullable()->constrained()->nullOnDelete();
            $table->date('borrowed_date');
            $table->date('due_date');
            $table->date('returned_date')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->decimal('fine_amount', 8, 2)->nullable();
            $table->boolean('fine_paid')->default(false);
            $table->string('fine_receipt_path')->nullable();
            $table->decimal('fine_paid_amount', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('library_id');
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
