<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->decimal('correlation_score', 5, 2)->nullable()->after('processing_error');
            $table->decimal('correlation_threshold', 5, 2)->nullable()->after('correlation_score');
            $table->json('correlation_metadata')->nullable()->after('correlation_threshold');
            $table->timestamp('correlation_evaluated_at')->nullable()->after('correlation_metadata');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'correlation_score',
                'correlation_threshold',
                'correlation_metadata',
                'correlation_evaluated_at',
            ]);
        });
    }
};