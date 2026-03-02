<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('catalog_variable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_id')->constrained('catalogs')->cascadeOnDelete();
            $table->foreignId('variable_id')->constrained('variables')->cascadeOnDelete();

            $table->string('section')->nullable();          // apartado/sección libre
            $table->string('code')->nullable();             // 1.5, 1.8 SI.1, etc.
            $table->unsignedInteger('order')->default(0);

            $table->boolean('required_value')->default(true);
            $table->boolean('required_word')->default(false);
            $table->unsignedTinyInteger('required_links_min')->default(0);
            $table->boolean('visible')->default(true);

            $table->timestamps();

            $table->unique(['catalog_id','variable_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('catalog_variable');
    }
};