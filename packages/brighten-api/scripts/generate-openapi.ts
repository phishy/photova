#!/usr/bin/env tsx
/**
 * Generate static OpenAPI spec for documentation.
 * This generates a complete spec with all available operations,
 * regardless of local config.
 */

import { writeFileSync } from 'fs';
import { join } from 'path';

const ALL_OPERATIONS = [
  'background-remove',
  'unblur',
  'colorize',
  'inpaint',
  'restore',
] as const;

function formatOperationName(op: string): string {
  return op
    .split('-')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function getOperationDescription(op: string): string {
  const descriptions: Record<string, string> = {
    'background-remove': 'Remove the background from an image, leaving only the foreground subject with transparency.',
    'unblur': 'Enhance and sharpen a blurry image using AI upscaling with face enhancement.',
    'colorize': 'Add color to black and white images using DeOldify.',
    'inpaint': 'Remove objects from an image by painting a mask over areas to fill. Requires a `mask` parameter in options (base64 image where white = areas to remove).',
    'restore': 'Restore old or damaged photos using FLUX Kontext. Fixes scratches, damage, and can colorize old photos.',
  };
  return descriptions[op] || `Perform ${formatOperationName(op)} operation on an image.`;
}

function generateSpec() {
  return {
    openapi: '3.0.3',
    info: {
      title: 'Brighten API',
      description: `Unified media processing API with configurable AI backends.

Brighten API provides a simple, consistent interface for AI-powered image processing operations. Configure your preferred backend providers (Replicate, fal.ai, remove.bg, etc.) and route operations through a single API.

## Features

- **Provider Agnostic**: Swap AI backends without changing your code
- **Fallback Support**: Automatic failover to backup providers  
- **Unified Format**: Consistent request/response format across all operations
- **Base64 I/O**: Simple base64 image encoding for easy integration

## Getting Started

1. Clone the repository and navigate to \`packages/brighten-api\`
2. Copy \`config.example.yaml\` to \`config.yaml\`
3. Add your API keys for the providers you want to use
4. Run \`npm run dev\` to start the server

See the [GitHub repository](https://github.com/phishy/brighten/tree/main/packages/brighten-api) for detailed setup instructions.`,
      version: '0.1.0',
      license: {
        name: 'BUSL-1.1',
        url: 'https://github.com/phishy/brighten/blob/main/LICENSE',
      },
      contact: {
        name: 'GitHub',
        url: 'https://github.com/phishy/brighten',
      },
    },
    servers: [
      {
        url: 'http://localhost:3001',
        description: 'Local development server',
      },
    ],
    tags: [
      {
        name: 'Operations',
        description: 'AI-powered image processing operations',
      },
      {
        name: 'System',
        description: 'Health checks and server information',
      },
    ],
    paths: {
      '/api/health': {
        get: {
          summary: 'Health check',
          description: 'Returns the health status of the API. Use this endpoint to verify the server is running.',
          operationId: 'healthCheck',
          tags: ['System'],
          responses: {
            '200': {
              description: 'API is healthy',
              content: {
                'application/json': {
                  schema: {
                    type: 'object',
                    properties: {
                      status: { type: 'string', example: 'ok' },
                    },
                  },
                },
              },
            },
          },
        },
      },
      '/api/operations': {
        get: {
          summary: 'List available operations',
          description: 'Returns a list of all configured operations and their assigned providers. Only operations configured in your `config.yaml` will be available.',
          operationId: 'listOperations',
          tags: ['System'],
          responses: {
            '200': {
              description: 'List of operations',
              content: {
                'application/json': {
                  schema: {
                    type: 'object',
                    properties: {
                      operations: {
                        type: 'array',
                        items: {
                          type: 'object',
                          properties: {
                            name: { type: 'string', example: 'background-remove' },
                            provider: { type: 'string', example: 'replicate' },
                          },
                        },
                      },
                    },
                  },
                  example: {
                    operations: [
                      { name: 'background-remove', provider: 'replicate' },
                      { name: 'unblur', provider: 'replicate' },
                    ],
                  },
                },
              },
            },
          },
        },
      },
      ...Object.fromEntries(
        ALL_OPERATIONS.map((op) => [
          `/api/v1/${op}`,
          {
            post: {
              summary: formatOperationName(op),
              description: getOperationDescription(op),
              operationId: op.replace(/-/g, '_'),
              tags: ['Operations'],
              requestBody: {
                required: true,
                content: {
                  'application/json': {
                    schema: {
                      $ref: '#/components/schemas/ImageInput',
                    },
                    example: {
                      image: 'data:image/png;base64,iVBORw0KGgo...',
                      options: {},
                    },
                  },
                },
              },
              responses: {
                '200': {
                  description: 'Operation successful',
                  content: {
                    'application/json': {
                      schema: {
                        $ref: '#/components/schemas/ImageOutput',
                      },
                    },
                  },
                },
                '400': {
                  description: 'Bad request - missing or invalid image',
                  content: {
                    'application/json': {
                      schema: {
                        $ref: '#/components/schemas/Error',
                      },
                      example: {
                        error: 'Image is required',
                      },
                    },
                  },
                },
                '500': {
                  description: 'Internal server error - operation failed',
                  content: {
                    'application/json': {
                      schema: {
                        $ref: '#/components/schemas/Error',
                      },
                      example: {
                        error: 'Provider timeout after 30000ms',
                      },
                    },
                  },
                },
              },
            },
          },
        ])
      ),
    },
    components: {
      schemas: {
        ImageInput: {
          type: 'object',
          required: ['image'],
          properties: {
            image: {
              type: 'string',
              description: 'Base64-encoded image. Can be a data URI (recommended) or raw base64 string.',
              example: 'data:image/png;base64,iVBORw0KGgo...',
            },
            options: {
              type: 'object',
              description: 'Operation-specific options. See individual operation docs for available options.',
              additionalProperties: true,
              example: {},
            },
          },
        },
        ImageOutput: {
          type: 'object',
          properties: {
            image: {
              type: 'string',
              description: 'Processed image as a data URI',
              example: 'data:image/png;base64,iVBORw0KGgo...',
            },
            metadata: {
              type: 'object',
              description: 'Information about how the operation was processed',
              properties: {
                provider: { 
                  type: 'string', 
                  description: 'The provider that processed the request',
                  example: 'replicate',
                },
                model: { 
                  type: 'string', 
                  description: 'The specific model used',
                  example: 'cjwbw/rembg',
                },
                processingTime: { 
                  type: 'number', 
                  description: 'Processing time in milliseconds',
                  example: 2340,
                },
              },
            },
          },
        },
        Error: {
          type: 'object',
          properties: {
            error: { 
              type: 'string',
              description: 'Error message describing what went wrong',
            },
          },
        },
      },
    },
  };
}

const spec = generateSpec();
const outputPath = process.argv[2] || join(__dirname, '..', 'openapi.json');

writeFileSync(outputPath, JSON.stringify(spec, null, 2));
console.log(`OpenAPI spec written to ${outputPath}`);
