/**
 * Core types for the Brighten photo editor SDK
 */

// ============================================================================
// Geometry Types
// ============================================================================

export interface Point {
  x: number;
  y: number;
}

export interface Size {
  width: number;
  height: number;
}

export interface Rectangle {
  x: number;
  y: number;
  width: number;
  height: number;
}

export interface Transform {
  x: number;
  y: number;
  scaleX: number;
  scaleY: number;
  rotation: number; // in radians
  skewX: number;
  skewY: number;
}

// ============================================================================
// Image Types
// ============================================================================

export type ImageFormat = 'png' | 'jpeg' | 'webp';

export interface ExportOptions {
  format: ImageFormat;
  quality: number; // 0-1 for jpeg/webp
  width?: number;
  height?: number;
  maintainAspectRatio?: boolean;
}

export interface ImageData {
  width: number;
  height: number;
  data: Uint8ClampedArray;
}

// ============================================================================
// Layer Types
// ============================================================================

export type LayerType = 'image' | 'text' | 'shape' | 'drawing' | 'sticker' | 'adjustment';

export interface LayerBase {
  id: string;
  type: LayerType;
  name: string;
  visible: boolean;
  locked: boolean;
  opacity: number; // 0-1
  blendMode: BlendMode;
  transform: Transform;
}

export interface ImageLayer extends LayerBase {
  type: 'image';
  source: HTMLImageElement | HTMLCanvasElement;
  originalSource?: HTMLImageElement;
  filters: AppliedFilter[];
}

export interface TextLayer extends LayerBase {
  type: 'text';
  text: string;
  fontFamily: string;
  fontSize: number;
  fontWeight: string;
  fontStyle: string;
  color: string;
  textAlign: 'left' | 'center' | 'right';
  lineHeight: number;
  letterSpacing: number;
  stroke?: {
    color: string;
    width: number;
  };
  shadow?: {
    color: string;
    blur: number;
    offsetX: number;
    offsetY: number;
  };
}

export interface ShapeLayer extends LayerBase {
  type: 'shape';
  shapeType: 'rectangle' | 'ellipse' | 'polygon' | 'line' | 'arrow';
  fill?: string;
  stroke?: {
    color: string;
    width: number;
    dashArray?: number[];
  };
  points?: Point[]; // For polygon/line/arrow
  cornerRadius?: number; // For rectangle
}

export interface DrawingLayer extends LayerBase {
  type: 'drawing';
  paths: DrawingPath[];
}

export interface DrawingPath {
  points: Point[];
  color: string;
  width: number;
  opacity: number;
  tool: 'brush' | 'eraser' | 'blur';
}

export interface StickerLayer extends LayerBase {
  type: 'sticker';
  source: HTMLImageElement;
  category?: string;
}

export interface AdjustmentLayer extends LayerBase {
  type: 'adjustment';
  adjustmentType: AdjustmentType;
  settings: AdjustmentSettings;
  mask?: HTMLCanvasElement;
}

export type Layer = 
  | ImageLayer 
  | TextLayer 
  | ShapeLayer 
  | DrawingLayer 
  | StickerLayer 
  | AdjustmentLayer;

// ============================================================================
// Filter Types
// ============================================================================

export type FilterType = 
  | 'brightness'
  | 'contrast'
  | 'saturation'
  | 'hue'
  | 'exposure'
  | 'highlights'
  | 'shadows'
  | 'temperature'
  | 'tint'
  | 'vibrance'
  | 'clarity'
  | 'sharpen'
  | 'blur'
  | 'noise'
  | 'vignette'
  | 'grain'
  | 'sepia'
  | 'grayscale'
  | 'invert'
  | 'custom';

export interface FilterDefinition {
  type: FilterType;
  name: string;
  defaultValue: number;
  min: number;
  max: number;
  step: number;
}

export interface AppliedFilter {
  type: FilterType;
  value: number;
  enabled: boolean;
}

export interface FilterPreset {
  id: string;
  name: string;
  category: string;
  thumbnail?: string;
  filters: AppliedFilter[];
}

// ============================================================================
// Adjustment Types
// ============================================================================

export type AdjustmentType = 
  | 'curves'
  | 'levels'
  | 'colorBalance'
  | 'hueSaturation'
  | 'selectiveColor';

export interface CurvesSettings {
  rgb: Point[];
  red: Point[];
  green: Point[];
  blue: Point[];
}

export interface LevelsSettings {
  inputBlack: number;
  inputWhite: number;
  inputGamma: number;
  outputBlack: number;
  outputWhite: number;
}

export interface ColorBalanceSettings {
  shadows: { cyan: number; magenta: number; yellow: number };
  midtones: { cyan: number; magenta: number; yellow: number };
  highlights: { cyan: number; magenta: number; yellow: number };
}

export interface HueSaturationSettings {
  hue: number;
  saturation: number;
  lightness: number;
}

export type AdjustmentSettings = 
  | CurvesSettings 
  | LevelsSettings 
  | ColorBalanceSettings 
  | HueSaturationSettings;

// ============================================================================
// Blend Modes
// ============================================================================

export type BlendMode = 
  | 'normal'
  | 'multiply'
  | 'screen'
  | 'overlay'
  | 'darken'
  | 'lighten'
  | 'color-dodge'
  | 'color-burn'
  | 'hard-light'
  | 'soft-light'
  | 'difference'
  | 'exclusion'
  | 'hue'
  | 'saturation'
  | 'color'
  | 'luminosity';

// ============================================================================
// Tool Types
// ============================================================================

export type ToolType = 
  | 'select'
  | 'ai'
  | 'crop'
  | 'rotate'
  | 'transform'
  | 'filter'
  | 'adjust'
  | 'brush'
  | 'eraser'
  | 'blur-brush'
  | 'text'
  | 'shape'
  | 'sticker'
  | 'focus'
  | 'layers'
  | 'hand'
  | 'zoom';

export interface ToolConfig {
  type: ToolType;
  name: string;
  icon: string;
  cursor: string;
  options: Record<string, unknown>;
}

// ============================================================================
// Crop Types
// ============================================================================

export interface CropConfig {
  aspectRatio?: number; // width/height, undefined = free
  minWidth?: number;
  minHeight?: number;
  maxWidth?: number;
  maxHeight?: number;
}

export interface CropPreset {
  id: string;
  name: string;
  aspectRatio?: number;
  icon?: string;
}

// ============================================================================
// Focus/Blur Types
// ============================================================================

export type FocusType = 'radial' | 'linear' | 'mirrored';

export interface FocusConfig {
  type: FocusType;
  position: Point;
  size: number;
  feather: number;
  rotation?: number; // for linear/mirrored
}

// ============================================================================
// Editor State
// ============================================================================

export interface EditorState {
  layers: Layer[];
  activeLayerId: string | null;
  selectedLayerIds: string[];
  activeTool: ToolType;
  zoom: number;
  pan: Point;
  canvasSize: Size;
  originalImageSize: Size;
  isDirty: boolean;
}

export interface HistoryEntry {
  id: string;
  timestamp: number;
  action: string;
  state: EditorState;
}

// ============================================================================
// Editor Events
// ============================================================================

export interface EditorEvents {
  'image:load': { width: number; height: number };
  'image:export': { format: ImageFormat; size: Size };
  'layer:add': { layer: Layer };
  'layer:remove': { layerId: string };
  'layer:update': { layerId: string; changes: Partial<Layer> };
  'layer:select': { layerIds: string[] };
  'layer:reorder': { layerIds: string[] };
  'tool:change': { tool: ToolType; previousTool: ToolType };
  'filter:apply': { filter: AppliedFilter };
  'filter:remove': { filterType: FilterType };
  'history:undo': { entry: HistoryEntry };
  'history:redo': { entry: HistoryEntry };
  'history:change': { canUndo: boolean; canRedo: boolean };
  'zoom:change': { zoom: number };
  'pan:change': { pan: Point };
  'crop:start': { config: CropConfig };
  'crop:apply': { rect: Rectangle };
  'crop:cancel': void;
  'transform:start': { layerId: string };
  'transform:apply': { layerId: string; transform: Transform };
  'transform:cancel': { layerId: string };
  'render': void;
  'error': { message: string; error?: Error };
}

// ============================================================================
// Configuration
// ============================================================================

export interface BrightenConfig {
  container: HTMLElement | string;
  image?: string | HTMLImageElement | HTMLCanvasElement;
  width?: number;
  height?: number;
  tools?: ToolType[];
  filters?: FilterType[];
  presets?: FilterPreset[];
  cropPresets?: CropPreset[];
  maxHistorySteps?: number;
  enableKeyboardShortcuts?: boolean;
  enableTouchGestures?: boolean;
  theme?: 'light' | 'dark' | 'auto';
  locale?: string;
  ai?: AIConfig;
}

export interface AIConfig {
  enabled: boolean;
  providers?: {
    backgroundRemoval?: AIProviderConfig;
    upscale?: AIProviderConfig;
    enhance?: AIProviderConfig;
    generativeFill?: AIProviderConfig;
  };
}

export interface AIProviderConfig {
  provider: string;
  apiKey?: string;
  endpoint?: string;
  options?: Record<string, unknown>;
}
