import type { Size } from './types';

export interface LoadedImage {
  element: HTMLImageElement;
  size: Size;
  originalSrc: string;
}

export class ImageLoader {
  async loadFromUrl(url: string): Promise<LoadedImage> {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.crossOrigin = 'anonymous';

      img.onload = () => {
        resolve({
          element: img,
          size: { width: img.naturalWidth, height: img.naturalHeight },
          originalSrc: url,
        });
      };

      img.onerror = () => {
        reject(new Error(`Failed to load image from URL: ${url}`));
      };

      img.src = url;
    });
  }

  async loadFromFile(file: File): Promise<LoadedImage> {
    return new Promise((resolve, reject) => {
      if (!file.type.startsWith('image/')) {
        reject(new Error('File is not an image'));
        return;
      }

      const reader = new FileReader();

      reader.onload = async (e) => {
        try {
          const dataUrl = e.target?.result as string;
          const loaded = await this.loadFromUrl(dataUrl);
          resolve({
            ...loaded,
            originalSrc: file.name,
          });
        } catch (error) {
          reject(error);
        }
      };

      reader.onerror = () => {
        reject(new Error('Failed to read file'));
      };

      reader.readAsDataURL(file);
    });
  }

  async loadFromBlob(blob: Blob): Promise<LoadedImage> {
    const url = URL.createObjectURL(blob);
    try {
      const loaded = await this.loadFromUrl(url);
      return loaded;
    } finally {
      URL.revokeObjectURL(url);
    }
  }

  async loadFromCanvas(canvas: HTMLCanvasElement): Promise<LoadedImage> {
    return new Promise((resolve, reject) => {
      canvas.toBlob(async (blob) => {
        if (!blob) {
          reject(new Error('Failed to convert canvas to blob'));
          return;
        }
        try {
          const loaded = await this.loadFromBlob(blob);
          resolve(loaded);
        } catch (error) {
          reject(error);
        }
      });
    });
  }

  async loadFromImageData(imageData: ImageData): Promise<LoadedImage> {
    const canvas = document.createElement('canvas');
    canvas.width = imageData.width;
    canvas.height = imageData.height;
    const ctx = canvas.getContext('2d')!;
    ctx.putImageData(imageData, 0, 0);
    return this.loadFromCanvas(canvas);
  }

  createCanvasFromImage(img: HTMLImageElement): HTMLCanvasElement {
    const canvas = document.createElement('canvas');
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(img, 0, 0);
    return canvas;
  }

  resizeImage(img: HTMLImageElement | HTMLCanvasElement, maxWidth: number, maxHeight: number): HTMLCanvasElement {
    const srcWidth = img instanceof HTMLImageElement ? img.naturalWidth : img.width;
    const srcHeight = img instanceof HTMLImageElement ? img.naturalHeight : img.height;

    let width = srcWidth;
    let height = srcHeight;

    if (width > maxWidth) {
      height = (height * maxWidth) / width;
      width = maxWidth;
    }

    if (height > maxHeight) {
      width = (width * maxHeight) / height;
      height = maxHeight;
    }

    const canvas = document.createElement('canvas');
    canvas.width = Math.round(width);
    canvas.height = Math.round(height);

    const ctx = canvas.getContext('2d')!;
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

    return canvas;
  }
}
