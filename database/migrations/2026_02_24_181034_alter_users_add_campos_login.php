<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('numero_economico')->unique()->after('id');
            $table->string('cargo')->after('numero_economico');
            $table->string('correo')->unique()->after('cargo');
            $table->string('unidad')->after('correo');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['numero_economico', 'cargo', 'correo', 'unidad']);
        });
    }
};
