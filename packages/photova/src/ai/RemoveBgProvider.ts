import {
  AIProvider,
  type AIProviderOptions,
  type BackgroundRemovalResult,
  type EnhanceResult,
  type UpscaleResult,
  type GenerativeFillResult,
} from './AIProvider';

export interface RemoveBgOptions extends AIProviderOptions {
  size?: 'preview' | 'full' | 'auto';
  type?: 'auto' | 'person' | 'product' | 'car';
  format?: 'png' | 'jpg' | 'zip';
  bgColor?: string;
}

export class RemoveBgProvider extends AIProvider {
  private readonly baseUrl = 'https://api.remove.bg/v1.0';
  private removeBgOptions: RemoveBgOptions;

  constructor(options: RemoveBgOptions = {}) {
    super(options);
    this.removeBgOptions = options;
  }

  get name(): string {
    return 'remove.bg';
  }

  async removeBackground(image: Blob): Promise<BackgroundRemovalResult> {
    if (!this.options.apiKey) {
      throw new Error('API key is required for remove.bg');
    }

    const formData = new FormData();
    formData.append('image_file', image);
    formData.append('size', this.removeBgOptions.size || 'auto');
    formData.append('type', this.removeBgOptions.type || 'auto');
    formData.append('format', this.removeBgOptions.format || 'png');

    if (this.removeBgOptions.bgColor) {
      formData.append('bg_color', this.removeBgOptions.bgColor);
    }

    const response = await this.fetchWithTimeout(`${this.baseUrl}/removebg`, {
      method: 'POST',
      headers: {
        'X-Api-Key': this.options.apiKey,
      },
      body: formData,
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ errors: [{ title: 'Unknown error' }] }));
      throw new Error(`remove.bg error: ${error.errors?.[0]?.title || 'Unknown error'}`);
    }

    const foreground = await response.blob();
    return { foreground };
  }

  async enhance(_image: Blob, _options?: { strength?: number }): Promise<EnhanceResult> {
    throw new Error('Enhance is not supported by remove.bg');
  }

  async upscale(_image: Blob, _scale: number): Promise<UpscaleResult> {
    throw new Error('Upscale is not supported by remove.bg');
  }

  async generativeFill(
    _image: Blob,
    _mask: Blob,
    _prompt: string
  ): Promise<GenerativeFillResult> {
    throw new Error('Generative fill is not supported by remove.bg');
  }
}
