import { EventEmitter } from './EventEmitter';
import type {
  EditorEvents,
  Layer,
  ImageLayer,
  TextLayer,
  ShapeLayer,
  DrawingLayer,
  StickerLayer,
  AdjustmentLayer,
  Transform,
  BlendMode,
} from './types';

const DEFAULT_TRANSFORM: Transform = {
  x: 0,
  y: 0,
  scaleX: 1,
  scaleY: 1,
  rotation: 0,
  skewX: 0,
  skewY: 0,
};

export class LayerManager extends EventEmitter<
  Pick<EditorEvents, 'layer:add' | 'layer:remove' | 'layer:update' | 'layer:select' | 'layer:reorder'>
> {
  private layers: Map<string, Layer> = new Map();
  private layerOrder: string[] = [];
  private selectedIds: Set<string> = new Set();
  private activeId: string | null = null;

  private generateId(): string {
    return `layer-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }

  private createBaseLayer(type: Layer['type'], name?: string): Omit<Layer, 'type'> & { type: Layer['type'] } {
    return {
      id: this.generateId(),
      type,
      name: name || `${type.charAt(0).toUpperCase() + type.slice(1)} Layer`,
      visible: true,
      locked: false,
      opacity: 1,
      blendMode: 'normal' as BlendMode,
      transform: { ...DEFAULT_TRANSFORM },
    };
  }

  addImageLayer(
    source: HTMLImageElement | HTMLCanvasElement,
    options: Partial<Omit<ImageLayer, 'id' | 'type' | 'source'>> = {}
  ): ImageLayer {
    const layer: ImageLayer = {
      ...this.createBaseLayer('image', options.name),
      type: 'image',
      source,
      originalSource: source instanceof HTMLImageElement ? source : undefined,
      filters: [],
      ...options,
    };

    this.layers.set(layer.id, layer);
    this.layerOrder.push(layer.id);
    this.emit('layer:add', { layer });
    return layer;
  }

  addTextLayer(text: string, options: Partial<Omit<TextLayer, 'id' | 'type' | 'text'>> = {}): TextLayer {
    const layer: TextLayer = {
      ...this.createBaseLayer('text', options.name),
      type: 'text',
      text,
      fontFamily: 'Arial',
      fontSize: 24,
      fontWeight: 'normal',
      fontStyle: 'normal',
      color: '#000000',
      textAlign: 'left',
      lineHeight: 1.2,
      letterSpacing: 0,
      ...options,
    };

    this.layers.set(layer.id, layer);
    this.layerOrder.push(layer.id);
    this.emit('layer:add', { layer });
    return layer;
  }

  addShapeLayer(
    shapeType: ShapeLayer['shapeType'],
    options: Partial<Omit<ShapeLayer, 'id' | 'type' | 'shapeType'>> = {}
  ): ShapeLayer {
    const layer: ShapeLayer = {
      ...this.createBaseLayer('shape', options.name),
      type: 'shape',
      shapeType,
      fill: '#cccccc',
      ...options,
    };

    this.layers.set(layer.id, layer);
    this.layerOrder.push(layer.id);
    this.emit('layer:add', { layer });
    return layer;
  }

  addDrawingLayer(options: Partial<Omit<DrawingLayer, 'id' | 'type' | 'paths'>> = {}): DrawingLayer {
    const layer: DrawingLayer = {
      ...this.createBaseLayer('drawing', options.name),
      type: 'drawing',
      paths: [],
      ...options,
    };

    this.layers.set(layer.id, layer);
    this.layerOrder.push(layer.id);
    this.emit('layer:add', { layer });
    return layer;
  }

  addStickerLayer(
    source: HTMLImageElement,
    options: Partial<Omit<StickerLayer, 'id' | 'type' | 'source'>> = {}
  ): StickerLayer {
    const layer: StickerLayer = {
      ...this.createBaseLayer('sticker', options.name),
      type: 'sticker',
      source,
      ...options,
    };

    this.layers.set(layer.id, layer);
    this.layerOrder.push(layer.id);
    this.emit('layer:add', { layer });
    return layer;
  }

  addAdjustmentLayer(
    adjustmentType: AdjustmentLayer['adjustmentType'],
    settings: AdjustmentLayer['settings'],
    options: Partial<Omit<AdjustmentLayer, 'id' | 'type' | 'adjustmentType' | 'settings'>> = {}
  ): AdjustmentLayer {
    const layer: AdjustmentLayer = {
      ...this.createBaseLayer('adjustment', options.name),
      type: 'adjustment',
      adjustmentType,
      settings,
      ...options,
    };

    this.layers.set(layer.id, layer);
    this.layerOrder.push(layer.id);
    this.emit('layer:add', { layer });
    return layer;
  }

  getLayer<T extends Layer = Layer>(id: string): T | undefined {
    return this.layers.get(id) as T | undefined;
  }

  getLayers(): Layer[] {
    return this.layerOrder.map((id) => this.layers.get(id)!);
  }

  updateLayer<T extends Layer>(id: string, changes: Partial<T>): void {
    const layer = this.layers.get(id);
    if (!layer) return;

    const updated = { ...layer, ...changes } as Layer;
    this.layers.set(id, updated);
    this.emit('layer:update', { layerId: id, changes });
  }

  removeLayer(id: string): void {
    if (!this.layers.has(id)) return;

    this.layers.delete(id);
    this.layerOrder = this.layerOrder.filter((layerId) => layerId !== id);
    this.selectedIds.delete(id);

    if (this.activeId === id) {
      this.activeId = this.layerOrder[this.layerOrder.length - 1] || null;
    }

    this.emit('layer:remove', { layerId: id });
  }

  reorderLayers(newOrder: string[]): void {
    const validOrder = newOrder.filter((id) => this.layers.has(id));
    if (validOrder.length !== this.layerOrder.length) return;

    this.layerOrder = validOrder;
    this.emit('layer:reorder', { layerIds: validOrder });
  }

  moveLayerUp(id: string): void {
    const index = this.layerOrder.indexOf(id);
    if (index < this.layerOrder.length - 1) {
      [this.layerOrder[index], this.layerOrder[index + 1]] = [this.layerOrder[index + 1], this.layerOrder[index]];
      this.emit('layer:reorder', { layerIds: [...this.layerOrder] });
    }
  }

  moveLayerDown(id: string): void {
    const index = this.layerOrder.indexOf(id);
    if (index > 0) {
      [this.layerOrder[index], this.layerOrder[index - 1]] = [this.layerOrder[index - 1], this.layerOrder[index]];
      this.emit('layer:reorder', { layerIds: [...this.layerOrder] });
    }
  }

  selectLayer(id: string, addToSelection: boolean = false): void {
    if (!addToSelection) {
      this.selectedIds.clear();
    }
    this.selectedIds.add(id);
    this.activeId = id;
    this.emit('layer:select', { layerIds: [...this.selectedIds] });
  }

  deselectLayer(id: string): void {
    this.selectedIds.delete(id);
    if (this.activeId === id) {
      this.activeId = [...this.selectedIds][0] || null;
    }
    this.emit('layer:select', { layerIds: [...this.selectedIds] });
  }

  clearSelection(): void {
    this.selectedIds.clear();
    this.activeId = null;
    this.emit('layer:select', { layerIds: [] });
  }

  getSelectedLayers(): Layer[] {
    return [...this.selectedIds].map((id) => this.layers.get(id)!).filter(Boolean);
  }

  getSelectedIds(): string[] {
    return [...this.selectedIds];
  }

  getActiveLayer(): Layer | null {
    return this.activeId ? this.layers.get(this.activeId) || null : null;
  }

  getActiveId(): string | null {
    return this.activeId;
  }

  duplicateLayer(id: string): Layer | null {
    const original = this.layers.get(id);
    if (!original) return null;

    const clone = JSON.parse(JSON.stringify(original)) as Layer;
    clone.id = this.generateId();
    clone.name = `${original.name} (Copy)`;

    this.layers.set(clone.id, clone);
    const originalIndex = this.layerOrder.indexOf(id);
    this.layerOrder.splice(originalIndex + 1, 0, clone.id);

    this.emit('layer:add', { layer: clone });
    return clone;
  }

  clear(): void {
    this.layers.clear();
    this.layerOrder = [];
    this.selectedIds.clear();
    this.activeId = null;
  }

  restoreFromLayers(layers: Layer[], activeId: string | null, selectedIds: string[]): void {
    this.layers.clear();
    this.layerOrder = [];
    
    for (const layer of layers) {
      this.layers.set(layer.id, layer);
      this.layerOrder.push(layer.id);
    }
    
    this.activeId = activeId;
    this.selectedIds = new Set(selectedIds);
  }
}
