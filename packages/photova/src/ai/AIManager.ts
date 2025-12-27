import type { AIProvider, BackgroundRemovalResult, EnhanceResult, UpscaleResult, GenerativeFillResult } from './AIProvider';

export type AIFeature = 'backgroundRemoval' | 'enhance' | 'upscale' | 'generativeFill';

export class AIManager {
  private providers: Map<AIFeature, AIProvider> = new Map();
  private defaultProvider: AIProvider | null = null;

  registerProvider(feature: AIFeature, provider: AIProvider): void {
    this.providers.set(feature, provider);
  }

  setDefaultProvider(provider: AIProvider): void {
    this.defaultProvider = provider;
  }

  getProvider(feature: AIFeature): AIProvider | null {
    return this.providers.get(feature) || this.defaultProvider;
  }

  isFeatureAvailable(feature: AIFeature): boolean {
    return this.providers.has(feature) || this.defaultProvider !== null;
  }

  async removeBackground(image: Blob): Promise<BackgroundRemovalResult> {
    const provider = this.getProvider('backgroundRemoval');
    if (!provider) {
      throw new Error('No provider available for background removal');
    }
    return provider.removeBackground(image);
  }

  async enhance(image: Blob, options?: { strength?: number }): Promise<EnhanceResult> {
    const provider = this.getProvider('enhance');
    if (!provider) {
      throw new Error('No provider available for image enhancement');
    }
    return provider.enhance(image, options);
  }

  async upscale(image: Blob, scale: number): Promise<UpscaleResult> {
    const provider = this.getProvider('upscale');
    if (!provider) {
      throw new Error('No provider available for image upscaling');
    }
    return provider.upscale(image, scale);
  }

  async generativeFill(
    image: Blob,
    mask: Blob,
    prompt: string
  ): Promise<GenerativeFillResult> {
    const provider = this.getProvider('generativeFill');
    if (!provider) {
      throw new Error('No provider available for generative fill');
    }
    return provider.generativeFill(image, mask, prompt);
  }
}
