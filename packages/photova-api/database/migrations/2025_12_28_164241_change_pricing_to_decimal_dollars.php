<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_pricing', function (Blueprint $table) {
            $table->decimal('cost_per_unit_dollars', 12, 6)->default(0)->after('unit_type');
            $table->decimal('price_per_unit_dollars', 12, 6)->default(0)->after('cost_per_unit_dollars');
        });

        DB::statement('UPDATE provider_pricing SET cost_per_unit_dollars = cost_per_unit / 100.0, price_per_unit_dollars = price_per_unit / 100.0');

        Schema::table('provider_pricing', function (Blueprint $table) {
            $table->dropColumn(['cost_per_unit', 'price_per_unit']);
        });

        Schema::table('provider_pricing', function (Blueprint $table) {
            $table->renameColumn('cost_per_unit_dollars', 'cost_per_unit');
            $table->renameColumn('price_per_unit_dollars', 'price_per_unit');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->decimal('cost_dollars', 12, 6)->nullable()->after('model');
            $table->decimal('price_dollars', 12, 6)->nullable()->after('cost_dollars');
        });

        DB::statement('UPDATE usage_logs SET cost_dollars = cost_cents / 100.0, price_dollars = price_cents / 100.0 WHERE cost_cents IS NOT NULL');

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropColumn(['cost_cents', 'price_cents']);
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->renameColumn('cost_dollars', 'cost');
            $table->renameColumn('price_dollars', 'price');
        });

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->decimal('total_cost_dollars', 12, 6)->default(0)->after('total_latency_ms');
            $table->decimal('total_price_dollars', 12, 6)->default(0)->after('total_cost_dollars');
        });

        DB::statement('UPDATE usage_daily SET total_cost_dollars = total_cost_cents / 100.0, total_price_dollars = total_price_cents / 100.0');

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->dropColumn(['total_cost_cents', 'total_price_cents']);
        });

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->renameColumn('total_cost_dollars', 'total_cost');
            $table->renameColumn('total_price_dollars', 'total_price');
        });
    }

    public function down(): void
    {
        Schema::table('provider_pricing', function (Blueprint $table) {
            $table->integer('cost_per_unit_cents')->default(0);
            $table->integer('price_per_unit_cents')->default(0);
        });

        DB::statement('UPDATE provider_pricing SET cost_per_unit_cents = ROUND(cost_per_unit * 100), price_per_unit_cents = ROUND(price_per_unit * 100)');

        Schema::table('provider_pricing', function (Blueprint $table) {
            $table->dropColumn(['cost_per_unit', 'price_per_unit']);
        });

        Schema::table('provider_pricing', function (Blueprint $table) {
            $table->renameColumn('cost_per_unit_cents', 'cost_per_unit');
            $table->renameColumn('price_per_unit_cents', 'price_per_unit');
        });

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->integer('cost_cents')->nullable();
            $table->integer('price_cents')->nullable();
        });

        DB::statement('UPDATE usage_logs SET cost_cents = ROUND(cost * 100), price_cents = ROUND(price * 100) WHERE cost IS NOT NULL');

        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropColumn(['cost', 'price']);
        });

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->integer('total_cost_cents')->default(0);
            $table->integer('total_price_cents')->default(0);
        });

        DB::statement('UPDATE usage_daily SET total_cost_cents = ROUND(total_cost * 100), total_price_cents = ROUND(total_price * 100)');

        Schema::table('usage_daily', function (Blueprint $table) {
            $table->dropColumn(['total_cost', 'total_price']);
        });
    }
};
