export interface AIProviderOptions {
  apiKey?: string;
  endpoint?: string;
  timeout?: number;
  headers?: Record<string, string>;
}

export interface BackgroundRemovalResult {
  foreground: Blob;
  mask?: Blob;
  background?: Blob;
}

export interface EnhanceResult {
  enhanced: Blob;
}

export interface UpscaleResult {
  upscaled: Blob;
  scale: number;
}

export interface GenerativeFillResult {
  filled: Blob;
}

export abstract class AIProvider {
  protected options: AIProviderOptions;

  constructor(options: AIProviderOptions = {}) {
    this.options = {
      timeout: 30000,
      ...options,
    };
  }

  abstract get name(): string;

  abstract removeBackground(image: Blob): Promise<BackgroundRemovalResult>;

  abstract enhance(image: Blob, options?: { strength?: number }): Promise<EnhanceResult>;

  abstract upscale(image: Blob, scale: number): Promise<UpscaleResult>;

  abstract generativeFill(
    image: Blob,
    mask: Blob,
    prompt: string
  ): Promise<GenerativeFillResult>;

  protected async fetchWithTimeout(
    url: string,
    options: RequestInit = {}
  ): Promise<Response> {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.options.timeout);

    try {
      const response = await fetch(url, {
        ...options,
        signal: controller.signal,
        headers: {
          ...this.options.headers,
          ...options.headers,
        },
      });
      return response;
    } finally {
      clearTimeout(timeoutId);
    }
  }

  protected async blobToBase64(blob: Blob): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => {
        const base64 = reader.result as string;
        resolve(base64.split(',')[1]);
      };
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
  }

  protected async base64ToBlob(base64: string, mimeType: string = 'image/png'): Promise<Blob> {
    const response = await fetch(`data:${mimeType};base64,${base64}`);
    return response.blob();
  }
}
