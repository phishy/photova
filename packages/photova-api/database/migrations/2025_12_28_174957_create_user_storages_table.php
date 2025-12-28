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
        Schema::create('user_storages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Display name (e.g., "My S3 Bucket")
            $table->string('driver'); // s3, gcs, r2, azure, ftp, sftp
            $table->text('config'); // Encrypted JSON config (keys, secrets, bucket, etc.)
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_storages');
    }
};
