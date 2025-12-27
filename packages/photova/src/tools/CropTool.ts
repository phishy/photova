import { BaseTool } from './BaseTool';
import type { ToolType, Point, Rectangle, CropConfig } from '../core/types';

export class CropTool extends BaseTool {
  type: ToolType = 'crop';
  name = 'Crop';
  cursor = 'crosshair';

  private isDragging = false;
  private startPoint: Point | null = null;
  private cropRect: Rectangle | null = null;
  private aspectRatio: number | undefined;
  private activeHandle: string | null = null;

  setAspectRatio(ratio: number | undefined): void {
    this.aspectRatio = ratio;
    if (this.cropRect && ratio) {
      this.cropRect.height = this.cropRect.width / ratio;
    }
  }

  getCropRect(): Rectangle | null {
    return this.cropRect ? { ...this.cropRect } : null;
  }

  setCropRect(rect: Rectangle): void {
    this.cropRect = { ...rect };
  }

  protected onActivate(): void {
    if (!this.context) return;
    const size = this.context.editor.getCanvasSize();
    this.cropRect = {
      x: size.width * 0.1,
      y: size.height * 0.1,
      width: size.width * 0.8,
      height: size.height * 0.8,
    };
  }

  protected onDeactivate(): void {
    this.cropRect = null;
    this.isDragging = false;
    this.startPoint = null;
    this.activeHandle = null;
  }

  onPointerDown(point: Point, _event: PointerEvent): void {
    if (!this.cropRect) return;

    this.activeHandle = this.getHandleAtPoint(point);
    this.isDragging = true;
    this.startPoint = point;
  }

  onPointerMove(point: Point, _event: PointerEvent): void {
    if (!this.isDragging || !this.startPoint || !this.cropRect) return;

    const dx = point.x - this.startPoint.x;
    const dy = point.y - this.startPoint.y;

    if (this.activeHandle) {
      this.resizeCropRect(this.activeHandle, dx, dy);
    } else if (this.isPointInCropRect(this.startPoint)) {
      this.cropRect.x += dx;
      this.cropRect.y += dy;
    }

    this.startPoint = point;
    this.context?.editor.requestRender();
  }

  onPointerUp(_point: Point, _event: PointerEvent): void {
    this.isDragging = false;
    this.startPoint = null;
    this.activeHandle = null;
  }

  apply(): Rectangle | null {
    if (!this.cropRect || !this.context) return null;

    const rect = { ...this.cropRect };
    const size = this.context.editor.getCanvasSize();

    rect.x = Math.max(0, Math.min(rect.x, size.width - rect.width));
    rect.y = Math.max(0, Math.min(rect.y, size.height - rect.height));
    rect.width = Math.min(rect.width, size.width - rect.x);
    rect.height = Math.min(rect.height, size.height - rect.y);

    return rect;
  }

  cancel(): void {
    this.cropRect = null;
  }

  private getHandleAtPoint(point: Point): string | null {
    if (!this.cropRect) return null;

    const handleSize = 10;
    const { x, y, width, height } = this.cropRect;
    const handles = [
      { name: 'nw', x: x, y: y },
      { name: 'n', x: x + width / 2, y: y },
      { name: 'ne', x: x + width, y: y },
      { name: 'e', x: x + width, y: y + height / 2 },
      { name: 'se', x: x + width, y: y + height },
      { name: 's', x: x + width / 2, y: y + height },
      { name: 'sw', x: x, y: y + height },
      { name: 'w', x: x, y: y + height / 2 },
    ];

    for (const handle of handles) {
      if (
        Math.abs(point.x - handle.x) <= handleSize &&
        Math.abs(point.y - handle.y) <= handleSize
      ) {
        return handle.name;
      }
    }

    return null;
  }

  private isPointInCropRect(point: Point): boolean {
    if (!this.cropRect) return false;
    const { x, y, width, height } = this.cropRect;
    return point.x >= x && point.x <= x + width && point.y >= y && point.y <= y + height;
  }

  private resizeCropRect(handle: string, dx: number, dy: number): void {
    if (!this.cropRect) return;

    const minSize = 20;

    switch (handle) {
      case 'nw':
        this.cropRect.x += dx;
        this.cropRect.y += dy;
        this.cropRect.width -= dx;
        this.cropRect.height -= dy;
        break;
      case 'n':
        this.cropRect.y += dy;
        this.cropRect.height -= dy;
        break;
      case 'ne':
        this.cropRect.y += dy;
        this.cropRect.width += dx;
        this.cropRect.height -= dy;
        break;
      case 'e':
        this.cropRect.width += dx;
        break;
      case 'se':
        this.cropRect.width += dx;
        this.cropRect.height += dy;
        break;
      case 's':
        this.cropRect.height += dy;
        break;
      case 'sw':
        this.cropRect.x += dx;
        this.cropRect.width -= dx;
        this.cropRect.height += dy;
        break;
      case 'w':
        this.cropRect.x += dx;
        this.cropRect.width -= dx;
        break;
    }

    if (this.cropRect.width < minSize) {
      this.cropRect.width = minSize;
    }
    if (this.cropRect.height < minSize) {
      this.cropRect.height = minSize;
    }

    if (this.aspectRatio) {
      if (handle.includes('e') || handle.includes('w')) {
        this.cropRect.height = this.cropRect.width / this.aspectRatio;
      } else {
        this.cropRect.width = this.cropRect.height * this.aspectRatio;
      }
    }
  }
}
