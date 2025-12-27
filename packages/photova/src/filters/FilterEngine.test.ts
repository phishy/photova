import { describe, it, expect, vi, beforeAll } from 'vitest';
import { FilterEngine } from './FilterEngine';

class MockImageData {
  data: Uint8ClampedArray;
  width: number;
  height: number;

  constructor(data: Uint8ClampedArray, width: number, height?: number) {
    this.data = data;
    this.width = width;
    this.height = height ?? (data.length / 4 / width);
  }
}

beforeAll(() => {
  if (typeof globalThis.ImageData === 'undefined') {
    (globalThis as any).ImageData = MockImageData;
  }
});

function createTestImageData(width = 2, height = 2, color = [128, 128, 128, 255]): ImageData {
  const data = new Uint8ClampedArray(width * height * 4);
  for (let i = 0; i < data.length; i += 4) {
    data[i] = color[0];
    data[i + 1] = color[1];
    data[i + 2] = color[2];
    data[i + 3] = color[3];
  }
  return new (globalThis as any).ImageData(data, width, height);
}

describe('FilterEngine', () => {
  describe('initialization', () => {
    it('should create instance with built-in filters', () => {
      const engine = new FilterEngine();
      expect(engine).toBeInstanceOf(FilterEngine);
    });

    it('should have built-in presets', () => {
      const engine = new FilterEngine();
      const presets = engine.getPresets();
      expect(presets.length).toBeGreaterThan(0);
    });

    it('should include expected presets', () => {
      const engine = new FilterEngine();
      const presets = engine.getPresets();
      const presetIds = presets.map(p => p.id);
      
      expect(presetIds).toContain('vintage');
      expect(presetIds).toContain('noir');
      expect(presetIds).toContain('warm');
      expect(presetIds).toContain('cool');
      expect(presetIds).toContain('vivid');
    });
  });

  describe('brightness filter', () => {
    it('should increase brightness with positive value', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [100, 100, 100, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'brightness',
        value: 0.2,
        enabled: true,
      });

      expect(result.data[0]).toBeGreaterThan(100);
      expect(result.data[1]).toBeGreaterThan(100);
      expect(result.data[2]).toBeGreaterThan(100);
    });

    it('should decrease brightness with negative value', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [100, 100, 100, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'brightness',
        value: -0.2,
        enabled: true,
      });

      expect(result.data[0]).toBeLessThan(100);
      expect(result.data[1]).toBeLessThan(100);
      expect(result.data[2]).toBeLessThan(100);
    });

    it('should not modify alpha channel', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [100, 100, 100, 200]);
      
      const result = engine.applyFilter(imageData, {
        type: 'brightness',
        value: 0.5,
        enabled: true,
      });

      expect(result.data[3]).toBe(200);
    });
  });

  describe('contrast filter', () => {
    it('should increase contrast with positive value', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [100, 100, 100, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'contrast',
        value: 0.5,
        enabled: true,
      });

      expect(result.data[0]).not.toBe(100);
    });
  });

  describe('grayscale filter', () => {
    it('should convert to grayscale with value 1', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [255, 0, 0, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'grayscale',
        value: 1,
        enabled: true,
      });

      expect(result.data[0]).toBe(result.data[1]);
      expect(result.data[1]).toBe(result.data[2]);
    });

    it('should partially convert with value 0.5', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [255, 0, 0, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'grayscale',
        value: 0.5,
        enabled: true,
      });

      expect(result.data[0]).toBeGreaterThan(result.data[1]);
    });
  });

  describe('sepia filter', () => {
    it('should apply sepia tone', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [128, 128, 128, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'sepia',
        value: 1,
        enabled: true,
      });

      expect(result.data[0]).toBeGreaterThan(result.data[2]);
    });
  });

  describe('invert filter', () => {
    it('should invert colors', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [0, 0, 0, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'invert',
        value: 1,
        enabled: true,
      });

      expect(result.data[0]).toBe(255);
      expect(result.data[1]).toBe(255);
      expect(result.data[2]).toBe(255);
    });

    it('should invert white to black', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [255, 255, 255, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'invert',
        value: 1,
        enabled: true,
      });

      expect(result.data[0]).toBe(0);
      expect(result.data[1]).toBe(0);
      expect(result.data[2]).toBe(0);
    });
  });

  describe('disabled filters', () => {
    it('should not apply filter when disabled', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [100, 100, 100, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'brightness',
        value: 1,
        enabled: false,
      });

      expect(result.data[0]).toBe(100);
    });

    it('should not apply filter when value is 0', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [100, 100, 100, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'brightness',
        value: 0,
        enabled: true,
      });

      expect(result.data[0]).toBe(100);
    });
  });

  describe('applyFilters (multiple)', () => {
    it('should apply multiple filters in sequence', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [128, 128, 128, 255]);
      
      const result = engine.applyFilters(imageData, [
        { type: 'brightness', value: 0.2, enabled: true },
        { type: 'contrast', value: 0.1, enabled: true },
      ]);

      expect(result.data[0]).not.toBe(128);
    });
  });

  describe('presets', () => {
    it('should apply vintage preset', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(4, 4, [128, 128, 128, 255]);
      
      const result = engine.applyPreset(imageData, 'vintage');
      
      expect(result.data[0]).not.toBe(128);
    });

    it('should apply noir preset (grayscale)', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(4, 4, [255, 0, 0, 255]);
      
      const result = engine.applyPreset(imageData, 'noir');
      
      expect(result.data[0]).toBe(result.data[1]);
      expect(result.data[1]).toBe(result.data[2]);
    });

    it('should return original for unknown preset', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [128, 128, 128, 255]);
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
      
      const result = engine.applyPreset(imageData, 'nonexistent');
      
      expect(result.data[0]).toBe(128);
      expect(consoleSpy).toHaveBeenCalled();
      consoleSpy.mockRestore();
    });

    it('should get presets by category', () => {
      const engine = new FilterEngine();
      const byCategory = engine.getPresetsByCategory();
      
      expect(byCategory.has('Classic')).toBe(true);
      expect(byCategory.has('Color')).toBe(true);
      expect(byCategory.get('Classic')?.length).toBeGreaterThan(0);
    });
  });

  describe('custom filters', () => {
    it('should register and apply custom filter', () => {
      const engine = new FilterEngine();
      
      engine.registerFilter({
        type: 'custom' as any,
        apply: (imageData, value) => {
          const data = imageData.data;
          for (let i = 0; i < data.length; i += 4) {
            data[i] = 255;
          }
          return imageData;
        },
      });

      const imageData = createTestImageData(2, 2, [0, 0, 0, 255]);
      const result = engine.applyFilter(imageData, {
        type: 'custom' as any,
        value: 1,
        enabled: true,
      });

      expect(result.data[0]).toBe(255);
    });
  });

  describe('edge cases', () => {
    it('should clamp values to 0-255 range', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(2, 2, [250, 250, 250, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'brightness',
        value: 1,
        enabled: true,
      });

      expect(result.data[0]).toBeLessThanOrEqual(255);
    });

    it('should handle 1x1 image', () => {
      const engine = new FilterEngine();
      const imageData = createTestImageData(1, 1, [128, 128, 128, 255]);
      
      const result = engine.applyFilter(imageData, {
        type: 'brightness',
        value: 0.5,
        enabled: true,
      });

      expect(result.width).toBe(1);
      expect(result.height).toBe(1);
    });
  });
});
