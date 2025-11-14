<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_of_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->integer('total_items')->default(20);
            $table->decimal('lots_percentage', 5, 2)->default(100.00); // LOTS focused
            $table->json('cognitive_level_distribution')->nullable();
            $table->text('assessment_focus')->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['document_id']);
        });

        Schema::create('tos_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tos_id')->constrained('table_of_specifications')->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('learning_outcome_id')->nullable()->constrained()->onDelete('set null');
            $table->string('cognitive_level', 50); // remember, understand, apply
            $table->string('bloom_category', 50); // knowledge, comprehension, application
            $table->integer('num_items')->default(0);
            $table->decimal('weight_percentage', 5, 2)->default(0);
            $table->json('sample_indicators')->nullable();
            $table->timestamps();
            
            $table->index(['tos_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tos_items');
        Schema::dropIfExists('table_of_specifications');
    }
};