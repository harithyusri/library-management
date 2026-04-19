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
        Schema::table('book_copies', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('room_bookings', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('fine_payments', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('library_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });

        Schema::table('fine_payments', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });

        Schema::table('maintenance_reports', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });

        Schema::table('room_bookings', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });

        Schema::table('book_copies', function (Blueprint $table) {
            $table->dropForeign(['library_id']);
            $table->dropColumn('library_id');
        });
    }
};
