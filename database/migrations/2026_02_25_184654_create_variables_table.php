<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('variables', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();                 // variable abreviada
            $table->string('label');                         // nombre corto
            $table->text('description')->nullable();
            $table->enum('data_type', ['number','text','boolean','select'])->default('text');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('variables');
    }
};