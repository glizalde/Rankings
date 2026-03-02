<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('year');
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('ranking_id')->constrained('rankings');
            $table->foreignId('stage_id')->constrained('stages');
            $table->foreignId('catalog_id')->constrained('catalogs');

            $table->enum('status', ['draft','submitted','in_review','approved','rejected'])->default('draft');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['year','unit_id','ranking_id','stage_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('submissions');
    }
};