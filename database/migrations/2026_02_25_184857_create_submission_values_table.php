<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('submission_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('variable_id')->constrained('variables')->cascadeOnDelete();

            $table->decimal('value_number', 18, 4)->nullable();
            $table->text('value_text')->nullable();
            $table->boolean('value_bool')->nullable();

            $table->string('word_path')->nullable(); // 1 word

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['submission_id','variable_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('submission_values');
    }
};