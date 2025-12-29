<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignUuid('storage_bucket_id')
                ->nullable()
                ->after('bucket')
                ->constrained('storage_buckets')
                ->nullOnDelete();

            $table->index('storage_bucket_id');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storage_bucket_id');
        });
    }
};
