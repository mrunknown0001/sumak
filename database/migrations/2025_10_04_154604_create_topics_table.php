<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();
            
            $table->index(['document_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};