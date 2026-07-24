<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'publishers', 'genres'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('library_id')->nullable()->constrained()->nullOnDelete()->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (['categories', 'publishers', 'genres'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeignIdFor(\App\Models\Library::class);
                $blueprint->dropColumn('library_id');
            });
        }
    }
};
