<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedInteger('view_count')->default(0)->after('metadata');
            $table->unsignedInteger('download_count')->default(0)->after('view_count');
            $table->timestamp('last_viewed_at')->nullable()->after('download_count');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['view_count', 'download_count', 'last_viewed_at']);
        });
    }
};
