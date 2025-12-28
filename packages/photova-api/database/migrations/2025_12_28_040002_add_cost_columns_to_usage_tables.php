<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('operation');
            $table->string('model')->nullable()->after('provider');
            $table->integer('cost_cents')->default(0)->after('latency_ms');
            $table->integer('price_cents')->default(0)->after('cost_cents');
        });

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->bigInteger('total_cost_cents')->default(0)->after('total_latency_ms');
            $table->bigInteger('total_price_cents')->default(0)->after('total_cost_cents');
        });
    }

    public function down(): void
    {
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropColumn(['provider', 'model', 'cost_cents', 'price_cents']);
        });

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->dropColumn(['total_cost_cents', 'total_price_cents']);
        });
    }
};
