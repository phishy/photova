import { EventEmitter } from './EventEmitter';
import type {
  EditorEvents,
  Layer,
  Point,
  Size,
  BlendMode,
  ImageLayer,
  TextLayer,
  ShapeLayer,
  DrawingLayer,
  StickerLayer,
} from './types';

/**
 * Manages the canvas rendering pipeline
 */
export class CanvasManager extends EventEmitter<EditorEvents> {
  private container: HTMLElement;
  private mainCanvas: HTMLCanvasElement;
  private mainCtx: CanvasRenderingContext2D;
  private workCanvas: HTMLCanvasElement;
  private workCtx: CanvasRenderingContext2D;
  private displayCanvas: HTMLCanvasElement;
  private displayCtx: CanvasRenderingContext2D;

  private canvasSize: Size = { width: 0, height: 0 };
  private displaySize: Size = { width: 0, height: 0 };
  private zoom: number = 1;
  private pan: Point = { x: 0, y: 0 };
  private devicePixelRatio: number;
  private isRendering: boolean = false;
  private renderQueued: boolean = false;

  constructor(container: HTMLElement) {
    super();
    this.container = container;
    this.devicePixelRatio = window.devicePixelRatio || 1;

    this.mainCanvas = document.createElement('canvas');
    this.mainCtx = this.mainCanvas.getContext('2d', { willReadFrequently: true })!;

    this.workCanvas = document.createElement('canvas');
    this.workCtx = this.workCanvas.getContext('2d', { willReadFrequently: true })!;

    this.displayCanvas = document.createElement('canvas');
    this.displayCanvas.style.display = 'block';
    this.displayCanvas.style.width = '100%';
    this.displayCanvas.style.height = '100%';
    this.displayCtx = this.displayCtx = this.displayCanvas.getContext('2d')!;

    container.appendChild(this.displayCanvas);
    this.setupResizeObserver();
  }

  private setupResizeObserver(): void {
    const resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        const { width, height } = entry.contentRect;
        this.updateDisplaySize({ width, height });
      }
    });
    resizeObserver.observe(this.container);
  }

  private updateDisplaySize(size: Size): void {
    this.displaySize = size;
    this.displayCanvas.width = size.width * this.devicePixelRatio;
    this.displayCanvas.height = size.height * this.devicePixelRatio;
    this.displayCanvas.style.width = `${size.width}px`;
    this.displayCanvas.style.height = `${size.height}px`;
    this.displayCtx.scale(this.devicePixelRatio, this.devicePixelRatio);
    if (this.canvasSize.width > 0 && this.canvasSize.height > 0) {
      this.fitToView();
    }
    this.queueRender();
  }

  /**
   * Set the canvas size (image dimensions)
   */
  setCanvasSize(size: Size): void {
    this.canvasSize = size;
    this.mainCanvas.width = size.width;
    this.mainCanvas.height = size.height;
    this.workCanvas.width = size.width;
    this.workCanvas.height = size.height;
    this.fitToView();
  }

  /**
   * Get the canvas size
   */
  getCanvasSize(): Size {
    return { ...this.canvasSize };
  }

  /**
   * Fit canvas to view
   */
  fitToView(): void {
    if (this.displaySize.width <= 0 || this.displaySize.height <= 0) {
      return;
    }
    if (this.canvasSize.width <= 0 || this.canvasSize.height <= 0) {
      return;
    }

    const padding = 40;
    const availableWidth = this.displaySize.width - padding * 2;
    const availableHeight = this.displaySize.height - padding * 2;

    if (availableWidth <= 0 || availableHeight <= 0) {
      this.zoom = 0.1;
      return;
    }

    const scaleX = availableWidth / this.canvasSize.width;
    const scaleY = availableHeight / this.canvasSize.height;

    this.zoom = Math.min(scaleX, scaleY, 1);
    this.pan = {
      x: (this.displaySize.width - this.canvasSize.width * this.zoom) / 2,
      y: (this.displaySize.height - this.canvasSize.height * this.zoom) / 2,
    };

    this.emit('zoom:change', { zoom: this.zoom });
    this.emit('pan:change', { pan: this.pan });
    this.queueRender();
  }

  /**
   * Set zoom level
   */
  setZoom(zoom: number, center?: Point): void {
    const oldZoom = this.zoom;
    this.zoom = Math.max(0.1, Math.min(10, zoom));

    if (center) {
      const scale = this.zoom / oldZoom;
      this.pan.x = center.x - (center.x - this.pan.x) * scale;
      this.pan.y = center.y - (center.y - this.pan.y) * scale;
      this.emit('pan:change', { pan: this.pan });
    }

    this.emit('zoom:change', { zoom: this.zoom });
    this.queueRender();
  }

  /**
   * Get current zoom level
   */
  getZoom(): number {
    return this.zoom;
  }

  /**
   * Set pan offset
   */
  setPan(pan: Point): void {
    this.pan = { ...pan };
    this.emit('pan:change', { pan: this.pan });
    this.queueRender();
  }

  /**
   * Get current pan offset
   */
  getPan(): Point {
    return { ...this.pan };
  }

  /**
   * Convert screen coordinates to canvas coordinates
   */
  screenToCanvas(screenPoint: Point): Point {
    const rect = this.displayCanvas.getBoundingClientRect();
    return {
      x: (screenPoint.x - rect.left - this.pan.x) / this.zoom,
      y: (screenPoint.y - rect.top - this.pan.y) / this.zoom,
    };
  }

  /**
   * Convert canvas coordinates to screen coordinates
   */
  canvasToScreen(canvasPoint: Point): Point {
    const rect = this.displayCanvas.getBoundingClientRect();
    return {
      x: canvasPoint.x * this.zoom + this.pan.x + rect.left,
      y: canvasPoint.y * this.zoom + this.pan.y + rect.top,
    };
  }

  /**
   * Queue a render for the next animation frame
   */
  queueRender(): void {
    if (this.renderQueued) return;
    this.renderQueued = true;
    requestAnimationFrame(() => this.performRender());
  }

  private performRender(): void {
    this.renderQueued = false;
    if (this.isRendering) {
      this.queueRender();
      return;
    }
    this.emit('render', undefined);
  }

  /**
   * Render layers to the canvas
   */
  render(layers: Layer[]): void {
    this.isRendering = true;
    this.mainCtx.clearRect(0, 0, this.canvasSize.width, this.canvasSize.height);

    for (const layer of layers) {
      if (!layer.visible) continue;
      this.renderLayer(layer);
    }

    this.renderToDisplay();
    this.isRendering = false;
  }

  private renderLayer(layer: Layer): void {
    const { transform, opacity, blendMode } = layer;

    this.mainCtx.save();
    this.mainCtx.globalCompositeOperation = this.blendModeToComposite(blendMode);
    this.mainCtx.globalAlpha = opacity;
    this.mainCtx.translate(transform.x, transform.y);
    this.mainCtx.rotate(transform.rotation);
    this.mainCtx.scale(transform.scaleX, transform.scaleY);
    this.mainCtx.transform(1, transform.skewY, transform.skewX, 1, 0, 0);

    switch (layer.type) {
      case 'image':
        this.renderImageLayer(layer);
        break;
      case 'text':
        this.renderTextLayer(layer);
        break;
      case 'shape':
        this.renderShapeLayer(layer);
        break;
      case 'drawing':
        this.renderDrawingLayer(layer);
        break;
      case 'sticker':
        this.renderStickerLayer(layer);
        break;
    }

    this.mainCtx.restore();
  }

  private renderImageLayer(layer: ImageLayer): void {
    this.mainCtx.drawImage(layer.source, 0, 0);
  }

  private renderTextLayer(layer: TextLayer): void {
    const ctx = this.mainCtx;

    ctx.font = `${layer.fontStyle} ${layer.fontWeight} ${layer.fontSize}px ${layer.fontFamily}`;
    ctx.textAlign = layer.textAlign;
    ctx.textBaseline = 'top';

    if (layer.shadow) {
      ctx.shadowColor = layer.shadow.color;
      ctx.shadowBlur = layer.shadow.blur;
      ctx.shadowOffsetX = layer.shadow.offsetX;
      ctx.shadowOffsetY = layer.shadow.offsetY;
    }

    const lines = layer.text.split('\n');
    const lineHeightPx = layer.fontSize * layer.lineHeight;

    for (let i = 0; i < lines.length; i++) {
      const y = i * lineHeightPx;

      if (layer.stroke) {
        ctx.strokeStyle = layer.stroke.color;
        ctx.lineWidth = layer.stroke.width;
        ctx.strokeText(lines[i], 0, y);
      }

      ctx.fillStyle = layer.color;
      ctx.fillText(lines[i], 0, y);
    }
  }

  private renderShapeLayer(layer: ShapeLayer): void {
    const ctx = this.mainCtx;
    ctx.beginPath();

    switch (layer.shapeType) {
      case 'rectangle':
        if (layer.cornerRadius) {
          this.roundRect(ctx, 0, 0, 100, 100, layer.cornerRadius);
        } else {
          ctx.rect(0, 0, 100, 100);
        }
        break;
      case 'ellipse':
        ctx.ellipse(50, 50, 50, 50, 0, 0, Math.PI * 2);
        break;
      case 'polygon':
      case 'line':
      case 'arrow':
        if (layer.points && layer.points.length > 0) {
          ctx.moveTo(layer.points[0].x, layer.points[0].y);
          for (let i = 1; i < layer.points.length; i++) {
            ctx.lineTo(layer.points[i].x, layer.points[i].y);
          }
          if (layer.shapeType === 'polygon') {
            ctx.closePath();
          }
        }
        break;
    }

    if (layer.fill) {
      ctx.fillStyle = layer.fill;
      ctx.fill();
    }

    if (layer.stroke) {
      ctx.strokeStyle = layer.stroke.color;
      ctx.lineWidth = layer.stroke.width;
      if (layer.stroke.dashArray) {
        ctx.setLineDash(layer.stroke.dashArray);
      }
      ctx.stroke();
    }
  }

  private roundRect(
    ctx: CanvasRenderingContext2D,
    x: number,
    y: number,
    width: number,
    height: number,
    radius: number
  ): void {
    ctx.moveTo(x + radius, y);
    ctx.lineTo(x + width - radius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
    ctx.lineTo(x + width, y + height - radius);
    ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    ctx.lineTo(x + radius, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
    ctx.lineTo(x, y + radius);
    ctx.quadraticCurveTo(x, y, x + radius, y);
    ctx.closePath();
  }

  private renderDrawingLayer(layer: DrawingLayer): void {
    const ctx = this.mainCtx;

    for (const path of layer.paths) {
      if (path.points.length < 2) continue;

      ctx.beginPath();
      ctx.strokeStyle = path.color;
      ctx.lineWidth = path.width;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.globalAlpha = path.opacity;

      ctx.moveTo(path.points[0].x, path.points[0].y);
      for (let i = 1; i < path.points.length; i++) {
        ctx.lineTo(path.points[i].x, path.points[i].y);
      }
      ctx.stroke();
    }
  }

  private renderStickerLayer(layer: StickerLayer): void {
    this.mainCtx.drawImage(layer.source, 0, 0);
  }

  private renderToDisplay(): void {
    this.displayCtx.clearRect(0, 0, this.displaySize.width, this.displaySize.height);
    this.drawTransparencyPattern();

    this.displayCtx.save();
    this.displayCtx.translate(this.pan.x, this.pan.y);
    this.displayCtx.scale(this.zoom, this.zoom);
    this.displayCtx.drawImage(this.mainCanvas, 0, 0);
    this.displayCtx.restore();
  }

  private drawTransparencyPattern(): void {
    const patternSize = 10;
    const lightColor = '#ffffff';
    const darkColor = '#cccccc';

    const startX = Math.floor(this.pan.x / patternSize) * patternSize;
    const startY = Math.floor(this.pan.y / patternSize) * patternSize;
    const endX = this.pan.x + this.canvasSize.width * this.zoom;
    const endY = this.pan.y + this.canvasSize.height * this.zoom;

    this.displayCtx.save();
    this.displayCtx.beginPath();
    this.displayCtx.rect(this.pan.x, this.pan.y, this.canvasSize.width * this.zoom, this.canvasSize.height * this.zoom);
    this.displayCtx.clip();

    for (let y = startY; y < endY; y += patternSize) {
      for (let x = startX; x < endX; x += patternSize) {
        const isLight = ((x - startX) / patternSize + (y - startY) / patternSize) % 2 === 0;
        this.displayCtx.fillStyle = isLight ? lightColor : darkColor;
        this.displayCtx.fillRect(x, y, patternSize, patternSize);
      }
    }

    this.displayCtx.restore();
  }

  private blendModeToComposite(blendMode: BlendMode): GlobalCompositeOperation {
    const map: Record<BlendMode, GlobalCompositeOperation> = {
      'normal': 'source-over',
      'multiply': 'multiply',
      'screen': 'screen',
      'overlay': 'overlay',
      'darken': 'darken',
      'lighten': 'lighten',
      'color-dodge': 'color-dodge',
      'color-burn': 'color-burn',
      'hard-light': 'hard-light',
      'soft-light': 'soft-light',
      'difference': 'difference',
      'exclusion': 'exclusion',
      'hue': 'hue',
      'saturation': 'saturation',
      'color': 'color',
      'luminosity': 'luminosity',
    };
    return map[blendMode] || 'source-over';
  }

  /**
   * Get the main canvas for operations
   */
  getMainCanvas(): HTMLCanvasElement {
    return this.mainCanvas;
  }

  /**
   * Get the work canvas for temporary operations
   */
  getWorkCanvas(): HTMLCanvasElement {
    return this.workCanvas;
  }

  /**
   * Get the display canvas element
   */
  getDisplayCanvas(): HTMLCanvasElement {
    return this.displayCanvas;
  }

  /**
   * Export the canvas as a data URL or blob
   */
  async export(format: 'png' | 'jpeg' | 'webp' = 'png', quality: number = 0.92): Promise<Blob> {
    return new Promise((resolve, reject) => {
      this.mainCanvas.toBlob(
        (blob) => {
          if (blob) {
            resolve(blob);
          } else {
            reject(new Error('Failed to export canvas'));
          }
        },
        `image/${format}`,
        quality
      );
    });
  }

  /**
   * Export as data URL
   */
  exportDataURL(format: 'png' | 'jpeg' | 'webp' = 'png', quality: number = 0.92): string {
    return this.mainCanvas.toDataURL(`image/${format}`, quality);
  }

  /**
   * Get image data from canvas
   */
  getImageData(x = 0, y = 0, width?: number, height?: number): ImageData {
    return this.mainCtx.getImageData(
      x,
      y,
      width ?? this.canvasSize.width,
      height ?? this.canvasSize.height
    );
  }

  /**
   * Put image data to canvas
   */
  putImageData(imageData: ImageData, x = 0, y = 0): void {
    this.mainCtx.putImageData(imageData, x, y);
    this.queueRender();
  }

  /**
   * Destroy the canvas manager
   */
  destroy(): void {
    this.removeAllListeners();
    this.displayCanvas.remove();
  }
}
