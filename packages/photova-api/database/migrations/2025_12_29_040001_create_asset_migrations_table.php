<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_migrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('from_bucket_id')->nullable()->constrained('storage_buckets')->nullOnDelete();
            $table->foreignUuid('to_bucket_id')->nullable()->constrained('storage_buckets')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->integer('total_assets')->default(0);
            $table->integer('processed_assets')->default(0);
            $table->integer('failed_assets')->default(0);
            $table->bigInteger('bytes_transferred')->default(0);
            $table->boolean('delete_source')->default(false);
            $table->jsonb('error_log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_migrations');
    }
};
