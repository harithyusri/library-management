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
        $libraryId = Illuminate\Support\Facades\DB::table('libraries')->insertGetId([
            'name' => 'Main Library',
            'address' => '123 Library Street, City Center',
            'phone' => '012-3456789',
            'email' => 'main@library.example.com',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tables = [
            'book_copies',
            'rooms',
            'loans',
            'room_bookings',
            'staff',
            'maintenance_reports',
            'reservations',
            'fine_payments',
            'announcements',
        ];

        foreach ($tables as $table) {
            Illuminate\Support\Facades\DB::table($table)->update(['library_id' => $libraryId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to undo data assignment without deleting data, 
        // but we can delete the library if needed.
        Illuminate\Support\Facades\DB::table('libraries')->where('name', 'Main Library')->delete();
    }
};
