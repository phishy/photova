<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_analytics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('share_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 32); // view, download, thumbnail
            $table->string('source', 32); // share, direct, api
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 512)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city', 128)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['asset_id', 'created_at']);
            $table->index(['asset_id', 'event_type']);
            $table->index(['share_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_analytics');
    }
};
