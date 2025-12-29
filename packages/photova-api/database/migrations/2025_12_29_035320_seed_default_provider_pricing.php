<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pricing = [
            [
                'provider' => 'replicate',
                'operation' => 'background-remove',
                'model' => 'cjwbw/rembg:fb8af171cfa1616ddcf1242c093f9c46bcada5ad4cf6f2fbe8b81b330ec5c003',
                'unit_type' => 'per_image',
                'cost_per_unit' => '0.004000',
                'price_per_unit' => '0.015000',
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'Replicate T4 GPU ~2s avg @ $0.000225/s',
            ],
            [
                'provider' => 'replicate',
                'operation' => 'upscale',
                'model' => 'nightmareai/real-esrgan:f121d640bd286e1fdc67f9799164c1d5be36ff74576ee11c803ae5b665dd46aa',
                'unit_type' => 'per_image',
                'cost_per_unit' => '0.002500',
                'price_per_unit' => '0.050000',
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'Replicate T4 GPU ~5s avg @ $0.000225/s',
            ],
            [
                'provider' => 'replicate',
                'operation' => 'unblur',
                'model' => 'tencentarc/gfpgan:0fbacf7afc6c144e5be9767cff80f25aff23e52b0708f17e20f9879b2f21516c',
                'unit_type' => 'per_image',
                'cost_per_unit' => '0.002700',
                'price_per_unit' => '0.050000',
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'Replicate T4 GPU ~3s avg @ $0.000225/s',
            ],
            [
                'provider' => 'replicate',
                'operation' => 'colorize',
                'model' => 'arielreplicate/deoldify_image:0da600fab0c45a66211339f1c16b71345d22f26ef5f067b17f4769b9bce92ae1',
                'unit_type' => 'per_image',
                'cost_per_unit' => '0.001800',
                'price_per_unit' => '0.040000',
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'Replicate T4 GPU ~4s avg @ $0.000225/s',
            ],
            [
                'provider' => 'replicate',
                'operation' => 'inpaint',
                'model' => 'stability-ai/stable-diffusion-inpainting:c28b92a7ecd66eee4aefcd8a94eb9e7f6c3805d5f06038165407fb5cb355ba67',
                'unit_type' => 'per_image',
                'cost_per_unit' => '0.003200',
                'price_per_unit' => '0.060000',
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'Replicate A100 GPU ~10s avg @ $0.0014/s',
            ],
            [
                'provider' => 'replicate',
                'operation' => 'restore',
                'model' => 'tencentarc/gfpgan:0fbacf7afc6c144e5be9767cff80f25aff23e52b0708f17e20f9879b2f21516c',
                'unit_type' => 'per_image',
                'cost_per_unit' => '0.002700',
                'price_per_unit' => '0.050000',
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'Replicate T4 GPU ~3s avg @ $0.000225/s',
            ],
            [
                'provider' => 'replicate',
                'operation' => 'analyze',
                'model' => 'salesforce/blip:2e1dddc8621f72155f24cf2e0adbde548458d3cab9f00c0139eea840d0ac4746',
                'unit_type' => 'per_image',
                'cost_per_unit' => '0.000510',
                'price_per_unit' => '0.005000',
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'Replicate T4 GPU ~1s avg @ $0.000225/s',
            ],
            [
                'provider' => 'removebg',
                'operation' => 'background-remove',
                'model' => null,
                'unit_type' => 'per_image',
                'cost_per_unit' => '0.200000',
                'price_per_unit' => '0.250000',
                'currency' => 'USD',
                'is_active' => true,
                'notes' => 'remove.bg API credit-based pricing',
            ],
        ];

        $now = now();

        foreach ($pricing as $record) {
            // Check if this provider+operation+model combo already exists
            $exists = DB::table('provider_pricing')
                ->where('provider', $record['provider'])
                ->where('operation', $record['operation'])
                ->where(function ($query) use ($record) {
                    if ($record['model'] === null) {
                        $query->whereNull('model');
                    } else {
                        $query->where('model', $record['model']);
                    }
                })
                ->exists();

            if (!$exists) {
                DB::table('provider_pricing')->insert(array_merge($record, [
                    'effective_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete pricing data on rollback - it may have been modified
    }
};
