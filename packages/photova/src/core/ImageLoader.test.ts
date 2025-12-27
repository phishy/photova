import { describe, it, expect, vi, beforeEach, beforeAll } from 'vitest';
import { ImageLoader } from './ImageLoader';

class MockImage {
  src: string = '';
  crossOrigin: string = '';
  naturalWidth: number = 100;
  naturalHeight: number = 100;
  onload: (() => void) | null = null;
  onerror: (() => void) | null = null;

  constructor() {
    setTimeout(() => {
      if (this.src && this.onload) {
        this.onload();
      }
    }, 0);
  }
}

class MockFileReader {
  result: string | ArrayBuffer | null = null;
  onload: ((e: { target: { result: string | ArrayBuffer | null } }) => void) | null = null;
  onerror: (() => void) | null = null;

  readAsDataURL(_blob: Blob) {
    setTimeout(() => {
      this.result = 'data:image/png;base64,mockbase64data';
      if (this.onload) {
        this.onload({ target: { result: this.result } });
      }
    }, 0);
  }
}

class MockCanvasContext {
  imageSmoothingEnabled: boolean = false;
  imageSmoothingQuality: string = 'low';
  fillStyle: string = '';
  
  drawImage = vi.fn();
  putImageData = vi.fn();
  fillRect = vi.fn();
}

class MockCanvas {
  width: number = 0;
  height: number = 0;
  private ctx = new MockCanvasContext();

  getContext(_type: string) {
    return this.ctx;
  }

  toBlob(callback: BlobCallback) {
    callback(new Blob(['test'], { type: 'image/png' }));
  }
}

class MockImageData {
  data: Uint8ClampedArray;
  width: number;
  height: number;

  constructor(width: number, height: number) {
    this.width = width;
    this.height = height;
    this.data = new Uint8ClampedArray(width * height * 4);
  }
}

const originalCreateElement = document.createElement.bind(document);

beforeAll(() => {
  (globalThis as any).Image = MockImage;
  (globalThis as any).FileReader = MockFileReader;
  (globalThis as any).ImageData = MockImageData;
  
  vi.spyOn(document, 'createElement').mockImplementation((tag: string) => {
    if (tag === 'canvas') {
      return new MockCanvas() as unknown as HTMLCanvasElement;
    }
    return originalCreateElement(tag);
  });
  
  if (!globalThis.URL.createObjectURL) {
    globalThis.URL.createObjectURL = vi.fn().mockReturnValue('blob:mock-url');
  }
  if (!globalThis.URL.revokeObjectURL) {
    globalThis.URL.revokeObjectURL = vi.fn();
  }
});

describe('ImageLoader', () => {
  let imageLoader: ImageLoader;

  beforeEach(() => {
    imageLoader = new ImageLoader();
    vi.clearAllMocks();
  });

  describe('loadFromUrl', () => {
    it('should load an image from URL', async () => {
      const result = await imageLoader.loadFromUrl('https://example.com/image.png');

      expect(result.element).toBeDefined();
      expect(result.size.width).toBe(100);
      expect(result.size.height).toBe(100);
      expect(result.originalSrc).toBe('https://example.com/image.png');
    });

    it('should set crossOrigin to anonymous', async () => {
      const result = await imageLoader.loadFromUrl('https://example.com/image.png');
      expect((result.element as any).crossOrigin).toBe('anonymous');
    });

    it('should reject on image load error', async () => {
      const OriginalImage = (globalThis as any).Image;
      
      class FailingImage {
        src: string = '';
        crossOrigin: string = '';
        onload: (() => void) | null = null;
        onerror: (() => void) | null = null;

        constructor() {
          setTimeout(() => {
            if (this.onerror) this.onerror();
          }, 0);
        }
      }
      
      (globalThis as any).Image = FailingImage;

      await expect(imageLoader.loadFromUrl('https://example.com/bad.png')).rejects.toThrow(
        'Failed to load image from URL'
      );

      (globalThis as any).Image = OriginalImage;
    });
  });

  describe('loadFromFile', () => {
    it('should load an image from File', async () => {
      const file = new File(['test'], 'test.png', { type: 'image/png' });

      const result = await imageLoader.loadFromFile(file);

      expect(result.element).toBeDefined();
      expect(result.originalSrc).toBe('test.png');
    });

    it('should reject non-image files', async () => {
      const file = new File(['test'], 'test.txt', { type: 'text/plain' });

      await expect(imageLoader.loadFromFile(file)).rejects.toThrow('File is not an image');
    });

    it('should accept various image types', async () => {
      const imageTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

      for (const type of imageTypes) {
        const file = new File(['test'], `test.${type.split('/')[1]}`, { type });
        const result = await imageLoader.loadFromFile(file);
        expect(result.element).toBeDefined();
      }
    });

    it('should reject on FileReader error', async () => {
      const OriginalFileReader = (globalThis as any).FileReader;

      class FailingFileReader {
        result: string | null = null;
        onload: (() => void) | null = null;
        onerror: (() => void) | null = null;

        readAsDataURL() {
          setTimeout(() => {
            if (this.onerror) this.onerror();
          }, 0);
        }
      }

      (globalThis as any).FileReader = FailingFileReader;

      const file = new File(['test'], 'test.png', { type: 'image/png' });
      await expect(imageLoader.loadFromFile(file)).rejects.toThrow('Failed to read file');

      (globalThis as any).FileReader = OriginalFileReader;
    });
  });

  describe('loadFromBlob', () => {
    it('should load an image from Blob', async () => {
      const blob = new Blob(['test'], { type: 'image/png' });

      const result = await imageLoader.loadFromBlob(blob);

      expect(result.element).toBeDefined();
      expect(URL.createObjectURL).toHaveBeenCalledWith(blob);
      expect(URL.revokeObjectURL).toHaveBeenCalled();
    });

    it('should revoke object URL after loading', async () => {
      const blob = new Blob(['test'], { type: 'image/png' });

      await imageLoader.loadFromBlob(blob);

      expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:mock-url');
    });
  });

  describe('createCanvasFromImage', () => {
    it('should create a canvas with correct dimensions', () => {
      const mockImg = {
        naturalWidth: 200,
        naturalHeight: 150,
      } as HTMLImageElement;

      const canvas = imageLoader.createCanvasFromImage(mockImg);

      expect(canvas.width).toBe(200);
      expect(canvas.height).toBe(150);
    });

    it('should draw the image on the canvas', () => {
      const mockImg = {
        naturalWidth: 200,
        naturalHeight: 150,
      } as HTMLImageElement;

      const canvas = imageLoader.createCanvasFromImage(mockImg);
      const ctx = canvas.getContext('2d') as unknown as MockCanvasContext;

      expect(ctx.drawImage).toHaveBeenCalledWith(mockImg, 0, 0);
    });
  });

  describe('resizeImage', () => {
    it('should resize canvas to fit within max dimensions (width constrained)', () => {
      const inputCanvas = new MockCanvas() as unknown as HTMLCanvasElement;
      inputCanvas.width = 1000;
      inputCanvas.height = 500;

      const canvas = imageLoader.resizeImage(inputCanvas, 500, 500);

      expect(canvas.width).toBe(500);
      expect(canvas.height).toBe(250);
    });

    it('should resize canvas to fit within max dimensions (height constrained)', () => {
      const inputCanvas = new MockCanvas() as unknown as HTMLCanvasElement;
      inputCanvas.width = 500;
      inputCanvas.height = 1000;

      const canvas = imageLoader.resizeImage(inputCanvas, 500, 500);

      expect(canvas.width).toBe(250);
      expect(canvas.height).toBe(500);
    });

    it('should not resize if canvas is smaller than max dimensions', () => {
      const inputCanvas = new MockCanvas() as unknown as HTMLCanvasElement;
      inputCanvas.width = 200;
      inputCanvas.height = 150;

      const canvas = imageLoader.resizeImage(inputCanvas, 500, 500);

      expect(canvas.width).toBe(200);
      expect(canvas.height).toBe(150);
    });

    it('should maintain aspect ratio when both dimensions need reduction', () => {
      const inputCanvas = new MockCanvas() as unknown as HTMLCanvasElement;
      inputCanvas.width = 1600;
      inputCanvas.height = 1200;

      const canvas = imageLoader.resizeImage(inputCanvas, 400, 400);

      expect(canvas.width).toBe(400);
      expect(canvas.height).toBe(300);
    });

    it('should round dimensions to whole numbers', () => {
      const inputCanvas = new MockCanvas() as unknown as HTMLCanvasElement;
      inputCanvas.width = 1000;
      inputCanvas.height = 333;

      const canvas = imageLoader.resizeImage(inputCanvas, 500, 500);

      expect(Number.isInteger(canvas.width)).toBe(true);
      expect(Number.isInteger(canvas.height)).toBe(true);
    });

    it('should enable high quality image smoothing', () => {
      const inputCanvas = new MockCanvas() as unknown as HTMLCanvasElement;
      inputCanvas.width = 1000;
      inputCanvas.height = 500;

      const canvas = imageLoader.resizeImage(inputCanvas, 500, 500);
      const ctx = canvas.getContext('2d') as unknown as MockCanvasContext;

      expect(ctx.imageSmoothingEnabled).toBe(true);
      expect(ctx.imageSmoothingQuality).toBe('high');
    });
  });

  describe('loadFromCanvas', () => {
    it('should load an image from canvas', async () => {
      const canvas = new MockCanvas() as unknown as HTMLCanvasElement;
      canvas.width = 100;
      canvas.height = 100;

      const result = await imageLoader.loadFromCanvas(canvas);
      expect(result.element).toBeDefined();
    });

    it('should reject if toBlob fails', async () => {
      const canvas = new MockCanvas() as unknown as HTMLCanvasElement;
      canvas.toBlob = (callback: BlobCallback) => {
        callback(null);
      };

      await expect(imageLoader.loadFromCanvas(canvas)).rejects.toThrow(
        'Failed to convert canvas to blob'
      );
    });
  });

  describe('loadFromImageData', () => {
    it('should load an image from ImageData', async () => {
      const imageData = new MockImageData(50, 50) as unknown as ImageData;

      const result = await imageLoader.loadFromImageData(imageData);
      expect(result.element).toBeDefined();
    });

    it('should set canvas dimensions from ImageData', async () => {
      const imageData = new MockImageData(75, 100) as unknown as ImageData;

      await imageLoader.loadFromImageData(imageData);
      
      expect(document.createElement).toHaveBeenCalledWith('canvas');
    });
  });
});
