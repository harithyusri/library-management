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
        // Keep users table minimal for authentication
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('phone');
            $table->softDeletes();
            
            $table->index('phone');
            $table->index('status');
        });

        // Create members table for borrower-specific data
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Personal Information
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            
            // Library Membership
            $table->string('library_card_number', 50)->unique();
            $table->date('membership_start_date');
            $table->date('membership_expiry_date');
            $table->enum('membership_type', ['basic', 'premium', 'student', 'senior'])->default('basic');
            
            // Emergency Contact
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relationship', 100)->nullable();
            
            // Borrowing Limits
            $table->integer('max_books_allowed')->default(5);
            $table->integer('max_days_allowed')->default(14);
            
            // Preferences
            $table->boolean('receive_notifications')->default(true);
            $table->boolean('receive_newsletters')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('library_card_number');
            $table->index('membership_expiry_date');
            $table->index(['membership_start_date', 'membership_expiry_date']);
        });

        // Create staff table for admin/librarian-specific data
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Employment Information
            $table->string('employee_id', 50)->unique();
            $table->date('hire_date');
            $table->string('position', 100)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('members');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropIndex(['status']);
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'status']);
        });
    }
};