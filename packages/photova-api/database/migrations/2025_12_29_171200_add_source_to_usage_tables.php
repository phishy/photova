<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->string('source', 20)->default('api')->after('operation');
            $table->index('source');
        });

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->string('source', 20)->default('api')->after('operation');
        });

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date', 'operation']);
            $table->unique(['user_id', 'date', 'operation', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('usage_daily', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date', 'operation', 'source']);
            $table->unique(['user_id', 'date', 'operation']);
        });

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
