import { BaseProvider } from '../base.js';
import type { OperationType, OperationInput, OperationResult } from '../../operations/types.js';
import type { ProviderConfig } from '../../config/schema.js';

interface ModelConfig {
  version: string;
  inputKey: string;
  maskKey?: string;
  defaultOptions?: Record<string, unknown>;
}

const REPLICATE_MODELS: Record<string, ModelConfig> = {
  'background-remove': { version: 'fb8af171cfa1616ddcf1242c093f9c46bcada5ad4cf6f2fbe8b81b330ec5c003', inputKey: 'image' },
  'upscale': { version: 'f121d640bd286e1fdc67f9799164c1d5be36ff74576ee11c803ae5b665dd46aa', inputKey: 'image', defaultOptions: { scale: 4 } },
  'unblur': { version: 'f121d640bd286e1fdc67f9799164c1d5be36ff74576ee11c803ae5b665dd46aa', inputKey: 'image', defaultOptions: { scale: 2, face_enhance: true } },
  'face-restore': { version: 'f121d640bd286e1fdc67f9799164c1d5be36ff74576ee11c803ae5b665dd46aa', inputKey: 'image', defaultOptions: { scale: 2, face_enhance: true } },
  'colorize': { version: '0da600fab0c45a66211339f1c16b71345d22f26ef5fea3dca1bb90bb5711e950', inputKey: 'input_image', defaultOptions: { model_name: 'Artistic', render_factor: 35 } },
  'inpaint': { version: '40e67426e1bf78199d78b36580389fbbdcb4c9cdc2bc2b489e99d713f167b3c5', inputKey: 'image', maskKey: 'mask' },
};

export class ReplicateProvider extends BaseProvider {
  readonly name = 'replicate';
  readonly supportedOperations: OperationType[] = [
    'background-remove',
    'upscale', 
    'unblur',
    'face-restore',
    'colorize',
    'inpaint',
  ];

  private apiKey: string;

  constructor(config: ProviderConfig) {
    super(config);
    if (!config.api_key) {
      throw new Error('Replicate API key is required');
    }
    this.apiKey = config.api_key;
  }

  async execute(operation: OperationType, input: OperationInput): Promise<OperationResult> {
    const modelConfig = REPLICATE_MODELS[operation];
    if (!modelConfig) {
      throw new Error(`Replicate does not support operation: ${operation}`);
    }

    const startTime = Date.now();
    
    const base64Image = input.image.toString('base64');
    const dataUri = `data:${input.mimeType};base64,${base64Image}`;

    const modelInput: Record<string, unknown> = { 
      [modelConfig.inputKey]: dataUri, 
      ...modelConfig.defaultOptions, 
    };

    if (modelConfig.maskKey && input.options?.mask) {
      modelInput[modelConfig.maskKey] = input.options.mask;
    }
    
    if (input.options) {
      for (const [key, value] of Object.entries(input.options)) {
        if (key !== 'mask') {
          modelInput[key] = value;
        }
      }
    }

    const response = await fetch('https://api.replicate.com/v1/predictions', {
      method: 'POST',
      headers: {
        'Authorization': `Token ${this.apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        version: modelConfig.version,
        input: modelInput,
      }),
    });

    if (!response.ok) {
      const errorBody = await response.text();
      throw new Error(`Replicate API error: ${response.status} - ${errorBody}`);
    }

    const prediction = await response.json() as { id: string; status: string; output?: string | string[] };
    const result = await this.pollForResult(prediction.id);

    const outputUrl = Array.isArray(result.output) ? result.output[0] : result.output;
    const imageResponse = await fetch(outputUrl);
    const imageBuffer = Buffer.from(await imageResponse.arrayBuffer());

    return {
      image: imageBuffer,
      mimeType: 'image/png',
      metadata: {
        provider: this.name,
        model: modelConfig.version,
        processingTime: Date.now() - startTime,
      },
    };
  }

  private async pollForResult(predictionId: string, maxAttempts = 60): Promise<{ output: string | string[] }> {
    for (let i = 0; i < maxAttempts; i++) {
      const response = await fetch(`https://api.replicate.com/v1/predictions/${predictionId}`, {
        headers: { 'Authorization': `Token ${this.apiKey}` },
      });

      const prediction = await response.json() as { status: string; output?: string | string[]; error?: string };

      if (prediction.status === 'succeeded' && prediction.output) {
        return { output: prediction.output };
      }
      if (prediction.status === 'failed') {
        throw new Error(`Replicate prediction failed: ${prediction.error}`);
      }

      await new Promise(resolve => setTimeout(resolve, 1000));
    }

    throw new Error('Replicate prediction timed out');
  }
}
