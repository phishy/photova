import { EventEmitter } from './EventEmitter';
import { CanvasManager } from './CanvasManager';
import { LayerManager } from './LayerManager';
import { HistoryManager } from './HistoryManager';
import { ImageLoader } from './ImageLoader';
import type {
  BrightenConfig,
  EditorEvents,
  EditorState,
  ToolType,
  Layer,
  ImageLayer,
  Point,
  Size,
  ExportOptions,
} from './types';

export class Editor extends EventEmitter<EditorEvents> {
  private config: BrightenConfig;
  private container: HTMLElement;
  private canvasManager: CanvasManager;
  private layerManager: LayerManager;
  private historyManager: HistoryManager;
  private imageLoader: ImageLoader;
  private activeTool: ToolType = 'select';
  private isDirty: boolean = false;
  private originalImageSize: Size = { width: 0, height: 0 };

  constructor(config: BrightenConfig) {
    super();
    this.config = config;
    this.container = this.resolveContainer(config.container);
    this.canvasManager = new CanvasManager(this.container);
    this.layerManager = new LayerManager();
    this.historyManager = new HistoryManager(config.maxHistorySteps);
    this.imageLoader = new ImageLoader();

    this.setupEventForwarding();
    this.initialize();
  }

  private resolveContainer(container: HTMLElement | string): HTMLElement {
    if (typeof container === 'string') {
      const el = document.querySelector(container);
      if (!el) throw new Error(`Container not found: ${container}`);
      return el as HTMLElement;
    }
    return container;
  }

  private setupEventForwarding(): void {
    this.canvasManager.on('zoom:change', (data) => this.emit('zoom:change', data));
    this.canvasManager.on('pan:change', (data) => this.emit('pan:change', data));
    this.canvasManager.on('render', () => {
      this.canvasManager.render(this.layerManager.getLayers());
      this.emit('render', undefined);
    });

    this.layerManager.on('layer:add', (data) => {
      this.emit('layer:add', data);
      this.markDirty();
      this.requestRender();
    });
    this.layerManager.on('layer:remove', (data) => {
      this.emit('layer:remove', data);
      this.markDirty();
      this.requestRender();
    });
    this.layerManager.on('layer:update', (data) => {
      this.emit('layer:update', data);
      this.markDirty();
      this.requestRender();
    });
    this.layerManager.on('layer:select', (data) => this.emit('layer:select', data));
    this.layerManager.on('layer:reorder', (data) => {
      this.emit('layer:reorder', data);
      this.requestRender();
    });

    this.historyManager.on('history:undo', async (data) => {
      await this.restoreState(data.entry.state);
      this.emit('history:undo', data);
    });
    this.historyManager.on('history:redo', async (data) => {
      await this.restoreState(data.entry.state);
      this.emit('history:redo', data);
    });
    this.historyManager.on('history:change', (data) => this.emit('history:change', data));
  }

  private async initialize(): Promise<void> {
    if (this.config.image) {
      await this.loadImage(this.config.image);
    } else if (this.config.width && this.config.height) {
      this.setCanvasSize({ width: this.config.width, height: this.config.height });
    }
  }

  async loadImage(source: string | HTMLImageElement | HTMLCanvasElement): Promise<void> {
    let loaded;

    if (typeof source === 'string') {
      loaded = await this.imageLoader.loadFromUrl(source);
    } else if (source instanceof HTMLImageElement) {
      loaded = {
        element: source,
        size: { width: source.naturalWidth, height: source.naturalHeight },
        originalSrc: source.src,
      };
    } else {
      loaded = {
        element: source,
        size: { width: source.width, height: source.height },
        originalSrc: 'canvas',
      };
    }

    this.originalImageSize = loaded.size;
    this.canvasManager.setCanvasSize(loaded.size);

    this.layerManager.clear();
    const imgElement = loaded.element instanceof HTMLCanvasElement
      ? loaded.element
      : loaded.element;
    this.layerManager.addImageLayer(imgElement, { name: 'Background' });

    this.historyManager.clear();
    this.saveToHistory('Load image');
    this.isDirty = false;

    this.emit('image:load', loaded.size);
    this.requestRender();
  }

  async loadFromFile(file: File): Promise<void> {
    const loaded = await this.imageLoader.loadFromFile(file);
    await this.loadImage(loaded.element);
  }

  setCanvasSize(size: Size): void {
    this.originalImageSize = size;
    this.canvasManager.setCanvasSize(size);
  }

  getCanvasSize(): Size {
    return this.canvasManager.getCanvasSize();
  }

  setTool(tool: ToolType): void {
    const previousTool = this.activeTool;
    this.activeTool = tool;
    this.emit('tool:change', { tool, previousTool });
  }

  getTool(): ToolType {
    return this.activeTool;
  }

  getLayerManager(): LayerManager {
    return this.layerManager;
  }

  getCanvasManager(): CanvasManager {
    return this.canvasManager;
  }

  getHistoryManager(): HistoryManager {
    return this.historyManager;
  }

  undo(): void {
    this.historyManager.undo();
  }

  redo(): void {
    this.historyManager.redo();
  }

  canUndo(): boolean {
    return this.historyManager.canUndo();
  }

  canRedo(): boolean {
    return this.historyManager.canRedo();
  }

  saveToHistory(action: string): void {
    this.historyManager.push(action, this.getState());
  }

  private getState(): EditorState {
    return {
      layers: this.layerManager.getLayers(),
      activeLayerId: this.layerManager.getActiveId(),
      selectedLayerIds: this.layerManager.getSelectedIds(),
      activeTool: this.activeTool,
      zoom: this.canvasManager.getZoom(),
      pan: this.canvasManager.getPan(),
      canvasSize: this.canvasManager.getCanvasSize(),
      originalImageSize: this.originalImageSize,
      isDirty: this.isDirty,
    };
  }

  private async restoreState(state: EditorState): Promise<void> {
    const deserializedState = await this.historyManager.deserializeState(state);
    
    this.layerManager.restoreFromLayers(
      deserializedState.layers,
      deserializedState.activeLayerId,
      deserializedState.selectedLayerIds
    );
    
    this.canvasManager.setCanvasSize(deserializedState.canvasSize);
    this.originalImageSize = deserializedState.originalImageSize;
    this.activeTool = deserializedState.activeTool;
    
    this.requestRender();
  }

  private markDirty(): void {
    this.isDirty = true;
  }

  isDirtyState(): boolean {
    return this.isDirty;
  }

  requestRender(): void {
    this.canvasManager.queueRender();
  }

  setZoom(zoom: number, center?: Point): void {
    this.canvasManager.setZoom(zoom, center);
  }

  getZoom(): number {
    return this.canvasManager.getZoom();
  }

  zoomIn(): void {
    this.setZoom(this.getZoom() * 1.25);
  }

  zoomOut(): void {
    this.setZoom(this.getZoom() / 1.25);
  }

  fitToView(): void {
    this.canvasManager.fitToView();
  }

  setPan(pan: Point): void {
    this.canvasManager.setPan(pan);
  }

  getPan(): Point {
    return this.canvasManager.getPan();
  }

  screenToCanvas(point: Point): Point {
    return this.canvasManager.screenToCanvas(point);
  }

  canvasToScreen(point: Point): Point {
    return this.canvasManager.canvasToScreen(point);
  }

  async export(options: Partial<ExportOptions> = {}): Promise<Blob> {
    const { format = 'png', quality = 0.92 } = options;
    const blob = await this.canvasManager.export(format, quality);
    this.emit('image:export', { format, size: this.canvasManager.getCanvasSize() });
    return blob;
  }

  exportDataURL(format: 'png' | 'jpeg' | 'webp' = 'png', quality: number = 0.92): string {
    return this.canvasManager.exportDataURL(format, quality);
  }

  getImageData(): ImageData {
    return this.canvasManager.getImageData();
  }

  destroy(): void {
    this.canvasManager.destroy();
    this.removeAllListeners();
  }
}
