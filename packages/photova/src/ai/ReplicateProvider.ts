import {
  AIProvider,
  type AIProviderOptions,
  type BackgroundRemovalResult,
  type EnhanceResult,
  type UpscaleResult,
  type GenerativeFillResult,
} from './AIProvider';

export interface ReplicateOptions extends AIProviderOptions {
  pollingInterval?: number;
  maxPollingTime?: number;
}

export class ReplicateProvider extends AIProvider {
  private readonly baseUrl = 'https://api.replicate.com/v1';
  private pollingInterval: number;
  private maxPollingTime: number;

  private readonly models = {
    backgroundRemoval: 'cjwbw/rembg:fb8af171cfa1616ddcf1242c093f9c46bcada5ad4cf6f2fbe8b81b330ec5c003',
    enhance: 'nightmareai/real-esrgan:42fed1c4974146d4d2414e2be2c5277c7fcf05fcc3a73abf41610695738c1d7b',
    upscale: 'nightmareai/real-esrgan:42fed1c4974146d4d2414e2be2c5277c7fcf05fcc3a73abf41610695738c1d7b',
    generativeFill: 'stability-ai/stable-diffusion-inpainting:c11bac58203367db93a3c552bd49a25a5418458ddffb7e90dae55780765e26d6',
  };

  constructor(options: ReplicateOptions = {}) {
    super(options);
    this.pollingInterval = options.pollingInterval || 1000;
    this.maxPollingTime = options.maxPollingTime || 60000;
  }

  get name(): string {
    return 'Replicate';
  }

  async removeBackground(image: Blob): Promise<BackgroundRemovalResult> {
    const base64 = await this.blobToBase64(image);
    const output = await this.runModel(this.models.backgroundRemoval, {
      image: `data:image/png;base64,${base64}`,
    });

    const foreground = await this.fetchImageAsBlob(output);
    return { foreground };
  }

  async enhance(image: Blob, options?: { strength?: number }): Promise<EnhanceResult> {
    const base64 = await this.blobToBase64(image);
    const output = await this.runModel(this.models.enhance, {
      image: `data:image/png;base64,${base64}`,
      scale: options?.strength ? Math.min(4, Math.max(2, Math.round(options.strength * 4))) : 2,
      face_enhance: true,
    });

    const enhanced = await this.fetchImageAsBlob(output);
    return { enhanced };
  }

  async upscale(image: Blob, scale: number): Promise<UpscaleResult> {
    const base64 = await this.blobToBase64(image);
    const clampedScale = Math.min(4, Math.max(2, Math.round(scale)));

    const output = await this.runModel(this.models.upscale, {
      image: `data:image/png;base64,${base64}`,
      scale: clampedScale,
    });

    const upscaled = await this.fetchImageAsBlob(output);
    return { upscaled, scale: clampedScale };
  }

  async generativeFill(
    image: Blob,
    mask: Blob,
    prompt: string
  ): Promise<GenerativeFillResult> {
    const imageBase64 = await this.blobToBase64(image);
    const maskBase64 = await this.blobToBase64(mask);

    const output = await this.runModel(this.models.generativeFill, {
      prompt,
      image: `data:image/png;base64,${imageBase64}`,
      mask: `data:image/png;base64,${maskBase64}`,
      num_outputs: 1,
    });

    const outputUrl = Array.isArray(output) ? output[0] : output;
    const filled = await this.fetchImageAsBlob(outputUrl);
    return { filled };
  }

  private async runModel(model: string, input: Record<string, unknown>): Promise<string> {
    if (!this.options.apiKey) {
      throw new Error('API key is required for Replicate');
    }

    const response = await this.fetchWithTimeout(`${this.baseUrl}/predictions`, {
      method: 'POST',
      headers: {
        Authorization: `Token ${this.options.apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ version: model.split(':')[1], input }),
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ detail: 'Unknown error' }));
      throw new Error(`Replicate error: ${error.detail || 'Unknown error'}`);
    }

    const prediction = await response.json();
    return this.pollForResult(prediction.id);
  }

  private async pollForResult(predictionId: string): Promise<string> {
    const startTime = Date.now();

    while (Date.now() - startTime < this.maxPollingTime) {
      const response = await this.fetchWithTimeout(
        `${this.baseUrl}/predictions/${predictionId}`,
        {
          headers: {
            Authorization: `Token ${this.options.apiKey}`,
          },
        }
      );

      if (!response.ok) {
        throw new Error('Failed to poll prediction status');
      }

      const prediction = await response.json();

      if (prediction.status === 'succeeded') {
        return prediction.output;
      }

      if (prediction.status === 'failed') {
        throw new Error(`Prediction failed: ${prediction.error || 'Unknown error'}`);
      }

      if (prediction.status === 'canceled') {
        throw new Error('Prediction was canceled');
      }

      await new Promise((resolve) => setTimeout(resolve, this.pollingInterval));
    }

    throw new Error('Prediction timed out');
  }

  private async fetchImageAsBlob(url: string): Promise<Blob> {
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error('Failed to fetch image');
    }
    return response.blob();
  }
}
