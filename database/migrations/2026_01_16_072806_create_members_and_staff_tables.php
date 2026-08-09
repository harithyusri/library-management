<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('address')->nullable();
            $table->string('library_card_number', 50)->unique()->nullable();
            $table->date('membership_start_date')->nullable();
            $table->date('membership_expiry_date')->nullable();
            $table->enum('membership_type', ['standard', 'premium', 'student', 'senior'])->default('standard');
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relationship', 100)->nullable();
            $table->integer('max_books_allowed')->default(5);
            $table->integer('max_days_allowed')->default(14);
            $table->boolean('receive_notifications')->default(true);
            $table->boolean('receive_newsletters')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('library_card_number');
            $table->index('membership_expiry_date');
            $table->index('membership_type');
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('employee_id', 50)->unique()->nullable();
            $table->date('hire_date')->nullable();
            $table->string('position', 100)->nullable();
            $table->json('work_hours')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('library_id');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('members');
    }
};
