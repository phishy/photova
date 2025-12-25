export {
  Editor,
  CanvasManager,
  LayerManager,
  HistoryManager,
  ImageLoader,
  EventEmitter,
} from './core';

export type {
  BrightenConfig,
  EditorEvents,
  EditorState,
  Point,
  Size,
  Rectangle,
  Transform,
  Layer,
  ImageLayer,
  TextLayer,
  ShapeLayer,
  DrawingLayer,
  StickerLayer,
  AdjustmentLayer,
  LayerType,
  BlendMode,
  FilterType,
  AppliedFilter,
  FilterPreset,
  FilterDefinition,
  ToolType,
  ToolConfig,
  CropConfig,
  CropPreset,
  FocusType,
  FocusConfig,
  ExportOptions,
  ImageFormat,
  HistoryEntry,
  AIConfig,
  AIProviderConfig,
} from './core';

export { FilterEngine } from './filters';
export type { FilterProcessor } from './filters';

export { BaseTool, CropTool, TransformTool, BrushTool } from './tools';
export type { ToolContext, BrushOptions } from './tools';

export {
  AIProvider,
  AIManager,
  RemoveBgProvider,
  ReplicateProvider,
} from './ai';
export type {
  AIProviderOptions,
  BackgroundRemovalResult,
  EnhanceResult,
  UpscaleResult,
  GenerativeFillResult,
  RemoveBgOptions,
  ReplicateOptions,
  AIFeature,
} from './ai';

export { PluginManager } from './plugins';
export type { Plugin, PluginFactory, PluginContext, HookCallback } from './plugins';

export { EditorUI } from './ui';
export type { EditorUIConfig, EditorUIStyles } from './ui';

import { Editor } from './core';
import { EditorUI } from './ui';
import type { BrightenConfig } from './core';
import type { EditorUIConfig } from './ui';

export function createEditor(config: BrightenConfig): Editor {
  return new Editor(config);
}

export function createEditorUI(config: EditorUIConfig): EditorUI {
  return new EditorUI(config);
}

export default { createEditor, createEditorUI, Editor, EditorUI };
