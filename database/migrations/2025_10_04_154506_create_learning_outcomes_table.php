<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obtl_document_id')->constrained()->onDelete('cascade');
            $table->string('outcome_code', 50);
            $table->text('description');
            $table->string('bloom_level', 50); // remember, understand, apply, etc.
            $table->timestamps();
            
            $table->index(['obtl_document_id']);
        });

        Schema::create('sub_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->string('bloom_level', 50);
            $table->timestamps();
            
            $table->index(['learning_outcome_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_outcomes');
        Schema::dropIfExists('learning_outcomes');
    }
};