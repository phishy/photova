import { BaseTool } from './BaseTool';
import type { ToolType, Point, DrawingLayer, DrawingPath } from '../core/types';

export interface BrushOptions {
  color: string;
  size: number;
  opacity: number;
  hardness: number;
}

export class BrushTool extends BaseTool {
  type: ToolType = 'brush';
  name = 'Brush';
  cursor = 'crosshair';

  private options: BrushOptions = {
    color: '#000000',
    size: 10,
    opacity: 1,
    hardness: 1,
  };

  private isDrawing = false;
  private currentPath: Point[] = [];
  private drawingLayerId: string | null = null;

  setOptions(options: Partial<BrushOptions>): void {
    this.options = { ...this.options, ...options };
  }

  getOptions(): BrushOptions {
    return { ...this.options };
  }

  protected onActivate(): void {
    this.ensureDrawingLayer();
  }

  private ensureDrawingLayer(): void {
    if (!this.context) return;

    const layerManager = this.context.editor.getLayerManager();
    const layers = layerManager.getLayers();
    const drawingLayer = layers.find((l) => l.type === 'drawing') as DrawingLayer | undefined;

    if (drawingLayer) {
      this.drawingLayerId = drawingLayer.id;
    } else {
      const newLayer = layerManager.addDrawingLayer({ name: 'Drawing' });
      this.drawingLayerId = newLayer.id;
    }
  }

  onPointerDown(point: Point, _event: PointerEvent): void {
    this.isDrawing = true;
    this.currentPath = [point];
  }

  onPointerMove(point: Point, _event: PointerEvent): void {
    if (!this.isDrawing) return;
    this.currentPath.push(point);
    this.drawCurrentPath();
  }

  onPointerUp(_point: Point, _event: PointerEvent): void {
    if (!this.isDrawing) return;

    this.isDrawing = false;
    this.commitPath();
    this.currentPath = [];
  }

  private drawCurrentPath(): void {
    this.context?.editor.requestRender();
  }

  private commitPath(): void {
    if (!this.context || !this.drawingLayerId || this.currentPath.length < 2) return;

    const layerManager = this.context.editor.getLayerManager();
    const layer = layerManager.getLayer<DrawingLayer>(this.drawingLayerId);
    if (!layer) return;

    const newPath: DrawingPath = {
      points: [...this.currentPath],
      color: this.options.color,
      width: this.options.size,
      opacity: this.options.opacity,
      tool: 'brush',
    };

    layerManager.updateLayer<DrawingLayer>(this.drawingLayerId, {
      paths: [...layer.paths, newPath],
    });

    this.context.editor.saveToHistory('Draw');
  }
}
