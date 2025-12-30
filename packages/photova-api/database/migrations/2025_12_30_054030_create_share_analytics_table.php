<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('share_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 20);
            $table->uuid('asset_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 512)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['share_id', 'created_at']);
            $table->index(['share_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_analytics');
    }
};
