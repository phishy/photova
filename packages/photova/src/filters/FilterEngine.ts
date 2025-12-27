import type { FilterType, AppliedFilter, FilterPreset } from '../core/types';

export interface FilterProcessor {
  type: FilterType;
  apply(imageData: ImageData, value: number): ImageData;
}

export class FilterEngine {
  private processors: Map<FilterType, FilterProcessor> = new Map();
  private presets: Map<string, FilterPreset> = new Map();

  constructor() {
    this.registerBuiltInFilters();
    this.registerBuiltInPresets();
  }

  private registerBuiltInFilters(): void {
    this.registerFilter({
      type: 'brightness',
      apply: (imageData, value) => {
        const data = imageData.data;
        const adjustment = value * 255;
        for (let i = 0; i < data.length; i += 4) {
          data[i] = this.clamp(data[i] + adjustment);
          data[i + 1] = this.clamp(data[i + 1] + adjustment);
          data[i + 2] = this.clamp(data[i + 2] + adjustment);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'contrast',
      apply: (imageData, value) => {
        const data = imageData.data;
        const factor = (259 * (value * 255 + 255)) / (255 * (259 - value * 255));
        for (let i = 0; i < data.length; i += 4) {
          data[i] = this.clamp(factor * (data[i] - 128) + 128);
          data[i + 1] = this.clamp(factor * (data[i + 1] - 128) + 128);
          data[i + 2] = this.clamp(factor * (data[i + 2] - 128) + 128);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'saturation',
      apply: (imageData, value) => {
        const data = imageData.data;
        const adjustment = value + 1;
        for (let i = 0; i < data.length; i += 4) {
          const gray = 0.2989 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
          data[i] = this.clamp(gray + adjustment * (data[i] - gray));
          data[i + 1] = this.clamp(gray + adjustment * (data[i + 1] - gray));
          data[i + 2] = this.clamp(gray + adjustment * (data[i + 2] - gray));
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'hue',
      apply: (imageData, value) => {
        const data = imageData.data;
        const angle = value * 360;
        const cosA = Math.cos((angle * Math.PI) / 180);
        const sinA = Math.sin((angle * Math.PI) / 180);

        const matrix = [
          cosA + (1 - cosA) / 3,
          (1 - cosA) / 3 - Math.sqrt(1 / 3) * sinA,
          (1 - cosA) / 3 + Math.sqrt(1 / 3) * sinA,
          (1 - cosA) / 3 + Math.sqrt(1 / 3) * sinA,
          cosA + (1 - cosA) / 3,
          (1 - cosA) / 3 - Math.sqrt(1 / 3) * sinA,
          (1 - cosA) / 3 - Math.sqrt(1 / 3) * sinA,
          (1 - cosA) / 3 + Math.sqrt(1 / 3) * sinA,
          cosA + (1 - cosA) / 3,
        ];

        for (let i = 0; i < data.length; i += 4) {
          const r = data[i];
          const g = data[i + 1];
          const b = data[i + 2];
          data[i] = this.clamp(r * matrix[0] + g * matrix[1] + b * matrix[2]);
          data[i + 1] = this.clamp(r * matrix[3] + g * matrix[4] + b * matrix[5]);
          data[i + 2] = this.clamp(r * matrix[6] + g * matrix[7] + b * matrix[8]);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'exposure',
      apply: (imageData, value) => {
        const data = imageData.data;
        const exposure = Math.pow(2, value);
        for (let i = 0; i < data.length; i += 4) {
          data[i] = this.clamp(data[i] * exposure);
          data[i + 1] = this.clamp(data[i + 1] * exposure);
          data[i + 2] = this.clamp(data[i + 2] * exposure);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'temperature',
      apply: (imageData, value) => {
        const data = imageData.data;
        const adjustment = value * 30;
        for (let i = 0; i < data.length; i += 4) {
          data[i] = this.clamp(data[i] + adjustment);
          data[i + 2] = this.clamp(data[i + 2] - adjustment);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'tint',
      apply: (imageData, value) => {
        const data = imageData.data;
        const adjustment = value * 30;
        for (let i = 0; i < data.length; i += 4) {
          data[i + 1] = this.clamp(data[i + 1] + adjustment);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'vibrance',
      apply: (imageData, value) => {
        const data = imageData.data;
        const amount = value * 2;
        for (let i = 0; i < data.length; i += 4) {
          const max = Math.max(data[i], data[i + 1], data[i + 2]);
          const avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
          const amt = ((Math.abs(max - avg) * 2) / 255) * amount;
          data[i] = this.clamp(data[i] + (max - data[i]) * amt);
          data[i + 1] = this.clamp(data[i + 1] + (max - data[i + 1]) * amt);
          data[i + 2] = this.clamp(data[i + 2] + (max - data[i + 2]) * amt);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'sharpen',
      apply: (imageData, value) => {
        if (value === 0) return imageData;
        const kernel = [0, -value, 0, -value, 1 + 4 * value, -value, 0, -value, 0];
        return this.convolve(imageData, kernel);
      },
    });

    this.registerFilter({
      type: 'blur',
      apply: (imageData, value) => {
        if (value === 0) return imageData;
        const size = Math.ceil(value * 10);
        return this.boxBlur(imageData, size);
      },
    });

    this.registerFilter({
      type: 'grayscale',
      apply: (imageData, value) => {
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
          const gray = 0.2989 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
          const blended = data[i] * (1 - value) + gray * value;
          const blendedG = data[i + 1] * (1 - value) + gray * value;
          const blendedB = data[i + 2] * (1 - value) + gray * value;
          data[i] = blended;
          data[i + 1] = blendedG;
          data[i + 2] = blendedB;
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'sepia',
      apply: (imageData, value) => {
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
          const r = data[i];
          const g = data[i + 1];
          const b = data[i + 2];
          const sepiaR = 0.393 * r + 0.769 * g + 0.189 * b;
          const sepiaG = 0.349 * r + 0.686 * g + 0.168 * b;
          const sepiaB = 0.272 * r + 0.534 * g + 0.131 * b;
          data[i] = this.clamp(r * (1 - value) + sepiaR * value);
          data[i + 1] = this.clamp(g * (1 - value) + sepiaG * value);
          data[i + 2] = this.clamp(b * (1 - value) + sepiaB * value);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'invert',
      apply: (imageData, value) => {
        const data = imageData.data;
        for (let i = 0; i < data.length; i += 4) {
          data[i] = this.clamp(data[i] * (1 - value) + (255 - data[i]) * value);
          data[i + 1] = this.clamp(data[i + 1] * (1 - value) + (255 - data[i + 1]) * value);
          data[i + 2] = this.clamp(data[i + 2] * (1 - value) + (255 - data[i + 2]) * value);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'vignette',
      apply: (imageData, value) => {
        const data = imageData.data;
        const width = imageData.width;
        const height = imageData.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const maxDist = Math.sqrt(centerX * centerX + centerY * centerY);

        for (let y = 0; y < height; y++) {
          for (let x = 0; x < width; x++) {
            const i = (y * width + x) * 4;
            const dist = Math.sqrt((x - centerX) ** 2 + (y - centerY) ** 2);
            const vignette = 1 - (dist / maxDist) * value;
            data[i] = this.clamp(data[i] * vignette);
            data[i + 1] = this.clamp(data[i + 1] * vignette);
            data[i + 2] = this.clamp(data[i + 2] * vignette);
          }
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'noise',
      apply: (imageData, value) => {
        const data = imageData.data;
        const amount = value * 50;
        for (let i = 0; i < data.length; i += 4) {
          const noise = (Math.random() - 0.5) * amount;
          data[i] = this.clamp(data[i] + noise);
          data[i + 1] = this.clamp(data[i + 1] + noise);
          data[i + 2] = this.clamp(data[i + 2] + noise);
        }
        return imageData;
      },
    });

    this.registerFilter({
      type: 'grain',
      apply: (imageData, value) => {
        const data = imageData.data;
        const amount = value * 30;
        for (let i = 0; i < data.length; i += 4) {
          const grain = (Math.random() - 0.5) * amount;
          const luminance = 0.2989 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
          const grainAdjusted = grain * (1 - luminance / 255);
          data[i] = this.clamp(data[i] + grainAdjusted);
          data[i + 1] = this.clamp(data[i + 1] + grainAdjusted);
          data[i + 2] = this.clamp(data[i + 2] + grainAdjusted);
        }
        return imageData;
      },
    });
  }

  private registerBuiltInPresets(): void {
    this.registerPreset({
      id: 'vintage',
      name: 'Vintage',
      category: 'Classic',
      filters: [
        { type: 'saturation', value: -0.3, enabled: true },
        { type: 'sepia', value: 0.4, enabled: true },
        { type: 'vignette', value: 0.3, enabled: true },
        { type: 'grain', value: 0.2, enabled: true },
      ],
    });

    this.registerPreset({
      id: 'noir',
      name: 'Noir',
      category: 'Classic',
      filters: [
        { type: 'grayscale', value: 1, enabled: true },
        { type: 'contrast', value: 0.3, enabled: true },
        { type: 'vignette', value: 0.4, enabled: true },
      ],
    });

    this.registerPreset({
      id: 'warm',
      name: 'Warm',
      category: 'Color',
      filters: [
        { type: 'temperature', value: 0.3, enabled: true },
        { type: 'saturation', value: 0.1, enabled: true },
      ],
    });

    this.registerPreset({
      id: 'cool',
      name: 'Cool',
      category: 'Color',
      filters: [
        { type: 'temperature', value: -0.3, enabled: true },
        { type: 'saturation', value: 0.1, enabled: true },
      ],
    });

    this.registerPreset({
      id: 'vivid',
      name: 'Vivid',
      category: 'Color',
      filters: [
        { type: 'saturation', value: 0.4, enabled: true },
        { type: 'vibrance', value: 0.3, enabled: true },
        { type: 'contrast', value: 0.1, enabled: true },
      ],
    });

    this.registerPreset({
      id: 'matte',
      name: 'Matte',
      category: 'Film',
      filters: [
        { type: 'contrast', value: -0.1, enabled: true },
        { type: 'brightness', value: 0.05, enabled: true },
        { type: 'saturation', value: -0.1, enabled: true },
      ],
    });

    this.registerPreset({
      id: 'dramatic',
      name: 'Dramatic',
      category: 'Mood',
      filters: [
        { type: 'contrast', value: 0.4, enabled: true },
        { type: 'saturation', value: -0.2, enabled: true },
        { type: 'vignette', value: 0.5, enabled: true },
      ],
    });

    this.registerPreset({
      id: 'soft',
      name: 'Soft',
      category: 'Portrait',
      filters: [
        { type: 'contrast', value: -0.1, enabled: true },
        { type: 'brightness', value: 0.1, enabled: true },
        { type: 'blur', value: 0.05, enabled: true },
      ],
    });
  }

  registerFilter(processor: FilterProcessor): void {
    this.processors.set(processor.type, processor);
  }

  registerPreset(preset: FilterPreset): void {
    this.presets.set(preset.id, preset);
  }

  applyFilter(imageData: ImageData, filter: AppliedFilter): ImageData {
    if (!filter.enabled || filter.value === 0) return imageData;

    const processor = this.processors.get(filter.type);
    if (!processor) {
      console.warn(`Filter processor not found: ${filter.type}`);
      return imageData;
    }

    return processor.apply(imageData, filter.value);
  }

  applyFilters(imageData: ImageData, filters: AppliedFilter[]): ImageData {
    let result = imageData;
    for (const filter of filters) {
      result = this.applyFilter(result, filter);
    }
    return result;
  }

  applyPreset(imageData: ImageData, presetId: string): ImageData {
    const preset = this.presets.get(presetId);
    if (!preset) {
      console.warn(`Preset not found: ${presetId}`);
      return imageData;
    }
    return this.applyFilters(imageData, preset.filters);
  }

  getPresets(): FilterPreset[] {
    return [...this.presets.values()];
  }

  getPresetsByCategory(): Map<string, FilterPreset[]> {
    const byCategory = new Map<string, FilterPreset[]>();
    for (const preset of this.presets.values()) {
      const existing = byCategory.get(preset.category) || [];
      existing.push(preset);
      byCategory.set(preset.category, existing);
    }
    return byCategory;
  }

  private clamp(value: number): number {
    return Math.max(0, Math.min(255, Math.round(value)));
  }

  private convolve(imageData: ImageData, kernel: number[]): ImageData {
    const data = imageData.data;
    const width = imageData.width;
    const height = imageData.height;
    const output = new Uint8ClampedArray(data.length);

    const kSize = Math.sqrt(kernel.length);
    const half = Math.floor(kSize / 2);

    for (let y = 0; y < height; y++) {
      for (let x = 0; x < width; x++) {
        let r = 0,
          g = 0,
          b = 0;

        for (let ky = 0; ky < kSize; ky++) {
          for (let kx = 0; kx < kSize; kx++) {
            const px = Math.min(width - 1, Math.max(0, x + kx - half));
            const py = Math.min(height - 1, Math.max(0, y + ky - half));
            const idx = (py * width + px) * 4;
            const weight = kernel[ky * kSize + kx];
            r += data[idx] * weight;
            g += data[idx + 1] * weight;
            b += data[idx + 2] * weight;
          }
        }

        const i = (y * width + x) * 4;
        output[i] = this.clamp(r);
        output[i + 1] = this.clamp(g);
        output[i + 2] = this.clamp(b);
        output[i + 3] = data[i + 3];
      }
    }

    return new ImageData(output, width, height);
  }

  private boxBlur(imageData: ImageData, radius: number): ImageData {
    const size = radius * 2 + 1;
    const weight = 1 / (size * size);
    const kernel = new Array(size * size).fill(weight);
    return this.convolve(imageData, kernel);
  }
}
