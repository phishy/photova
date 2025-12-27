<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bucket')->default('assets');
            $table->string('storage_key');
            $table->string('filename');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'bucket']);
            $table->index('storage_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
