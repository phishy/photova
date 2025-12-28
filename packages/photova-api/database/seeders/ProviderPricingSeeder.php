<?php

namespace Database\Seeders;

use App\Models\ProviderPricing;
use Illuminate\Database\Seeder;

class ProviderPricingSeeder extends Seeder
{
    public function run(): void
    {
        // Pricing Strategy: "Undercut 50%" - Half of Photoroom's pricing
        // Competitive analysis (Dec 2024):
        // - Photoroom: $0.02 BG removal, $0.10 AI features
        // - remove.bg: $0.07-$1.00 (volume dependent)
        // - imgix/Cloudinary: $0.20+ (requires $700+/mo plans)
        //
        // Our strategy: 25-60% cheaper than Photoroom while maintaining 73-95% margins
        $pricing = [
            [
                'provider' => 'replicate',
                'operation' => 'background-remove',
                'model' => 'cjwbw/rembg:fb8af171cfa1616ddcf1242c093f9c46bcada5ad4cf6f2fbe8b81b330ec5c003',
                'unit_type' => 'per_image',
                'cost_per_unit' => 0.0040,      // Replicate cost
                'price_per_unit' => 0.015,      // 73% margin, 25% cheaper than Photoroom ($0.02)
            ],
            [
                'provider' => 'replicate',
                'operation' => 'upscale',
                'model' => 'nightmareai/real-esrgan:f121d640bd286e1fdc67f9799164c1d5be36ff74576ee11c803ae5b665dd46aa',
                'unit_type' => 'per_image',
                'cost_per_unit' => 0.0025,      // Replicate cost
                'price_per_unit' => 0.05,       // 95% margin, 50% cheaper than Photoroom ($0.10)
            ],
            [
                'provider' => 'replicate',
                'operation' => 'unblur',
                'model' => 'tencentarc/gfpgan:0fbacf7afc6c144e5be9767cff80f25aff23e52b0708f17e20f9879b2f21516c',
                'unit_type' => 'per_image',
                'cost_per_unit' => 0.0027,      // Replicate cost
                'price_per_unit' => 0.05,       // 95% margin, 50% cheaper than Photoroom ($0.10)
            ],
            [
                'provider' => 'replicate',
                'operation' => 'colorize',
                'model' => 'arielreplicate/deoldify_image:0da600fab0c45a66211339f1c16b71345d22f26ef5f067b17f4769b9bce92ae1',
                'unit_type' => 'per_image',
                'cost_per_unit' => 0.0018,      // Replicate cost
                'price_per_unit' => 0.04,       // 95% margin, 60% cheaper than Photoroom ($0.10)
            ],
            [
                'provider' => 'replicate',
                'operation' => 'inpaint',
                'model' => 'stability-ai/stable-diffusion-inpainting:c28b92a7ecd66eee4aefcd8a94eb9e7f6c3805d5f06038165407fb5cb355ba67',
                'unit_type' => 'per_image',
                'cost_per_unit' => 0.0032,      // Replicate cost
                'price_per_unit' => 0.06,       // 95% margin, 40% cheaper than Photoroom ($0.10)
            ],
            [
                'provider' => 'replicate',
                'operation' => 'restore',
                'model' => 'tencentarc/gfpgan:0fbacf7afc6c144e5be9767cff80f25aff23e52b0708f17e20f9879b2f21516c',
                'unit_type' => 'per_image',
                'cost_per_unit' => 0.0027,      // Replicate cost
                'price_per_unit' => 0.05,       // 95% margin, 50% cheaper than Photoroom ($0.10)
            ],
            [
                'provider' => 'replicate',
                'operation' => 'analyze',
                'model' => 'salesforce/blip:2e1dddc8621f72155f24cf2e0adbde548458d3cab9f00c0139eea840d0ac4746',
                'unit_type' => 'per_image',
                'cost_per_unit' => 0.00051,     // Replicate cost
                'price_per_unit' => 0.005,      // 90% margin, unique feature
            ],
            [
                'provider' => 'removebg',
                'operation' => 'background-remove',
                'model' => null,
                'unit_type' => 'per_image',
                'cost_per_unit' => 0.20,        // remove.bg API cost
                'price_per_unit' => 0.25,       // 20% margin (fallback only)
            ],
        ];

        foreach ($pricing as $data) {
            ProviderPricing::updateOrCreate(
                [
                    'provider' => $data['provider'],
                    'operation' => $data['operation'],
                    'model' => $data['model'],
                ],
                array_merge($data, [
                    'effective_at' => now(),
                    'is_active' => true,
                ])
            );
        }
    }
}
