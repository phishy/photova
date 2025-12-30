<?php

return [
    'auth' => [
        'enabled' => env('AUTH_ENABLED', true),
    ],

    'operations' => [
        'background-remove' => [
            'provider' => env('BACKGROUND_REMOVE_PROVIDER', 'replicate'),
            'fallback' => env('BACKGROUND_REMOVE_FALLBACK', 'removebg'),
        ],
        'upscale' => [
            'provider' => env('UPSCALE_PROVIDER', 'replicate'),
            // Note: upscale needs full-res input, no downscale
        ],
        'unblur' => [
            'provider' => env('UNBLUR_PROVIDER', 'replicate'),
        ],
        'colorize' => [
            'provider' => env('COLORIZE_PROVIDER', 'replicate'),
        ],
        'inpaint' => [
            'provider' => env('INPAINT_PROVIDER', 'replicate'),
        ],
        'restore' => [
            'provider' => env('RESTORE_PROVIDER', 'replicate'),
        ],
        'analyze' => [
            'provider' => env('ANALYZE_PROVIDER', 'replicate'),
            'preprocess' => [
                'downscale' => true,
                'max_width' => 1024,
                'max_height' => 1024,
            ],
        ],
    ],

    'providers' => [
        'replicate' => [
            'api_key' => env('REPLICATE_API_KEY'),
            'models' => [
                'background-remove' => 'cjwbw/rembg:fb8af171cfa1616ddcf1242c093f9c46bcada5ad4cf6f2fbe8b81b330ec5c003',
                'upscale' => 'nightmareai/real-esrgan:f121d640bd286e1fdc67f9799164c1d5be36ff74576ee11c803ae5b665dd46aa',
                'unblur' => 'tencentarc/gfpgan:0fbacf7afc6c144e5be9767cff80f25aff23e52b0708f17e20f9879b2f21516c',
                'colorize' => 'arielreplicate/deoldify_image:0da600fab0c45a66211339f1c16b71345d22f26ef5f067b17f4769b9bce92ae1',
                'inpaint' => 'stability-ai/stable-diffusion-inpainting:c28b92a7ecd66eee4aefcd8a94eb9e7f6c3805d5f06038165407fb5cb355ba67',
                'restore' => 'tencentarc/gfpgan:0fbacf7afc6c144e5be9767cff80f25aff23e52b0708f17e20f9879b2f21516c',
                'analyze' => 'salesforce/blip:2e1dddc8621f72155f24cf2e0adbde548458d3cab9f00c0139eea840d0ac4746',
            ],
        ],
        'fal' => [
            'api_key' => env('FAL_API_KEY'),
        ],
        'removebg' => [
            'api_key' => env('REMOVEBG_API_KEY'),
        ],
    ],
];
