<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tos_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('learning_outcome_id')->nullable()->constrained()->onDelete('set null');
            $table->text('question');
            $table->json('options'); // A, B, C, D with text and is_correct flag
            $table->string('correct_answer', 1); // A, B, C, or D
            $table->text('explanation')->nullable();
            $table->string('cognitive_level', 50);
            $table->decimal('difficulty_b', 8, 4)->default(0); // IRT difficulty parameter
            $table->integer('time_estimate_seconds')->default(60);
            $table->timestamp('created_at');
            
            $table->index(['tos_item_id']);
            $table->index(['topic_id', 'cognitive_level']);
            $table->index(['difficulty_b']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_bank');
    }
};