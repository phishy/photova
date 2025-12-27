import { describe, it, expect, vi, beforeEach } from 'vitest';
import { AIManager, AIFeature } from './AIManager';
import { AIProvider, BackgroundRemovalResult, EnhanceResult, UpscaleResult, GenerativeFillResult } from './AIProvider';

class MockProvider extends AIProvider {
  get name(): string {
    return 'mock-provider';
  }

  removeBackground = vi.fn<[Blob], Promise<BackgroundRemovalResult>>().mockResolvedValue({
    foreground: new Blob(['foreground'], { type: 'image/png' }),
  });

  enhance = vi.fn<[Blob, { strength?: number } | undefined], Promise<EnhanceResult>>().mockResolvedValue({
    enhanced: new Blob(['enhanced'], { type: 'image/png' }),
  });

  upscale = vi.fn<[Blob, number], Promise<UpscaleResult>>().mockResolvedValue({
    upscaled: new Blob(['upscaled'], { type: 'image/png' }),
    scale: 2,
  });

  generativeFill = vi.fn<[Blob, Blob, string], Promise<GenerativeFillResult>>().mockResolvedValue({
    filled: new Blob(['filled'], { type: 'image/png' }),
  });
}

describe('AIManager', () => {
  let aiManager: AIManager;
  let mockProvider: MockProvider;

  beforeEach(() => {
    aiManager = new AIManager();
    mockProvider = new MockProvider();
  });

  describe('registerProvider', () => {
    it('should register a provider for a feature', () => {
      aiManager.registerProvider('backgroundRemoval', mockProvider);
      expect(aiManager.getProvider('backgroundRemoval')).toBe(mockProvider);
    });

    it('should allow registering different providers for different features', () => {
      const provider1 = new MockProvider();
      const provider2 = new MockProvider();

      aiManager.registerProvider('backgroundRemoval', provider1);
      aiManager.registerProvider('enhance', provider2);

      expect(aiManager.getProvider('backgroundRemoval')).toBe(provider1);
      expect(aiManager.getProvider('enhance')).toBe(provider2);
    });

    it('should overwrite existing provider for same feature', () => {
      const provider1 = new MockProvider();
      const provider2 = new MockProvider();

      aiManager.registerProvider('backgroundRemoval', provider1);
      aiManager.registerProvider('backgroundRemoval', provider2);

      expect(aiManager.getProvider('backgroundRemoval')).toBe(provider2);
    });
  });

  describe('setDefaultProvider', () => {
    it('should set a default provider', () => {
      aiManager.setDefaultProvider(mockProvider);
      expect(aiManager.getProvider('backgroundRemoval')).toBe(mockProvider);
      expect(aiManager.getProvider('enhance')).toBe(mockProvider);
    });

    it('should use feature-specific provider over default', () => {
      const specificProvider = new MockProvider();
      aiManager.setDefaultProvider(mockProvider);
      aiManager.registerProvider('backgroundRemoval', specificProvider);

      expect(aiManager.getProvider('backgroundRemoval')).toBe(specificProvider);
      expect(aiManager.getProvider('enhance')).toBe(mockProvider);
    });
  });

  describe('getProvider', () => {
    it('should return null when no provider registered', () => {
      expect(aiManager.getProvider('backgroundRemoval')).toBeNull();
    });

    it('should return feature-specific provider', () => {
      aiManager.registerProvider('enhance', mockProvider);
      expect(aiManager.getProvider('enhance')).toBe(mockProvider);
    });

    it('should fallback to default provider', () => {
      aiManager.setDefaultProvider(mockProvider);
      expect(aiManager.getProvider('upscale')).toBe(mockProvider);
    });
  });

  describe('isFeatureAvailable', () => {
    it('should return false when no provider available', () => {
      expect(aiManager.isFeatureAvailable('backgroundRemoval')).toBe(false);
    });

    it('should return true when feature provider is registered', () => {
      aiManager.registerProvider('backgroundRemoval', mockProvider);
      expect(aiManager.isFeatureAvailable('backgroundRemoval')).toBe(true);
    });

    it('should return true when default provider is set', () => {
      aiManager.setDefaultProvider(mockProvider);
      expect(aiManager.isFeatureAvailable('backgroundRemoval')).toBe(true);
      expect(aiManager.isFeatureAvailable('enhance')).toBe(true);
      expect(aiManager.isFeatureAvailable('upscale')).toBe(true);
      expect(aiManager.isFeatureAvailable('generativeFill')).toBe(true);
    });
  });

  describe('removeBackground', () => {
    it('should call provider removeBackground', async () => {
      aiManager.registerProvider('backgroundRemoval', mockProvider);
      const imageBlob = new Blob(['test'], { type: 'image/png' });

      const result = await aiManager.removeBackground(imageBlob);

      expect(mockProvider.removeBackground).toHaveBeenCalledWith(imageBlob);
      expect(result.foreground).toBeDefined();
    });

    it('should throw when no provider available', async () => {
      const imageBlob = new Blob(['test'], { type: 'image/png' });

      await expect(aiManager.removeBackground(imageBlob)).rejects.toThrow(
        'No provider available for background removal'
      );
    });

    it('should use default provider when no specific provider', async () => {
      aiManager.setDefaultProvider(mockProvider);
      const imageBlob = new Blob(['test'], { type: 'image/png' });

      await aiManager.removeBackground(imageBlob);

      expect(mockProvider.removeBackground).toHaveBeenCalledWith(imageBlob);
    });
  });

  describe('enhance', () => {
    it('should call provider enhance with options', async () => {
      aiManager.registerProvider('enhance', mockProvider);
      const imageBlob = new Blob(['test'], { type: 'image/png' });
      const options = { strength: 0.8 };

      const result = await aiManager.enhance(imageBlob, options);

      expect(mockProvider.enhance).toHaveBeenCalledWith(imageBlob, options);
      expect(result.enhanced).toBeDefined();
    });

    it('should call provider enhance without options', async () => {
      aiManager.registerProvider('enhance', mockProvider);
      const imageBlob = new Blob(['test'], { type: 'image/png' });

      await aiManager.enhance(imageBlob);

      expect(mockProvider.enhance).toHaveBeenCalledWith(imageBlob, undefined);
    });

    it('should throw when no provider available', async () => {
      const imageBlob = new Blob(['test'], { type: 'image/png' });

      await expect(aiManager.enhance(imageBlob)).rejects.toThrow(
        'No provider available for image enhancement'
      );
    });
  });

  describe('upscale', () => {
    it('should call provider upscale with scale factor', async () => {
      aiManager.registerProvider('upscale', mockProvider);
      const imageBlob = new Blob(['test'], { type: 'image/png' });
      const scale = 4;

      const result = await aiManager.upscale(imageBlob, scale);

      expect(mockProvider.upscale).toHaveBeenCalledWith(imageBlob, scale);
      expect(result.upscaled).toBeDefined();
      expect(result.scale).toBe(2);
    });

    it('should throw when no provider available', async () => {
      const imageBlob = new Blob(['test'], { type: 'image/png' });

      await expect(aiManager.upscale(imageBlob, 2)).rejects.toThrow(
        'No provider available for image upscaling'
      );
    });
  });

  describe('generativeFill', () => {
    it('should call provider generativeFill with all params', async () => {
      aiManager.registerProvider('generativeFill', mockProvider);
      const imageBlob = new Blob(['image'], { type: 'image/png' });
      const maskBlob = new Blob(['mask'], { type: 'image/png' });
      const prompt = 'fill with flowers';

      const result = await aiManager.generativeFill(imageBlob, maskBlob, prompt);

      expect(mockProvider.generativeFill).toHaveBeenCalledWith(imageBlob, maskBlob, prompt);
      expect(result.filled).toBeDefined();
    });

    it('should throw when no provider available', async () => {
      const imageBlob = new Blob(['image'], { type: 'image/png' });
      const maskBlob = new Blob(['mask'], { type: 'image/png' });

      await expect(aiManager.generativeFill(imageBlob, maskBlob, 'prompt')).rejects.toThrow(
        'No provider available for generative fill'
      );
    });
  });

  describe('all features', () => {
    const features: AIFeature[] = ['backgroundRemoval', 'enhance', 'upscale', 'generativeFill'];

    it.each(features)('should handle %s feature registration', (feature) => {
      aiManager.registerProvider(feature, mockProvider);
      expect(aiManager.isFeatureAvailable(feature)).toBe(true);
      expect(aiManager.getProvider(feature)).toBe(mockProvider);
    });
  });
});
