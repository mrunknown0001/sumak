<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('table_of_specifications', function (Blueprint $table) {
            $table->string('term')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_of_specifications', function (Blueprint $table) {
            $table->enum('term', ['midterm', 'final'])->nullable()->change();
        });
    }
};
