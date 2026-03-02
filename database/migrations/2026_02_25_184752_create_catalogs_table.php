<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_id')->constrained('rankings');
            $table->foreignId('stage_id')->constrained('stages');
            $table->unsignedSmallInteger('year')->nullable(); // si algún día versionas por año
            $table->enum('status', ['draft','published','archived'])->default('draft');
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ranking_id','stage_id','year','status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('catalogs');
    }
};