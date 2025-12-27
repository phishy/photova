<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function operations(): JsonResponse
    {
        $operations = [];
        
        foreach (config('photova.operations') as $name => $config) {
            $operations[] = [
                'name' => $name,
                'provider' => $config['provider'],
                'fallback' => $config['fallback'] ?? null,
            ];
        }

        return response()->json(['operations' => $operations]);
    }

    public function openapi(): JsonResponse
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Photova API',
                'version' => '2.0.0',
                'description' => 'Unified media processing API with configurable backends',
            ],
            'servers' => [
                ['url' => config('app.url') . '/api'],
            ],
            'paths' => $this->generatePaths(),
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                    ],
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                ],
            ],
        ];

        return response()->json($spec);
    }

    private function generatePaths(): array
    {
        $paths = [
            '/health' => [
                'get' => [
                    'summary' => 'Health check',
                    'responses' => [
                        '200' => ['description' => 'Service is healthy'],
                    ],
                ],
            ],
            '/operations' => [
                'get' => [
                    'summary' => 'List available operations',
                    'responses' => [
                        '200' => ['description' => 'List of operations'],
                    ],
                ],
            ],
        ];

        foreach (array_keys(config('photova.operations')) as $operation) {
            $paths["/v1/{$operation}"] = [
                'post' => [
                    'summary' => ucfirst(str_replace('-', ' ', $operation)),
                    'security' => [['ApiKeyAuth' => []], ['BearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'image' => ['type' => 'string', 'description' => 'Base64 encoded image or data URI'],
                                        'options' => ['type' => 'object'],
                                    ],
                                    'required' => ['image'],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Processed image'],
                        '401' => ['description' => 'Unauthorized'],
                        '500' => ['description' => 'Processing error'],
                    ],
                ],
            ];
        }

        return $paths;
    }
}
