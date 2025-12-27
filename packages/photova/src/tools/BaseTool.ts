import type { Editor } from '../core/Editor';
import type { ToolType, Point } from '../core/types';

export interface ToolContext {
  editor: Editor;
  canvas: HTMLCanvasElement;
}

export abstract class BaseTool {
  abstract type: ToolType;
  abstract name: string;
  abstract cursor: string;

  protected context: ToolContext | null = null;
  protected isActive: boolean = false;

  attach(context: ToolContext): void {
    this.context = context;
    this.onAttach();
  }

  detach(): void {
    this.onDetach();
    this.context = null;
  }

  activate(): void {
    this.isActive = true;
    this.onActivate();
  }

  deactivate(): void {
    this.isActive = false;
    this.onDeactivate();
  }

  protected onAttach(): void {}
  protected onDetach(): void {}
  protected onActivate(): void {}
  protected onDeactivate(): void {}

  abstract onPointerDown(point: Point, event: PointerEvent): void;
  abstract onPointerMove(point: Point, event: PointerEvent): void;
  abstract onPointerUp(point: Point, event: PointerEvent): void;

  onKeyDown?(event: KeyboardEvent): void;
  onKeyUp?(event: KeyboardEvent): void;
  onWheel?(event: WheelEvent): void;
}
