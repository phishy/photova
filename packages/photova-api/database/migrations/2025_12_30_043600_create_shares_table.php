<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 16)->unique();
            $table->string('name')->nullable();
            $table->jsonb('asset_ids');
            $table->timestamp('expires_at')->nullable();
            $table->string('password')->nullable();
            $table->boolean('allow_download')->default(true);
            $table->boolean('allow_zip')->default(true);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('slug');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
