<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('type')->nullable()->after('guard_name');
        });

        // Seed initial values for existing roles
        DB::table('roles')->whereIn('name', ['super-admin', 'admin', 'librarian'])->update(['type' => 'staff']);
        DB::table('roles')->where('name', 'member')->update(['type' => 'member']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
