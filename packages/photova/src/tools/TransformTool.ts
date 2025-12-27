import { BaseTool } from './BaseTool';
import type { ToolType, Point, Transform, Layer } from '../core/types';

type TransformHandle = 'nw' | 'n' | 'ne' | 'e' | 'se' | 's' | 'sw' | 'w' | 'rotate' | 'move';

export class TransformTool extends BaseTool {
  type: ToolType = 'transform';
  name = 'Transform';
  cursor = 'move';

  private isDragging = false;
  private startPoint: Point | null = null;
  private activeHandle: TransformHandle | null = null;
  private initialTransform: Transform | null = null;
  private targetLayerId: string | null = null;

  protected onActivate(): void {
    const activeLayer = this.context?.editor.getLayerManager().getActiveLayer();
    if (activeLayer) {
      this.targetLayerId = activeLayer.id;
      this.initialTransform = { ...activeLayer.transform };
    }
  }

  protected onDeactivate(): void {
    this.isDragging = false;
    this.startPoint = null;
    this.activeHandle = null;
    this.initialTransform = null;
    this.targetLayerId = null;
  }

  onPointerDown(point: Point, _event: PointerEvent): void {
    if (!this.context || !this.targetLayerId) return;

    this.activeHandle = this.getHandleAtPoint(point);
    if (!this.activeHandle) {
      this.activeHandle = 'move';
    }

    this.isDragging = true;
    this.startPoint = point;

    const layer = this.context.editor.getLayerManager().getLayer(this.targetLayerId);
    if (layer) {
      this.initialTransform = { ...layer.transform };
    }
  }

  onPointerMove(point: Point, event: PointerEvent): void {
    if (!this.isDragging || !this.startPoint || !this.context || !this.targetLayerId) return;

    const dx = point.x - this.startPoint.x;
    const dy = point.y - this.startPoint.y;

    const layer = this.context.editor.getLayerManager().getLayer(this.targetLayerId);
    if (!layer || !this.initialTransform) return;

    const newTransform = { ...layer.transform };

    switch (this.activeHandle) {
      case 'move':
        newTransform.x = this.initialTransform.x + dx;
        newTransform.y = this.initialTransform.y + dy;
        break;
      case 'rotate':
        const centerX = this.initialTransform.x;
        const centerY = this.initialTransform.y;
        const startAngle = Math.atan2(this.startPoint.y - centerY, this.startPoint.x - centerX);
        const currentAngle = Math.atan2(point.y - centerY, point.x - centerX);
        newTransform.rotation = this.initialTransform.rotation + (currentAngle - startAngle);
        break;
      case 'se':
      case 'nw':
      case 'ne':
      case 'sw':
        const scaleX = 1 + dx / 100;
        const scaleY = 1 + dy / 100;
        if (event.shiftKey) {
          const uniformScale = Math.max(scaleX, scaleY);
          newTransform.scaleX = this.initialTransform.scaleX * uniformScale;
          newTransform.scaleY = this.initialTransform.scaleY * uniformScale;
        } else {
          newTransform.scaleX = this.initialTransform.scaleX * scaleX;
          newTransform.scaleY = this.initialTransform.scaleY * scaleY;
        }
        break;
      case 'e':
      case 'w':
        newTransform.scaleX = this.initialTransform.scaleX * (1 + dx / 100);
        break;
      case 'n':
      case 's':
        newTransform.scaleY = this.initialTransform.scaleY * (1 + dy / 100);
        break;
    }

    this.context.editor.getLayerManager().updateLayer(this.targetLayerId, { transform: newTransform });
  }

  onPointerUp(_point: Point, _event: PointerEvent): void {
    if (this.isDragging && this.context) {
      this.context.editor.saveToHistory('Transform');
    }
    this.isDragging = false;
    this.startPoint = null;
    this.activeHandle = null;
  }

  rotate(degrees: number): void {
    if (!this.context || !this.targetLayerId) return;

    const layer = this.context.editor.getLayerManager().getLayer(this.targetLayerId);
    if (!layer) return;

    const radians = (degrees * Math.PI) / 180;
    this.context.editor.getLayerManager().updateLayer(this.targetLayerId, {
      transform: { ...layer.transform, rotation: layer.transform.rotation + radians },
    });
    this.context.editor.saveToHistory('Rotate');
  }

  flipHorizontal(): void {
    if (!this.context || !this.targetLayerId) return;

    const layer = this.context.editor.getLayerManager().getLayer(this.targetLayerId);
    if (!layer) return;

    this.context.editor.getLayerManager().updateLayer(this.targetLayerId, {
      transform: { ...layer.transform, scaleX: layer.transform.scaleX * -1 },
    });
    this.context.editor.saveToHistory('Flip Horizontal');
  }

  flipVertical(): void {
    if (!this.context || !this.targetLayerId) return;

    const layer = this.context.editor.getLayerManager().getLayer(this.targetLayerId);
    if (!layer) return;

    this.context.editor.getLayerManager().updateLayer(this.targetLayerId, {
      transform: { ...layer.transform, scaleY: layer.transform.scaleY * -1 },
    });
    this.context.editor.saveToHistory('Flip Vertical');
  }

  private getHandleAtPoint(_point: Point): TransformHandle | null {
    return null;
  }
}
