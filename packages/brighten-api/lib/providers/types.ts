export type OperationType = 
  | 'background-remove'
  | 'upscale'
  | 'unblur'
  | 'enhance'
  | 'colorize'
  | 'denoise'
  | 'face-restore'
  | 'style-transfer'
  | 'inpaint'
  | 'outpaint'
  | 'restore';

export interface OperationInput {
  image: Buffer;
  mimeType: string;
  options?: Record<string, unknown>;
}

export interface OperationResult {
  image: Buffer;
  mimeType: string;
  metadata?: {
    provider: string;
    model?: string;
    processingTime: number;
    [key: string]: unknown;
  };
}
