<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('id')->constrained('units')->nullOnDelete();
            $table->enum('role', ['admin','unit_user'])->default('unit_user')->after('unit_id');
        });

        // Backfill suave: si tu columna users.unidad tiene el nombre exacto de la unidad
        DB::statement("
            UPDATE users u
            JOIN units un ON un.name = u.unidad
            SET u.unit_id = un.id
            WHERE u.unit_id IS NULL AND u.unidad IS NOT NULL
        ");
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn('role');
        });
    }
};