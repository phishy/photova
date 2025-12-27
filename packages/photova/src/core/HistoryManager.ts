import { EventEmitter } from './EventEmitter';
import type { EditorEvents, EditorState, HistoryEntry, Layer, ImageLayer, StickerLayer } from './types';

interface SerializedLayer extends Omit<Layer, 'source'> {
  sourceDataUrl?: string;
  originalSourceDataUrl?: string;
}

export class HistoryManager extends EventEmitter<Pick<EditorEvents, 'history:undo' | 'history:redo' | 'history:change'>> {
  private history: HistoryEntry[] = [];
  private currentIndex: number = -1;
  private maxSteps: number;
  private isBatching: boolean = false;
  private batchedChanges: Partial<EditorState>[] = [];

  constructor(maxSteps: number = 50) {
    super();
    this.maxSteps = maxSteps;
  }

  push(action: string, state: EditorState): void {
    if (this.isBatching) {
      return;
    }

    if (this.currentIndex < this.history.length - 1) {
      this.history = this.history.slice(0, this.currentIndex + 1);
    }

    const entry: HistoryEntry = {
      id: this.generateId(),
      timestamp: Date.now(),
      action,
      state: this.serializeState(state),
    };

    this.history.push(entry);

    if (this.history.length > this.maxSteps) {
      this.history.shift();
    } else {
      this.currentIndex++;
    }

    this.emitChange();
  }

  undo(): HistoryEntry | null {
    if (!this.canUndo()) return null;

    this.currentIndex--;
    const entry = this.history[this.currentIndex];
    this.emit('history:undo', { entry });
    this.emitChange();
    return entry;
  }

  redo(): HistoryEntry | null {
    if (!this.canRedo()) return null;

    this.currentIndex++;
    const entry = this.history[this.currentIndex];
    this.emit('history:redo', { entry });
    this.emitChange();
    return entry;
  }

  canUndo(): boolean {
    return this.currentIndex > 0;
  }

  canRedo(): boolean {
    return this.currentIndex < this.history.length - 1;
  }

  getCurrentState(): EditorState | null {
    if (this.currentIndex < 0 || this.currentIndex >= this.history.length) {
      return null;
    }
    return this.history[this.currentIndex].state;
  }

  startBatch(): void {
    this.isBatching = true;
    this.batchedChanges = [];
  }

  endBatch(action: string, state: EditorState): void {
    this.isBatching = false;
    this.batchedChanges = [];
    this.push(action, state);
  }

  cancelBatch(): void {
    this.isBatching = false;
    this.batchedChanges = [];
  }

  clear(): void {
    this.history = [];
    this.currentIndex = -1;
    this.emitChange();
  }

  getHistory(): HistoryEntry[] {
    return [...this.history];
  }

  private generateId(): string {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  private serializeState(state: EditorState): EditorState {
    const serializedLayers = state.layers.map(layer => this.serializeLayer(layer));
    return {
      ...state,
      layers: serializedLayers as Layer[],
    };
  }

  private serializeLayer(layer: Layer): SerializedLayer {
    if (layer.type === 'image') {
      const imgLayer = layer as ImageLayer;
      const serialized: SerializedLayer = {
        ...layer,
        sourceDataUrl: this.elementToDataUrl(imgLayer.source),
      };
      if (imgLayer.originalSource) {
        serialized.originalSourceDataUrl = this.elementToDataUrl(imgLayer.originalSource);
      }
      delete (serialized as any).source;
      delete (serialized as any).originalSource;
      return serialized;
    }
    if (layer.type === 'sticker') {
      const stickerLayer = layer as StickerLayer;
      const serialized: SerializedLayer = {
        ...layer,
        sourceDataUrl: this.elementToDataUrl(stickerLayer.source),
      };
      delete (serialized as any).source;
      return serialized;
    }
    return { ...layer };
  }

  private elementToDataUrl(element: HTMLImageElement | HTMLCanvasElement): string {
    if (element instanceof HTMLCanvasElement) {
      return element.toDataURL('image/png');
    }
    const canvas = document.createElement('canvas');
    canvas.width = element.naturalWidth || element.width;
    canvas.height = element.naturalHeight || element.height;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(element, 0, 0);
    return canvas.toDataURL('image/png');
  }

  deserializeState(state: EditorState): Promise<EditorState> {
    return this.deserializeStateAsync(state);
  }

  private async deserializeStateAsync(state: EditorState): Promise<EditorState> {
    const deserializedLayers = await Promise.all(
      state.layers.map(layer => this.deserializeLayer(layer as unknown as SerializedLayer))
    );
    return {
      ...state,
      layers: deserializedLayers,
    };
  }

  private async deserializeLayer(layer: SerializedLayer): Promise<Layer> {
    if (layer.type === 'image' && layer.sourceDataUrl) {
      const source = await this.dataUrlToCanvas(layer.sourceDataUrl);
      let originalSource: HTMLImageElement | undefined;
      if (layer.originalSourceDataUrl) {
        originalSource = await this.dataUrlToImage(layer.originalSourceDataUrl);
      }
      const { sourceDataUrl, originalSourceDataUrl, ...rest } = layer;
      return {
        ...rest,
        type: 'image',
        source,
        originalSource,
        filters: (layer as any).filters || [],
      } as ImageLayer;
    }
    if (layer.type === 'sticker' && layer.sourceDataUrl) {
      const source = await this.dataUrlToImage(layer.sourceDataUrl);
      const { sourceDataUrl, ...rest } = layer;
      return {
        ...rest,
        type: 'sticker',
        source,
      } as StickerLayer;
    }
    return layer as Layer;
  }

  private dataUrlToCanvas(dataUrl: string): Promise<HTMLCanvasElement> {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d')!;
        ctx.drawImage(img, 0, 0);
        resolve(canvas);
      };
      img.src = dataUrl;
    });
  }

  private dataUrlToImage(dataUrl: string): Promise<HTMLImageElement> {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.src = dataUrl;
    });
  }

  private emitChange(): void {
    this.emit('history:change', {
      canUndo: this.canUndo(),
      canRedo: this.canRedo(),
    });
  }
}
