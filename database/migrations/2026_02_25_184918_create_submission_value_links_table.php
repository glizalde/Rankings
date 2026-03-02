<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('submission_value_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_value_id')->constrained('submission_values')->cascadeOnDelete();
            $table->text('url');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('submission_value_links');
    }
};