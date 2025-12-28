<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('provider');           // e.g., 'replicate', 'fal', 'removebg'
            $table->string('operation');          // e.g., 'background-remove', 'upscale'
            $table->string('model')->nullable();  // e.g., 'cjwbw/rembg:fb8af171...'
            
            // Pricing (stored in cents to avoid float issues)
            $table->string('unit_type');          // 'per_image', 'per_second', 'per_megapixel'
            $table->integer('cost_per_unit');     // Provider cost in cents (what we pay)
            $table->integer('price_per_unit');    // Customer price in cents (what we charge)
            
            $table->string('currency', 3)->default('USD');
            $table->timestamp('effective_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();    // e.g., "Updated from Replicate docs 2025-01"
            
            $table->timestamps();

            $table->unique(['provider', 'operation', 'model', 'effective_at'], 'provider_pricing_unique');
            $table->index(['provider', 'operation', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_pricing');
    }
};
