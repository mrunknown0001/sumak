<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->decimal('theta', 8, 4)->default(0); // IRT ability parameter
            $table->integer('attempts_count')->default(0);
            $table->timestamp('last_updated');
            
            $table->unique(['user_id', 'topic_id']);
            $table->index(['topic_id', 'theta']);
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('feedback_text');
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('next_steps')->nullable();
            $table->text('motivational_message')->nullable();
            $table->timestamp('generated_at');
            
            $table->index(['quiz_attempt_id']);
            $table->index(['user_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('student_abilities');
    }
};