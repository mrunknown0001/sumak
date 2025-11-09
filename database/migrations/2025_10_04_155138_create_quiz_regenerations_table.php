<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_regenerations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_item_id')->constrained('item_bank')->onDelete('cascade');
            $table->foreignId('regenerated_item_id')->constrained('item_bank')->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->integer('regeneration_count')->default(1); // 1, 2, or 3
            $table->boolean('maintains_equivalence')->default(true);
            $table->timestamp('regenerated_at');
            
            $table->index(['original_item_id', 'regeneration_count']);
            $table->index(['topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_regenerations');
    }
};