import { Editor } from '../core/Editor';
import { FilterEngine } from '../filters/FilterEngine';
import { BrushTool } from '../tools/BrushTool';
import type { ToolType, FilterPreset, Layer, ImageLayer, Rectangle, Point } from '../core/types';
import { injectStyles } from './styles';
import { icons } from './icons';

export interface EditorUIStyles {
  background?: string;
  surface?: string;
  surfaceHover?: string;
  border?: string;
  text?: string;
  textSecondary?: string;
  primary?: string;
  primaryHover?: string;
  danger?: string;
  success?: string;
  radius?: string;
  fontFamily?: string;
}

export interface EditorUIConfig {
  container: HTMLElement | string;
  image?: string;
  theme?: 'light' | 'dark';
  tools?: ToolType[];
  showHeader?: boolean;
  showSidebar?: boolean;
  showPanel?: boolean;
  apiEndpoint?: string;
  styles?: EditorUIStyles;
  unstyled?: boolean;
  onExport?: (blob: Blob) => void;
  onClose?: () => void;
}

type PanelType = 'filters' | 'adjust' | 'layers' | 'text' | 'shapes' | 'crop' | 'transform' | 'brush' | 'ai' | null;

const DEFAULT_TOOLS: ToolType[] = ['select', 'ai', 'crop', 'transform', 'filter', 'adjust', 'brush', 'text', 'shape', 'layers'];

export class EditorUI {
  private config: EditorUIConfig;
  private container: HTMLElement;
  private editor: Editor;
  private filterEngine: FilterEngine;
  private root: HTMLElement;
  private currentPanel: PanelType = null;
  private currentTool: ToolType = 'select';
  private adjustments: Record<string, number> = {};
  private originalImageData: ImageData | null = null;
  private currentPreset: string | null = null;
  private cropRect: Rectangle | null = null;
  private cropOverlay: HTMLDivElement | null = null;
  private brushOptions = { color: '#60a5fa', size: 10, opacity: 100 };
  private keyboardHandler: ((e: KeyboardEvent) => void) | null = null;
  private adjustmentHistoryTimeout: ReturnType<typeof setTimeout> | null = null;
  private cropDragState: {
    active: boolean;
    handle: string | null;
    startX: number;
    startY: number;
    startRect: Rectangle | null;
  } = { active: false, handle: null, startX: 0, startY: 0, startRect: null };
  private cropMouseMoveHandler: ((e: MouseEvent) => void) | null = null;
  private cropMouseUpHandler: ((e: MouseEvent) => void) | null = null;
  private filterPreviewCache: Map<string, string> = new Map();
  private filterPreviewSource: string | null = null;
  private panState: {
    active: boolean;
    startX: number;
    startY: number;
    startPanX: number;
    startPanY: number;
  } = { active: false, startX: 0, startY: 0, startPanX: 0, startPanY: 0 };
  private panMouseMoveHandler: ((e: MouseEvent) => void) | null = null;
  private panMouseUpHandler: ((e: MouseEvent) => void) | null = null;
  private inpaintMode = false;
  private maskCanvas: HTMLCanvasElement | null = null;
  private maskCtx: CanvasRenderingContext2D | null = null;
  private maskOverlay: HTMLDivElement | null = null;
  private inpaintDrawState: { drawing: boolean; lastX: number; lastY: number } = { drawing: false, lastX: 0, lastY: 0 };
  private inpaintBrushSize = 60;
  private brushTool: BrushTool | null = null;
  private brushMouseMoveHandler: ((e: MouseEvent) => void) | null = null;
  private brushMouseUpHandler: (() => void) | null = null;

  constructor(config: EditorUIConfig) {
    this.config = {
      theme: 'dark',
      tools: DEFAULT_TOOLS,
      showHeader: true,
      showSidebar: true,
      showPanel: true,
      ...config,
    };

    this.container = this.resolveContainer(config.container);
    this.filterEngine = new FilterEngine();

    if (!config.unstyled) {
      injectStyles();
    }
    this.root = this.createRoot();
    this.applyCustomStyles();
    this.container.appendChild(this.root);

    const canvasContainer = this.root.querySelector('.brighten-canvas-container') as HTMLElement;
    this.editor = new Editor({ container: canvasContainer });

    this.initializeBrushTool(canvasContainer);
    this.setupEventListeners();
    this.resetAdjustments();

    if (config.image) {
      this.loadImage(config.image);
    }
  }

  private resolveContainer(container: HTMLElement | string): HTMLElement {
    if (typeof container === 'string') {
      const el = document.querySelector(container);
      if (!el) throw new Error(`Container not found: ${container}`);
      return el as HTMLElement;
    }
    return container;
  }

  private applyCustomStyles(): void {
    const styles = this.config.styles;
    if (!styles) return;

    const varMap: Record<keyof EditorUIStyles, string> = {
      background: '--brighten-bg',
      surface: '--brighten-surface',
      surfaceHover: '--brighten-surface-hover',
      border: '--brighten-border',
      text: '--brighten-text',
      textSecondary: '--brighten-text-secondary',
      primary: '--brighten-primary',
      primaryHover: '--brighten-primary-hover',
      danger: '--brighten-danger',
      success: '--brighten-success',
      radius: '--brighten-radius',
      fontFamily: 'font-family',
    };

    for (const [key, value] of Object.entries(styles)) {
      if (value && varMap[key as keyof EditorUIStyles]) {
        this.root.style.setProperty(varMap[key as keyof EditorUIStyles], value);
      }
    }
  }

  private createRoot(): HTMLElement {
    const root = document.createElement('div');
    root.className = `brighten-editor ${this.config.theme === 'light' ? 'brighten-light' : ''}`;

    root.innerHTML = `
      ${this.config.showHeader ? this.renderHeader() : ''}
      <div class="brighten-main">
        ${this.config.showSidebar ? this.renderSidebar() : ''}
        <div class="brighten-canvas-container"></div>
        ${this.config.showPanel ? '<div class="brighten-panel"></div>' : ''}
      </div>
    `;

    return root;
  }

  private renderHeader(): string {
    return `
      <header class="brighten-header">
        <div class="brighten-header-left">
          <button class="brighten-btn brighten-btn-icon" data-action="undo" title="Undo">
            ${icons.undo}
          </button>
          <button class="brighten-btn brighten-btn-icon" data-action="redo" title="Redo">
            ${icons.redo}
          </button>
        </div>
        <div class="brighten-header-center">
          <div class="brighten-zoom-controls">
            <button class="brighten-btn brighten-btn-icon" data-action="zoom-out" title="Zoom Out">
              ${icons.zoomOut}
            </button>
            <span class="brighten-zoom-value">100%</span>
            <button class="brighten-btn brighten-btn-icon" data-action="zoom-in" title="Zoom In">
              ${icons.zoomIn}
            </button>
          </div>
        </div>
        <div class="brighten-header-right">
          <button class="brighten-btn" data-action="open" title="Open Image">
            ${icons.upload} Open
          </button>
          <button class="brighten-btn brighten-btn-primary" data-action="export" title="Export">
            ${icons.download} Export
          </button>
          ${this.config.onClose ? `<button class="brighten-btn brighten-btn-icon" data-action="close" title="Close">${icons.close}</button>` : ''}
        </div>
      </header>
    `;
  }

  private renderSidebar(): string {
    const allTools = [
      { type: 'select', icon: 'select', label: 'Select' },
      { type: 'ai', icon: 'sparkles', label: 'AI', panel: 'ai' },
      { type: 'crop', icon: 'crop', label: 'Crop', panel: 'crop' },
      { type: 'transform', icon: 'transform', label: 'Transform', panel: 'transform' },
      { type: 'filter', icon: 'filter', label: 'Filters', panel: 'filters' },
      { type: 'adjust', icon: 'adjust', label: 'Adjust', panel: 'adjust' },
      { type: 'brush', icon: 'brush', label: 'Brush', panel: 'brush' },
      { type: 'text', icon: 'text', label: 'Text', panel: 'text' },
      { type: 'shape', icon: 'shapes', label: 'Shapes', panel: 'shapes' },
      { type: 'layers', icon: 'layers', label: 'Layers', panel: 'layers' },
    ];

    const enabledTools = this.config.tools || DEFAULT_TOOLS;
    const tools = allTools.filter(tool => enabledTools.includes(tool.type as ToolType));

    return `
      <aside class="brighten-sidebar">
        ${tools
          .map(
            (tool) => `
          <button class="brighten-tool-btn ${this.currentTool === tool.type ? 'active' : ''}" 
                  data-tool="${tool.type}" 
                  ${tool.panel ? `data-panel="${tool.panel}"` : ''}>
            ${icons[tool.icon as keyof typeof icons] || ''}
            <span>${tool.label}</span>
          </button>
        `
          )
          .join('')}
      </aside>
    `;
  }

  private renderFiltersPanel(): string {
    const byCategory = this.filterEngine.getPresetsByCategory();
    this.generateFilterPreviews();
    const nonePreview = this.filterPreviewCache.get('none') || '';
    
    return `
      <div class="brighten-panel-header">
        <span>Filters</span>
      </div>
      <div class="brighten-panel-content">
        <div class="brighten-presets-grid" style="margin-bottom: 16px;">
          <button class="brighten-preset ${this.currentPreset === null ? 'active' : ''}" data-preset="none">
            <div class="brighten-preset-preview" style="background-image: url(${nonePreview}); background-size: cover; background-position: center;"></div>
            <span class="brighten-preset-name">None</span>
          </button>
        </div>
        ${Array.from(byCategory.entries())
          .map(
            ([category, categoryPresets]) => `
          <div style="margin-bottom: 16px;">
            <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">${category}</div>
            <div class="brighten-presets-grid">
              ${categoryPresets
                .map(
                  (preset) => {
                    const preview = this.filterPreviewCache.get(preset.id) || '';
                    return `
                <button class="brighten-preset ${this.currentPreset === preset.id ? 'active' : ''}" data-preset="${preset.id}">
                  <div class="brighten-preset-preview" style="background-image: url(${preview}); background-size: cover; background-position: center;"></div>
                  <span class="brighten-preset-name">${preset.name}</span>
                </button>
              `;
                  }
                )
                .join('')}
            </div>
          </div>
        `
          )
          .join('')}
      </div>
    `;
  }

  private generateFilterPreviews(): void {
    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    const source = imageLayer.source;
    const sourceId = source instanceof HTMLImageElement ? source.src : 'canvas';
    
    if (this.filterPreviewSource === sourceId && this.filterPreviewCache.size > 0) {
      return;
    }

    this.filterPreviewSource = sourceId;
    this.filterPreviewCache.clear();

    const thumbSize = 60;
    const thumbCanvas = document.createElement('canvas');
    const sourceWidth = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    const sourceHeight = source instanceof HTMLImageElement ? source.naturalHeight : source.height;
    const scale = Math.min(thumbSize / sourceWidth, thumbSize / sourceHeight);
    thumbCanvas.width = Math.round(sourceWidth * scale);
    thumbCanvas.height = Math.round(sourceHeight * scale);
    const thumbCtx = thumbCanvas.getContext('2d')!;
    thumbCtx.drawImage(source, 0, 0, thumbCanvas.width, thumbCanvas.height);
    const thumbImageData = thumbCtx.getImageData(0, 0, thumbCanvas.width, thumbCanvas.height);

    this.filterPreviewCache.set('none', thumbCanvas.toDataURL('image/jpeg', 0.7));

    const presets = this.filterEngine.getPresets();
    for (const preset of presets) {
      const previewData = new ImageData(
        new Uint8ClampedArray(thumbImageData.data),
        thumbImageData.width,
        thumbImageData.height
      );
      const filtered = this.filterEngine.applyPreset(previewData, preset.id);
      thumbCtx.putImageData(filtered, 0, 0);
      this.filterPreviewCache.set(preset.id, thumbCanvas.toDataURL('image/jpeg', 0.7));
    }
  }

  private renderAdjustPanel(): string {
    const adjustments = [
      { id: 'brightness', label: 'Brightness', min: -100, max: 100, default: 0 },
      { id: 'contrast', label: 'Contrast', min: -100, max: 100, default: 0 },
      { id: 'saturation', label: 'Saturation', min: -100, max: 100, default: 0 },
      { id: 'exposure', label: 'Exposure', min: -100, max: 100, default: 0 },
      { id: 'temperature', label: 'Temperature', min: -100, max: 100, default: 0 },
      { id: 'tint', label: 'Tint', min: -100, max: 100, default: 0 },
      { id: 'vibrance', label: 'Vibrance', min: -100, max: 100, default: 0 },
      { id: 'sharpen', label: 'Sharpen', min: 0, max: 100, default: 0 },
      { id: 'vignette', label: 'Vignette', min: 0, max: 100, default: 0 },
    ];

    return `
      <div class="brighten-panel-header">
        <span>Adjustments</span>
        <button class="brighten-btn" data-action="reset-adjustments" style="padding: 4px 8px; font-size: 12px;">Reset</button>
      </div>
      <div class="brighten-panel-content">
        ${adjustments
          .map(
            (adj) => `
          <div class="brighten-slider-group">
            <div class="brighten-slider-label">
              <span>${adj.label}</span>
              <span data-value="${adj.id}">${this.adjustments[adj.id] ?? adj.default}</span>
            </div>
            <input type="range" class="brighten-slider" 
                   data-adjust="${adj.id}"
                   min="${adj.min}" max="${adj.max}" 
                   value="${this.adjustments[adj.id] ?? adj.default}">
          </div>
        `
          )
          .join('')}
      </div>
    `;
  }

  private renderLayersPanel(): string {
    const layers = this.editor.getLayerManager().getLayers();
    const activeId = this.editor.getLayerManager().getActiveId();

    return `
      <div class="brighten-panel-header">
        <span>Layers</span>
        <button class="brighten-btn brighten-btn-icon" data-action="add-layer" style="padding: 4px;">
          ${icons.plus}
        </button>
      </div>
      <div class="brighten-panel-content">
        <div class="brighten-layers-list">
          ${layers
            .slice()
            .reverse()
            .map(
              (layer) => `
            <div class="brighten-layer-item ${layer.id === activeId ? 'active' : ''}" data-layer="${layer.id}">
              <div class="brighten-layer-thumb"></div>
              <div class="brighten-layer-info">
                <div class="brighten-layer-name">${layer.name}</div>
                <div class="brighten-layer-type">${layer.type}</div>
              </div>
              <button class="brighten-btn brighten-btn-icon" data-action="toggle-visibility" data-layer="${layer.id}" style="padding: 4px;">
                ${layer.visible ? icons.eye : icons.eyeOff}
              </button>
            </div>
          `
            )
            .join('')}
        </div>
      </div>
    `;
  }

  private renderTextPanel(): string {
    return `
      <div class="brighten-panel-header">
        <span>Text</span>
      </div>
      <div class="brighten-panel-content">
        <button class="brighten-btn" data-action="add-text" style="width: 100%;">
          ${icons.plus} Add Text
        </button>
        <div style="margin-top: 16px;">
          <div class="brighten-slider-group">
            <div class="brighten-slider-label">
              <span>Font Size</span>
              <span data-value="fontSize">24</span>
            </div>
            <input type="range" class="brighten-slider" data-text="fontSize" min="8" max="200" value="24">
          </div>
        </div>
      </div>
    `;
  }

  private renderShapesPanel(): string {
    const shapes = [
      { type: 'rectangle', label: 'Rectangle' },
      { type: 'ellipse', label: 'Ellipse' },
      { type: 'line', label: 'Line' },
    ];

    return `
      <div class="brighten-panel-header">
        <span>Shapes</span>
      </div>
      <div class="brighten-panel-content">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
          ${shapes
            .map(
              (shape) => `
            <button class="brighten-btn" data-action="add-shape" data-shape="${shape.type}">
              ${shape.label}
            </button>
          `
            )
            .join('')}
        </div>
      </div>
    `;
  }

  private renderCropPanel(): string {
    const presets = [
      { id: 'free', label: 'Free', ratio: null },
      { id: 'square', label: '1:1', ratio: 1 },
      { id: '4:3', label: '4:3', ratio: 4/3 },
      { id: '16:9', label: '16:9', ratio: 16/9 },
      { id: '3:2', label: '3:2', ratio: 3/2 },
      { id: '9:16', label: '9:16', ratio: 9/16 },
    ];

    return `
      <div class="brighten-panel-header">
        <span>Crop</span>
        <div style="display: flex; gap: 4px;">
          <button class="brighten-btn" data-action="cancel-crop" style="padding: 4px 8px; font-size: 12px;">Cancel</button>
          <button class="brighten-btn brighten-btn-primary" data-action="apply-crop" style="padding: 4px 8px; font-size: 12px;">Apply</button>
        </div>
      </div>
      <div class="brighten-panel-content">
        <div style="margin-bottom: 16px;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Aspect Ratio</div>
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
            ${presets.map(p => `
              <button class="brighten-btn" data-action="set-crop-ratio" data-ratio="${p.ratio ?? 'free'}" style="padding: 8px; font-size: 12px;">
                ${p.label}
              </button>
            `).join('')}
          </div>
        </div>
        <div style="font-size: 12px; color: var(--brighten-text-secondary);">
          Drag the corners or edges to adjust the crop area.
        </div>
      </div>
    `;
  }

  private renderTransformPanel(): string {
    return `
      <div class="brighten-panel-header">
        <span>Transform</span>
      </div>
      <div class="brighten-panel-content">
        <div style="margin-bottom: 16px;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Rotate</div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
            <button class="brighten-btn" data-action="rotate-ccw" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
              <span style="display: inline-block; width: 20px; height: 20px; transform: scaleX(-1);">${icons.rotate}</span>
              90° Left
            </button>
            <button class="brighten-btn" data-action="rotate-cw" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
              <span style="display: inline-block; width: 20px; height: 20px;">${icons.rotate}</span>
              90° Right
            </button>
          </div>
        </div>
        <div style="margin-bottom: 16px;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Flip</div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
            <button class="brighten-btn" data-action="flip-h" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
              <span style="display: inline-block; width: 20px; height: 20px;">${icons.flipH}</span>
              Horizontal
            </button>
            <button class="brighten-btn" data-action="flip-v" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px;">
              <span style="display: inline-block; width: 20px; height: 20px;">${icons.flipV}</span>
              Vertical
            </button>
          </div>
        </div>
        <div style="font-size: 12px; color: var(--brighten-text-secondary);">
          Select a layer first, then use these controls to transform it.
        </div>
      </div>
    `;
  }

  private renderAIPanel(): string {
    const hasApiEndpoint = !!this.config.apiEndpoint;
    const iconStyle = 'display: inline-block; width: 16px; height: 16px; vertical-align: middle; margin-right: 6px;';
    return `
      <div class="brighten-panel-header">
        <span>AI Tools</span>
      </div>
      <div class="brighten-panel-content">
        ${hasApiEndpoint ? `
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <button class="brighten-btn" data-action="remove-background" style="width: 100%; justify-content: flex-start;">
              <span style="${iconStyle}">${icons.magic}</span> Remove Background
            </button>
            <button class="brighten-btn" data-action="unblur" style="width: 100%; justify-content: flex-start;">
              <span style="${iconStyle}">${icons.focus}</span> Unblur / Enhance
            </button>
            <button class="brighten-btn" data-action="colorize" style="width: 100%; justify-content: flex-start;">
              <span style="${iconStyle}">${icons.palette}</span> Colorize
            </button>
            <button class="brighten-btn" data-action="restore" style="width: 100%; justify-content: flex-start;">
              <span style="${iconStyle}">${icons.magic}</span> Restore Photo
            </button>
            <button class="brighten-btn" data-action="start-inpaint" style="width: 100%; justify-content: flex-start;">
              <span style="${iconStyle}">${icons.eraser}</span> Remove Objects
            </button>
          </div>
          ${this.inpaintMode ? `
          <div style="margin-top: 12px; padding: 12px; background: var(--brighten-bg); border-radius: 6px;">
            <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Paint over objects to remove</div>
            <div class="brighten-slider-group" style="margin-bottom: 12px;">
              <div class="brighten-slider-label">
                <span>Brush Size</span>
                <span data-value="inpaintBrush">${this.inpaintBrushSize}</span>
              </div>
              <input type="range" class="brighten-slider" data-inpaint="brushSize" min="5" max="100" value="${this.inpaintBrushSize}">
            </div>
            <div style="display: flex; gap: 8px;">
              <button class="brighten-btn" data-action="cancel-inpaint" style="flex: 1;">Cancel</button>
              <button class="brighten-btn brighten-btn-primary" data-action="apply-inpaint" style="flex: 1;">Remove</button>
            </div>
          </div>
          ` : `
          <div style="margin-top: 12px; font-size: 12px; color: var(--brighten-text-secondary);">
            Use AI to enhance your images automatically.
          </div>
          `}
        ` : `
          <div style="padding: 16px; background: var(--brighten-bg-tertiary); border-radius: 6px; text-align: center;">
            <div style="font-size: 14px; color: var(--brighten-text-secondary); margin-bottom: 8px;">
              AI features require an API endpoint
            </div>
            <div style="font-size: 12px; color: var(--brighten-text-secondary);">
              Configure <code>apiEndpoint</code> in EditorUI options to enable AI tools.
            </div>
          </div>
        `}
      </div>
    `;
  }

  private renderBrushPanel(): string {
    return `
      <div class="brighten-panel-header">
        <span>Brush</span>
      </div>
      <div class="brighten-panel-content">
        <div class="brighten-slider-group">
          <div class="brighten-slider-label">
            <span>Color</span>
          </div>
          <input type="color" data-brush="color" value="${this.brushOptions.color}" 
                 style="width: 100%; height: 36px; border: none; border-radius: 4px; cursor: pointer; background: transparent;">
        </div>
        <div class="brighten-slider-group">
          <div class="brighten-slider-label">
            <span>Size</span>
            <span data-value="brushSize">${this.brushOptions.size}</span>
          </div>
          <input type="range" class="brighten-slider" data-brush="size" min="1" max="100" value="${this.brushOptions.size}">
        </div>
        <div class="brighten-slider-group">
          <div class="brighten-slider-label">
            <span>Opacity</span>
            <span data-value="brushOpacity">${this.brushOptions.opacity}%</span>
          </div>
          <input type="range" class="brighten-slider" data-brush="opacity" min="1" max="100" value="${this.brushOptions.opacity}">
        </div>
        <div style="margin-top: 16px; padding: 12px; background: var(--brighten-bg-tertiary); border-radius: 6px;">
          <div style="font-size: 12px; color: var(--brighten-text-secondary); margin-bottom: 8px;">Preview</div>
          <div style="display: flex; align-items: center; justify-content: center; height: 60px;">
            <div style="width: ${Math.min(this.brushOptions.size, 60)}px; height: ${Math.min(this.brushOptions.size, 60)}px; 
                        background: ${this.brushOptions.color}; border-radius: 50%; 
                        opacity: ${this.brushOptions.opacity / 100};"></div>
          </div>
        </div>
      </div>
    `;
  }

  private showPanel(panel: PanelType): void {
    this.currentPanel = panel;
    const panelEl = this.root.querySelector('.brighten-panel');
    if (!panelEl) return;

    switch (panel) {
      case 'filters':
        panelEl.innerHTML = this.renderFiltersPanel();
        break;
      case 'adjust':
        panelEl.innerHTML = this.renderAdjustPanel();
        break;
      case 'layers':
        panelEl.innerHTML = this.renderLayersPanel();
        break;
      case 'text':
        panelEl.innerHTML = this.renderTextPanel();
        break;
      case 'shapes':
        panelEl.innerHTML = this.renderShapesPanel();
        break;
      case 'crop':
        panelEl.innerHTML = this.renderCropPanel();
        break;
      case 'transform':
        panelEl.innerHTML = this.renderTransformPanel();
        break;
      case 'brush':
        panelEl.innerHTML = this.renderBrushPanel();
        break;
      case 'ai':
        panelEl.innerHTML = this.renderAIPanel();
        break;
      default:
        panelEl.innerHTML = '';
    }
  }

  private setupEventListeners(): void {
    this.root.addEventListener('click', (e) => this.handleClick(e));
    this.root.addEventListener('input', (e) => this.handleInput(e));

    this.keyboardHandler = (e: KeyboardEvent) => this.handleKeyboard(e);
    document.addEventListener('keydown', this.keyboardHandler);

    this.editor.on('zoom:change', ({ zoom }) => {
      const zoomValue = this.root.querySelector('.brighten-zoom-value');
      if (zoomValue) {
        zoomValue.textContent = `${Math.round(zoom * 100)}%`;
      }
    });

    this.editor.on('history:change', ({ canUndo, canRedo }) => {
      const undoBtn = this.root.querySelector('[data-action="undo"]') as HTMLButtonElement;
      const redoBtn = this.root.querySelector('[data-action="redo"]') as HTMLButtonElement;
      if (undoBtn) undoBtn.disabled = !canUndo;
      if (redoBtn) redoBtn.disabled = !canRedo;
    });

    this.editor.on('layer:add', () => this.refreshLayersPanel());
    this.editor.on('layer:remove', () => this.refreshLayersPanel());
    this.editor.on('layer:update', () => this.refreshLayersPanel());
    this.editor.on('layer:select', () => this.refreshLayersPanel());

    this.setupCanvasPanning();
  }

  private setupCanvasPanning(): void {
    const canvasContainer = this.root.querySelector('.brighten-canvas-container') as HTMLElement;
    if (!canvasContainer) return;

    canvasContainer.addEventListener('mousedown', (e: MouseEvent) => {
      const isMiddleButton = e.button === 1;
      const isLeftButton = e.button === 0;
      
      if (!isMiddleButton && !isLeftButton) return;
      const toolsWithOwnInteraction = ['brush', 'crop'];
      if (isLeftButton && toolsWithOwnInteraction.includes(this.currentTool)) return;
      if (isLeftButton && this.inpaintMode) return;
      if (this.cropRect) return;

      const currentPan = this.editor.getPan();
      this.panState = {
        active: true,
        startX: e.clientX,
        startY: e.clientY,
        startPanX: currentPan.x,
        startPanY: currentPan.y,
      };

      canvasContainer.style.cursor = 'grabbing';

      this.panMouseMoveHandler = (moveEvent: MouseEvent) => {
        if (!this.panState.active) return;
        
        const dx = moveEvent.clientX - this.panState.startX;
        const dy = moveEvent.clientY - this.panState.startY;
        
        this.editor.setPan({
          x: this.panState.startPanX + dx,
          y: this.panState.startPanY + dy,
        });
      };

      this.panMouseUpHandler = () => {
        this.panState.active = false;
        canvasContainer.style.cursor = 'grab';
        
        if (this.panMouseMoveHandler) {
          document.removeEventListener('mousemove', this.panMouseMoveHandler);
        }
        if (this.panMouseUpHandler) {
          document.removeEventListener('mouseup', this.panMouseUpHandler);
        }
      };

      document.addEventListener('mousemove', this.panMouseMoveHandler);
      document.addEventListener('mouseup', this.panMouseUpHandler);
    });

    canvasContainer.style.cursor = 'grab';

    canvasContainer.addEventListener('wheel', (e: WheelEvent) => {
      e.preventDefault();
      const zoomFactor = e.deltaY > 0 ? 0.9 : 1.1;
      const currentZoom = this.editor.getZoom();
      this.editor.setZoom(currentZoom * zoomFactor);
    }, { passive: false });

    canvasContainer.addEventListener('dblclick', (e: MouseEvent) => {
      const point = this.getCanvasPoint(e, canvasContainer);
      const textLayer = this.findTextLayerAtPoint(point);
      if (textLayer) {
        this.startTextEdit(textLayer);
      }
    });
  }

  private findTextLayerAtPoint(point: Point): import('../core/types').TextLayer | null {
    const layers = this.editor.getLayerManager().getLayers();
    const ctx = document.createElement('canvas').getContext('2d')!;
    
    for (let i = layers.length - 1; i >= 0; i--) {
      const layer = layers[i];
      if (layer.type !== 'text' || !layer.visible) continue;
      
      const textLayer = layer as import('../core/types').TextLayer;
      ctx.font = `${textLayer.fontStyle} ${textLayer.fontWeight} ${textLayer.fontSize}px ${textLayer.fontFamily}`;
      const metrics = ctx.measureText(textLayer.text);
      const textWidth = metrics.width;
      const textHeight = textLayer.fontSize * textLayer.lineHeight;
      
      const transform = textLayer.transform;
      const x = transform.x;
      const y = transform.y;
      
      if (point.x >= x && point.x <= x + textWidth &&
          point.y >= y - textHeight && point.y <= y) {
        return textLayer;
      }
    }
    return null;
  }

  private startTextEdit(layer: import('../core/types').TextLayer): void {
    const canvasContainer = this.root.querySelector('.brighten-canvas-container') as HTMLElement;
    if (!canvasContainer) return;

    const zoom = this.editor.getZoom();
    const pan = this.editor.getPan();
    
    const input = document.createElement('input');
    input.type = 'text';
    input.value = layer.text;
    input.style.cssText = `
      position: absolute;
      left: ${layer.transform.x * zoom + pan.x}px;
      top: ${(layer.transform.y - layer.fontSize) * zoom + pan.y}px;
      font-family: ${layer.fontFamily};
      font-size: ${layer.fontSize * zoom}px;
      font-weight: ${layer.fontWeight};
      font-style: ${layer.fontStyle};
      color: ${layer.color};
      background: transparent;
      border: 1px dashed rgba(255,255,255,0.5);
      outline: none;
      padding: 2px 4px;
      min-width: 100px;
      z-index: 1000;
    `;

    const finishEdit = () => {
      const newText = input.value.trim() || layer.text;
      if (newText !== layer.text) {
        this.editor.getLayerManager().updateLayer(layer.id, { text: newText });
        this.editor.saveToHistory('Edit text');
      }
      input.remove();
    };

    input.addEventListener('blur', finishEdit);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        finishEdit();
      } else if (e.key === 'Escape') {
        input.value = layer.text;
        input.blur();
      }
    });

    canvasContainer.appendChild(input);
    input.focus();
    input.select();
  }

  private initializeBrushTool(canvasContainer: HTMLElement): void {
    const canvas = canvasContainer.querySelector('canvas');
    if (!canvas) return;

    this.brushTool = new BrushTool();
    this.brushTool.attach({ editor: this.editor, canvas });
    this.brushTool.setOptions({
      color: this.brushOptions.color,
      size: this.brushOptions.size,
      opacity: this.brushOptions.opacity / 100,
    });

    canvasContainer.addEventListener('mousedown', (e: MouseEvent) => {
      if (e.button !== 0) return;
      if (this.currentTool !== 'brush') return;
      if (!this.brushTool) return;

      const point = this.getCanvasPoint(e, canvasContainer);
      this.brushTool.activate();
      this.brushTool.onPointerDown(point, e as unknown as PointerEvent);
      canvasContainer.style.cursor = 'crosshair';

      this.brushMouseMoveHandler = (moveEvent: MouseEvent) => {
        if (!this.brushTool) return;
        const movePoint = this.getCanvasPoint(moveEvent, canvasContainer);
        this.brushTool.onPointerMove(movePoint, moveEvent as unknown as PointerEvent);
      };

      this.brushMouseUpHandler = () => {
        if (!this.brushTool) return;
        this.brushTool.onPointerUp({ x: 0, y: 0 }, new PointerEvent('pointerup'));
        this.brushTool.deactivate();
        canvasContainer.style.cursor = 'crosshair';

        if (this.brushMouseMoveHandler) {
          document.removeEventListener('mousemove', this.brushMouseMoveHandler);
        }
        if (this.brushMouseUpHandler) {
          document.removeEventListener('mouseup', this.brushMouseUpHandler);
        }
      };

      document.addEventListener('mousemove', this.brushMouseMoveHandler);
      document.addEventListener('mouseup', this.brushMouseUpHandler);
    });
  }

  private getCanvasPoint(e: MouseEvent, canvasContainer: HTMLElement): Point {
    const rect = canvasContainer.getBoundingClientRect();
    const zoom = this.editor.getZoom();
    const pan = this.editor.getPan();
    
    const containerX = e.clientX - rect.left;
    const containerY = e.clientY - rect.top;
    
    const x = (containerX - pan.x) / zoom;
    const y = (containerY - pan.y) / zoom;
    
    return { x, y };
  }

  private handleKeyboard(e: KeyboardEvent): void {
    const target = e.target as HTMLElement;
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') return;

    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const cmdOrCtrl = isMac ? e.metaKey : e.ctrlKey;

    if (cmdOrCtrl && e.key === 'z' && !e.shiftKey) {
      e.preventDefault();
      this.editor.undo();
    } else if (cmdOrCtrl && e.key === 'z' && e.shiftKey) {
      e.preventDefault();
      this.editor.redo();
    } else if (cmdOrCtrl && e.key === 'y') {
      e.preventDefault();
      this.editor.redo();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      if (this.cropRect) {
        this.cancelCrop();
      } else if (this.currentPanel) {
        this.showPanel(null);
        this.setTool('select');
      }
    } else if (e.key === 'Delete' || e.key === 'Backspace') {
      const activeLayer = this.editor.getLayerManager().getActiveLayer();
      if (activeLayer && activeLayer.type !== 'image') {
        e.preventDefault();
        this.editor.getLayerManager().removeLayer(activeLayer.id);
        this.editor.saveToHistory('Delete layer');
      }
    } else if (e.key === 'v' || e.key === 'V') {
      this.setTool('select');
      this.showPanel(null);
    } else if (e.key === 'c' || e.key === 'C') {
      this.setTool('crop');
      this.showPanel('crop');
      this.startCrop();
    } else if (e.key === 'b' || e.key === 'B') {
      this.setTool('brush');
      this.showPanel('brush');
    } else if (e.key === 't' || e.key === 'T') {
      this.setTool('text');
      this.showPanel('text');
    } else if (cmdOrCtrl && e.key === '=') {
      e.preventDefault();
      this.editor.zoomIn();
    } else if (cmdOrCtrl && e.key === '-') {
      e.preventDefault();
      this.editor.zoomOut();
    } else if (cmdOrCtrl && e.key === '0') {
      e.preventDefault();
      this.editor.fitToView();
    }
  }

  private handleClick(e: Event): void {
    const target = e.target as HTMLElement;
    const button = target.closest('button');
    if (!button) return;

    const action = button.dataset.action;
    const tool = button.dataset.tool;
    const panel = button.dataset.panel as PanelType;
    const preset = button.dataset.preset;
    const layerId = button.dataset.layer;
    const shape = button.dataset.shape;
    const ratio = button.dataset.ratio;

    if (tool) {
      this.setTool(tool as ToolType);
      if (panel) {
        this.showPanel(panel);
        if (panel === 'crop') {
          this.startCrop();
        }
      }
    }

    if (action) {
      switch (action) {
        case 'undo':
          this.editor.undo();
          break;
        case 'redo':
          this.editor.redo();
          break;
        case 'zoom-in':
          this.editor.zoomIn();
          break;
        case 'zoom-out':
          this.editor.zoomOut();
          break;
        case 'open':
          this.openFilePicker();
          break;
        case 'export':
          this.exportImage();
          break;
        case 'close':
          this.config.onClose?.();
          break;
        case 'reset-adjustments':
          this.resetAdjustmentsAndApply();
          break;
        case 'add-text':
          this.addText();
          break;
        case 'add-layer':
          this.addLayer();
          break;
        case 'add-shape':
          if (shape) this.addShape(shape as 'rectangle' | 'ellipse' | 'line');
          break;
        case 'toggle-visibility':
          if (layerId) this.toggleLayerVisibility(layerId);
          break;
        case 'apply-crop':
          this.applyCrop();
          break;
        case 'cancel-crop':
          this.cancelCrop();
          break;
        case 'set-crop-ratio':
          if (ratio) this.setCropRatio(ratio === 'free' ? null : parseFloat(ratio));
          break;
        case 'rotate-cw':
          this.rotateImage(90);
          break;
        case 'rotate-ccw':
          this.rotateImage(-90);
          break;
        case 'flip-h':
          this.flipImage('horizontal');
          break;
        case 'flip-v':
          this.flipImage('vertical');
          break;
        case 'remove-background':
          this.removeBackground();
          break;
        case 'unblur':
          this.unblur();
          break;
        case 'colorize':
          this.colorize();
          break;
        case 'restore':
          this.restore();
          break;
        case 'start-inpaint':
          this.startInpaintMode();
          break;
        case 'cancel-inpaint':
          this.cancelInpaintMode();
          break;

        case 'apply-inpaint':
          this.applyInpaint();
          break;
      }
    }

    if (preset) {
      this.applyPreset(preset);
    }

    if (layerId && !action) {
      this.editor.getLayerManager().selectLayer(layerId);
    }
  }

  private handleInput(e: Event): void {
    const target = e.target as HTMLInputElement;
    const adjust = target.dataset.adjust;
    const brush = target.dataset.brush;

    if (adjust) {
      const value = parseInt(target.value, 10);
      this.adjustments[adjust] = value;

      const valueEl = this.root.querySelector(`[data-value="${adjust}"]`);
      if (valueEl) valueEl.textContent = String(value);

      this.applyAdjustments();
      this.saveAdjustmentToHistoryDebounced();
    }

    if (brush) {
      if (brush === 'color') {
        this.brushOptions.color = target.value;
      } else if (brush === 'size') {
        this.brushOptions.size = parseInt(target.value, 10);
        const valueEl = this.root.querySelector('[data-value="brushSize"]');
        if (valueEl) valueEl.textContent = String(this.brushOptions.size);
      } else if (brush === 'opacity') {
        this.brushOptions.opacity = parseInt(target.value, 10);
        const valueEl = this.root.querySelector('[data-value="brushOpacity"]');
        if (valueEl) valueEl.textContent = `${this.brushOptions.opacity}%`;
      }
      this.updateBrushToolOptions();
      this.refreshBrushPreview();
    }

    const inpaint = target.dataset.inpaint;
    if (inpaint === 'brushSize') {
      this.inpaintBrushSize = parseInt(target.value, 10);
      const valueEl = this.root.querySelector('[data-value="inpaintBrush"]');
      if (valueEl) valueEl.textContent = String(this.inpaintBrushSize);
    }
  }

  private refreshBrushPreview(): void {
    this.showPanel('brush');
  }

  private updateBrushToolOptions(): void {
    if (!this.brushTool) return;
    this.brushTool.setOptions({
      color: this.brushOptions.color,
      size: this.brushOptions.size,
      opacity: this.brushOptions.opacity / 100,
    });
  }

  private setTool(tool: ToolType): void {
    if (this.currentTool === 'crop' && tool !== 'crop') {
      this.removeCropOverlay();
      this.cropRect = null;
    }
    
    this.currentTool = tool;
    this.editor.setTool(tool);

    this.root.querySelectorAll('.brighten-tool-btn').forEach((btn) => {
      btn.classList.toggle('active', btn.getAttribute('data-tool') === tool);
    });

    const canvasContainer = this.root.querySelector('.brighten-canvas-container') as HTMLElement;
    if (canvasContainer) {
      canvasContainer.style.cursor = tool === 'brush' ? 'crosshair' : 'grab';
    }
  }

  private resetAdjustments(): void {
    this.adjustments = {
      brightness: 0,
      contrast: 0,
      saturation: 0,
      exposure: 0,
      temperature: 0,
      tint: 0,
      vibrance: 0,
      sharpen: 0,
      vignette: 0,
    };
  }

  private applyAdjustments(): void {
    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    if (!this.originalImageData) {
      const canvas = document.createElement('canvas');
      const source = imageLayer.source;
      canvas.width = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
      canvas.height = source instanceof HTMLImageElement ? source.naturalHeight : source.height;
      const ctx = canvas.getContext('2d')!;
      ctx.drawImage(source, 0, 0);
      this.originalImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    }

    const filters = Object.entries(this.adjustments)
      .filter(([_, value]) => value !== 0)
      .map(([type, value]) => ({
        type: type as any,
        value: value / 100,
        enabled: true,
      }));

    const sourceCopy = new ImageData(
      new Uint8ClampedArray(this.originalImageData.data),
      this.originalImageData.width,
      this.originalImageData.height
    );

    let resultData: ImageData;
    if (filters.length === 0) {
      resultData = sourceCopy;
    } else {
      resultData = this.filterEngine.applyFilters(sourceCopy, filters);
    }

    const resultCanvas = document.createElement('canvas');
    resultCanvas.width = resultData.width;
    resultCanvas.height = resultData.height;
    const resultCtx = resultCanvas.getContext('2d')!;
    resultCtx.putImageData(resultData, 0, 0);

    this.editor.getLayerManager().updateLayer(imageLayer.id, { source: resultCanvas });
  }

  private saveAdjustmentToHistoryDebounced(): void {
    if (this.adjustmentHistoryTimeout) {
      clearTimeout(this.adjustmentHistoryTimeout);
    }
    this.adjustmentHistoryTimeout = setTimeout(() => {
      this.editor.saveToHistory('Adjust image');
      this.adjustmentHistoryTimeout = null;
    }, 500);
  }

  private resetAdjustmentsAndApply(): void {
    this.resetAdjustments();
    this.applyAdjustments();
    this.editor.saveToHistory('Reset adjustments');
    this.showPanel('adjust');
  }

  private applyPreset(presetId: string): void {
    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    const canvas = document.createElement('canvas');
    const source = imageLayer.source;
    canvas.width = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    canvas.height = source instanceof HTMLImageElement ? source.naturalHeight : source.height;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(source, 0, 0);
    const currentImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

    if (presetId === 'none') {
      this.currentPreset = null;
      this.showPanel('filters');
      return;
    }

    const previousPreset = this.currentPreset;
    this.currentPreset = presetId;
    
    const resultData = this.filterEngine.applyPreset(currentImageData, presetId);

    const resultCanvas = document.createElement('canvas');
    resultCanvas.width = resultData.width;
    resultCanvas.height = resultData.height;
    const resultCtx = resultCanvas.getContext('2d')!;
    resultCtx.putImageData(resultData, 0, 0);

    this.editor.getLayerManager().updateLayer(imageLayer.id, { source: resultCanvas });
    this.editor.saveToHistory(`Apply ${presetId} filter`);
    
    this.originalImageData = null;
    this.showPanel('filters');
  }

  private refreshLayersPanel(): void {
    if (this.currentPanel === 'layers') {
      this.showPanel('layers');
    }
  }

  private startCrop(): void {
    const size = this.editor.getCanvasSize();
    const padding = 0.1;
    this.cropRect = {
      x: size.width * padding,
      y: size.height * padding,
      width: size.width * (1 - padding * 2),
      height: size.height * (1 - padding * 2),
    };
    this.renderCropOverlay();
  }

  private renderCropOverlay(): void {
    this.removeCropOverlay();

    if (!this.cropRect) return;

    const canvasContainer = this.root.querySelector('.brighten-canvas-container') as HTMLElement;
    if (!canvasContainer) return;

    const overlay = document.createElement('div');
    overlay.className = 'brighten-crop-overlay-container';
    overlay.style.cssText = 'position: absolute; inset: 0; pointer-events: none;';

    const canvasSize = this.editor.getCanvasSize();
    const zoom = this.editor.getZoom();
    const pan = this.editor.getPan();

    const toScreen = (x: number, y: number) => ({
      x: x * zoom + pan.x,
      y: y * zoom + pan.y,
    });

    const topLeft = toScreen(this.cropRect.x, this.cropRect.y);
    const bottomRight = toScreen(
      this.cropRect.x + this.cropRect.width,
      this.cropRect.y + this.cropRect.height
    );

    const cropWidth = bottomRight.x - topLeft.x;
    const cropHeight = bottomRight.y - topLeft.y;

    overlay.innerHTML = `
      <svg width="100%" height="100%" style="position: absolute; inset: 0;">
        <defs>
          <mask id="crop-mask">
            <rect width="100%" height="100%" fill="white"/>
            <rect x="${topLeft.x}" y="${topLeft.y}" width="${cropWidth}" height="${cropHeight}" fill="black"/>
          </mask>
        </defs>
        <rect width="100%" height="100%" fill="rgba(0,0,0,0.5)" mask="url(#crop-mask)"/>
        <rect x="${topLeft.x}" y="${topLeft.y}" width="${cropWidth}" height="${cropHeight}" 
              fill="none" stroke="white" stroke-width="2" stroke-dasharray="5,5"/>
        <rect x="${topLeft.x}" y="${topLeft.y}" width="${cropWidth}" height="${cropHeight}" 
              fill="none" stroke="var(--brighten-primary)" stroke-width="2"/>
      </svg>
      <div class="crop-handles" style="position: absolute; inset: 0; pointer-events: auto;">
        ${this.renderCropHandles(topLeft.x, topLeft.y, cropWidth, cropHeight)}
      </div>
    `;

    canvasContainer.appendChild(overlay);
    this.cropOverlay = overlay;
    this.setupCropHandlers(overlay);
  }

  private renderCropHandles(x: number, y: number, w: number, h: number): string {
    const handleSize = 10;
    const handles = [
      { pos: 'nw', x: x - handleSize/2, y: y - handleSize/2 },
      { pos: 'n', x: x + w/2 - handleSize/2, y: y - handleSize/2 },
      { pos: 'ne', x: x + w - handleSize/2, y: y - handleSize/2 },
      { pos: 'e', x: x + w - handleSize/2, y: y + h/2 - handleSize/2 },
      { pos: 'se', x: x + w - handleSize/2, y: y + h - handleSize/2 },
      { pos: 's', x: x + w/2 - handleSize/2, y: y + h - handleSize/2 },
      { pos: 'sw', x: x - handleSize/2, y: y + h - handleSize/2 },
      { pos: 'w', x: x - handleSize/2, y: y + h/2 - handleSize/2 },
    ];

    const cursors: Record<string, string> = {
      nw: 'nwse-resize', n: 'ns-resize', ne: 'nesw-resize', e: 'ew-resize',
      se: 'nwse-resize', s: 'ns-resize', sw: 'nesw-resize', w: 'ew-resize',
    };

    return handles.map(h => `
      <div data-crop-handle="${h.pos}" style="
        position: absolute;
        left: ${h.x}px;
        top: ${h.y}px;
        width: ${handleSize}px;
        height: ${handleSize}px;
        background: white;
        border: 2px solid var(--brighten-primary);
        cursor: ${cursors[h.pos]};
      "></div>
    `).join('') + `
      <div data-crop-handle="move" style="
        position: absolute;
        left: ${x}px;
        top: ${y}px;
        width: ${w}px;
        height: ${h}px;
        cursor: move;
      "></div>
    `;
  }

  private setupCropHandlers(overlay: HTMLElement): void {
    if (!this.cropMouseMoveHandler) {
      this.cropMouseMoveHandler = (e: MouseEvent) => {
        const state = this.cropDragState;
        if (!state.active || !state.startRect || !this.cropRect) return;

        const zoom = this.editor.getZoom();
        const dx = (e.clientX - state.startX) / zoom;
        const dy = (e.clientY - state.startY) / zoom;
        const size = this.editor.getCanvasSize();
        const minSize = 20;

        if (state.handle === 'move') {
          this.cropRect.x = Math.max(0, Math.min(state.startRect.x + dx, size.width - this.cropRect.width));
          this.cropRect.y = Math.max(0, Math.min(state.startRect.y + dy, size.height - this.cropRect.height));
        } else {
          if (state.handle?.includes('w')) {
            const newX = state.startRect.x + dx;
            const newWidth = state.startRect.width - dx;
            if (newWidth >= minSize && newX >= 0) {
              this.cropRect.x = newX;
              this.cropRect.width = newWidth;
            }
          }
          if (state.handle?.includes('e')) {
            const newWidth = state.startRect.width + dx;
            if (newWidth >= minSize && state.startRect.x + newWidth <= size.width) {
              this.cropRect.width = newWidth;
            }
          }
          if (state.handle?.includes('n')) {
            const newY = state.startRect.y + dy;
            const newHeight = state.startRect.height - dy;
            if (newHeight >= minSize && newY >= 0) {
              this.cropRect.y = newY;
              this.cropRect.height = newHeight;
            }
          }
          if (state.handle?.includes('s')) {
            const newHeight = state.startRect.height + dy;
            if (newHeight >= minSize && state.startRect.y + newHeight <= size.height) {
              this.cropRect.height = newHeight;
            }
          }
        }

        this.updateCropOverlayPosition();
      };
      document.addEventListener('mousemove', this.cropMouseMoveHandler);
    }

    if (!this.cropMouseUpHandler) {
      this.cropMouseUpHandler = () => {
        this.cropDragState.active = false;
        this.cropDragState.handle = null;
        this.cropDragState.startRect = null;
      };
      document.addEventListener('mouseup', this.cropMouseUpHandler);
    }

    overlay.addEventListener('mousedown', (e: MouseEvent) => {
      const target = e.target as HTMLElement;
      const handle = target.dataset.cropHandle;
      if (!handle || !this.cropRect) return;

      this.cropDragState.active = true;
      this.cropDragState.handle = handle;
      this.cropDragState.startX = e.clientX;
      this.cropDragState.startY = e.clientY;
      this.cropDragState.startRect = { ...this.cropRect };
      e.preventDefault();
    });
  }

  private updateCropOverlayPosition(): void {
    if (!this.cropOverlay || !this.cropRect) return;

    const zoom = this.editor.getZoom();
    const pan = this.editor.getPan();

    const toScreen = (x: number, y: number) => ({
      x: x * zoom + pan.x,
      y: y * zoom + pan.y,
    });

    const topLeft = toScreen(this.cropRect.x, this.cropRect.y);
    const bottomRight = toScreen(
      this.cropRect.x + this.cropRect.width,
      this.cropRect.y + this.cropRect.height
    );

    const cropWidth = bottomRight.x - topLeft.x;
    const cropHeight = bottomRight.y - topLeft.y;
    const handleSize = 10;

    const svg = this.cropOverlay.querySelector('svg');
    if (svg) {
      const rects = svg.querySelectorAll('rect');
      rects.forEach((rect, i) => {
        if (i === 0) return;
        rect.setAttribute('x', String(topLeft.x));
        rect.setAttribute('y', String(topLeft.y));
        rect.setAttribute('width', String(cropWidth));
        rect.setAttribute('height', String(cropHeight));
      });
    }

    const handles = this.cropOverlay.querySelectorAll('[data-crop-handle]') as NodeListOf<HTMLElement>;
    const handlePositions: Record<string, { x: number; y: number }> = {
      nw: { x: topLeft.x - handleSize/2, y: topLeft.y - handleSize/2 },
      n: { x: topLeft.x + cropWidth/2 - handleSize/2, y: topLeft.y - handleSize/2 },
      ne: { x: topLeft.x + cropWidth - handleSize/2, y: topLeft.y - handleSize/2 },
      e: { x: topLeft.x + cropWidth - handleSize/2, y: topLeft.y + cropHeight/2 - handleSize/2 },
      se: { x: topLeft.x + cropWidth - handleSize/2, y: topLeft.y + cropHeight - handleSize/2 },
      s: { x: topLeft.x + cropWidth/2 - handleSize/2, y: topLeft.y + cropHeight - handleSize/2 },
      sw: { x: topLeft.x - handleSize/2, y: topLeft.y + cropHeight - handleSize/2 },
      w: { x: topLeft.x - handleSize/2, y: topLeft.y + cropHeight/2 - handleSize/2 },
      move: { x: topLeft.x, y: topLeft.y },
    };

    handles.forEach(handle => {
      const pos = handle.dataset.cropHandle;
      if (pos && handlePositions[pos]) {
        handle.style.left = `${handlePositions[pos].x}px`;
        handle.style.top = `${handlePositions[pos].y}px`;
        if (pos === 'move') {
          handle.style.width = `${cropWidth}px`;
          handle.style.height = `${cropHeight}px`;
        }
      }
    });
  }

  private removeCropOverlay(): void {
    if (this.cropMouseMoveHandler) {
      document.removeEventListener('mousemove', this.cropMouseMoveHandler);
      this.cropMouseMoveHandler = null;
    }
    if (this.cropMouseUpHandler) {
      document.removeEventListener('mouseup', this.cropMouseUpHandler);
      this.cropMouseUpHandler = null;
    }
    this.cropDragState = { active: false, handle: null, startX: 0, startY: 0, startRect: null };
    if (this.cropOverlay) {
      this.cropOverlay.remove();
      this.cropOverlay = null;
    }
  }

  private setCropRatio(ratio: number | null): void {
    if (!this.cropRect) return;

    if (ratio) {
      const currentArea = this.cropRect.width * this.cropRect.height;
      const newWidth = Math.sqrt(currentArea * ratio);
      const newHeight = newWidth / ratio;
      
      const centerX = this.cropRect.x + this.cropRect.width / 2;
      const centerY = this.cropRect.y + this.cropRect.height / 2;
      
      this.cropRect.width = newWidth;
      this.cropRect.height = newHeight;
      this.cropRect.x = centerX - newWidth / 2;
      this.cropRect.y = centerY - newHeight / 2;
    }

    this.renderCropOverlay();
  }

  private applyCrop(): void {
    if (!this.cropRect) return;

    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    const source = imageLayer.source;
    const sourceWidth = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    const sourceHeight = source instanceof HTMLImageElement ? source.naturalHeight : source.height;

    const cropCanvas = document.createElement('canvas');
    cropCanvas.width = Math.round(this.cropRect.width);
    cropCanvas.height = Math.round(this.cropRect.height);
    const ctx = cropCanvas.getContext('2d')!;

    ctx.drawImage(
      source,
      this.cropRect.x, this.cropRect.y, this.cropRect.width, this.cropRect.height,
      0, 0, this.cropRect.width, this.cropRect.height
    );

    this.editor.getLayerManager().updateLayer(imageLayer.id, { source: cropCanvas });
    this.editor.getCanvasManager().setCanvasSize({ 
      width: Math.round(this.cropRect.width), 
      height: Math.round(this.cropRect.height) 
    });
    this.editor.saveToHistory('Crop');

    this.removeCropOverlay();
    this.cropRect = null;
    this.originalImageData = null;
    this.showPanel(null);
    this.setTool('select');
  }

  private cancelCrop(): void {
    this.removeCropOverlay();
    this.cropRect = null;
    this.showPanel(null);
    this.setTool('select');
  }

  private rotateImage(degrees: number): void {
    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    const source = imageLayer.source;
    const sourceWidth = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    const sourceHeight = source instanceof HTMLImageElement ? source.naturalHeight : source.height;

    const rotatedCanvas = document.createElement('canvas');
    const isRightAngle = Math.abs(degrees) === 90 || Math.abs(degrees) === 270;
    
    rotatedCanvas.width = isRightAngle ? sourceHeight : sourceWidth;
    rotatedCanvas.height = isRightAngle ? sourceWidth : sourceHeight;
    
    const ctx = rotatedCanvas.getContext('2d')!;
    ctx.translate(rotatedCanvas.width / 2, rotatedCanvas.height / 2);
    ctx.rotate((degrees * Math.PI) / 180);
    ctx.drawImage(source, -sourceWidth / 2, -sourceHeight / 2);

    this.editor.getLayerManager().updateLayer(imageLayer.id, { source: rotatedCanvas });
    this.editor.getCanvasManager().setCanvasSize({ 
      width: rotatedCanvas.width, 
      height: rotatedCanvas.height 
    });
    this.editor.saveToHistory(`Rotate ${degrees}°`);
    this.originalImageData = null;
  }

  private flipImage(direction: 'horizontal' | 'vertical'): void {
    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    const source = imageLayer.source;
    const sourceWidth = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    const sourceHeight = source instanceof HTMLImageElement ? source.naturalHeight : source.height;

    const flippedCanvas = document.createElement('canvas');
    flippedCanvas.width = sourceWidth;
    flippedCanvas.height = sourceHeight;
    
    const ctx = flippedCanvas.getContext('2d')!;
    
    if (direction === 'horizontal') {
      ctx.translate(sourceWidth, 0);
      ctx.scale(-1, 1);
    } else {
      ctx.translate(0, sourceHeight);
      ctx.scale(1, -1);
    }
    
    ctx.drawImage(source, 0, 0);

    this.editor.getLayerManager().updateLayer(imageLayer.id, { source: flippedCanvas });
    this.editor.saveToHistory(`Flip ${direction}`);
    this.originalImageData = null;
  }

  private aiGlowElement: HTMLElement | null = null;

  private setAiProcessing(active: boolean): void {
    const canvasContainer = this.root.querySelector('.brighten-canvas-container');
    if (!canvasContainer) return;

    if (active && !this.aiGlowElement) {
      this.aiGlowElement = document.createElement('div');
      this.aiGlowElement.className = 'brighten-ai-border';
      canvasContainer.appendChild(this.aiGlowElement);
    } else if (!active && this.aiGlowElement) {
      this.aiGlowElement.remove();
      this.aiGlowElement = null;
    }
  }

  private async removeBackground(): Promise<void> {
    if (!this.config.apiEndpoint) {
      console.error('API endpoint not configured');
      return;
    }

    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    const source = imageLayer.source;
    const canvas = document.createElement('canvas');
    canvas.width = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    canvas.height = source instanceof HTMLImageElement ? source.naturalHeight : source.height;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(source, 0, 0);
    const base64Image = canvas.toDataURL('image/png');

    const btn = this.root.querySelector('[data-action="remove-background"]') as HTMLButtonElement;
    const resetButton = () => {
      this.setAiProcessing(false);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `${icons.magic} Remove Background`;
      }
    };

    this.setAiProcessing(true);
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `${icons.magic} Processing...`;
    }

    try {
      const response = await fetch(`${this.config.apiEndpoint}/api/v1/background-remove`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: base64Image }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Failed to remove background');
      }

      const result = await response.json();
      
      const img = new Image();
      img.onload = () => {
        this.editor.getLayerManager().updateLayer(imageLayer.id, { source: img });
        this.editor.saveToHistory('Remove background');
        this.originalImageData = null;
        resetButton();
      };
      img.onerror = () => {
        throw new Error('Failed to load processed image');
      };
      img.src = result.image;
    } catch (error) {
      console.error('Background removal failed:', error);
      resetButton();
      alert(error instanceof Error ? error.message : 'Background removal failed');
    }
  }

  private async unblur(): Promise<void> {
    if (!this.config.apiEndpoint) {
      console.error('API endpoint not configured');
      return;
    }

    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    const source = imageLayer.source;
    const canvas = document.createElement('canvas');
    canvas.width = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    canvas.height = source instanceof HTMLImageElement ? source.naturalHeight : source.height;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(source, 0, 0);
    const base64Image = canvas.toDataURL('image/png');

    const btn = this.root.querySelector('[data-action="unblur"]') as HTMLButtonElement;
    const resetButton = () => {
      this.setAiProcessing(false);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `${icons.focus} Unblur / Enhance`;
      }
    };

    this.setAiProcessing(true);
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `${icons.focus} Processing...`;
    }

    try {
      const response = await fetch(`${this.config.apiEndpoint}/api/v1/unblur`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: base64Image }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Failed to unblur image');
      }

      const result = await response.json();
      const originalWidth = canvas.width;
      const originalHeight = canvas.height;
      
      const img = new Image();
      img.onload = () => {
        const resizedCanvas = document.createElement('canvas');
        resizedCanvas.width = originalWidth;
        resizedCanvas.height = originalHeight;
        const resizedCtx = resizedCanvas.getContext('2d')!;
        resizedCtx.drawImage(img, 0, 0, originalWidth, originalHeight);
        
        this.editor.getLayerManager().updateLayer(imageLayer.id, { source: resizedCanvas });
        this.editor.saveToHistory('Unblur image');
        this.originalImageData = null;
        resetButton();
      };
      img.onerror = () => {
        resetButton();
        alert('Failed to load processed image');
      };
      img.src = result.image;
    } catch (error) {
      console.error('Unblur failed:', error);
      resetButton();
      alert(error instanceof Error ? error.message : 'Unblur failed');
    }
  }

  private async colorize(): Promise<void> {
    if (!this.config.apiEndpoint) {
      console.error('API endpoint not configured');
      return;
    }

    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    const source = imageLayer.source;
    const canvas = document.createElement('canvas');
    canvas.width = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    canvas.height = source instanceof HTMLImageElement ? source.naturalHeight : source.height;
    const ctx = canvas.getContext('2d')!;
    ctx.drawImage(source, 0, 0);
    const base64Image = canvas.toDataURL('image/png');

    const btn = this.root.querySelector('[data-action="colorize"]') as HTMLButtonElement;
    const resetButton = () => {
      this.setAiProcessing(false);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `${icons.palette} Colorize`;
      }
    };

    this.setAiProcessing(true);
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `${icons.palette} Processing...`;
    }

    try {
      const response = await fetch(`${this.config.apiEndpoint}/api/v1/colorize`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: base64Image }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Failed to colorize image');
      }

      const result = await response.json();
      const originalWidth = canvas.width;
      const originalHeight = canvas.height;
      
      const img = new Image();
      img.onload = () => {
        const resizedCanvas = document.createElement('canvas');
        resizedCanvas.width = originalWidth;
        resizedCanvas.height = originalHeight;
        const resizedCtx = resizedCanvas.getContext('2d')!;
        resizedCtx.drawImage(img, 0, 0, originalWidth, originalHeight);
        
        this.editor.getLayerManager().updateLayer(imageLayer.id, { source: resizedCanvas });
        this.editor.saveToHistory('Colorize image');
        this.originalImageData = null;
        resetButton();
      };
      img.onerror = () => {
        resetButton();
        alert('Failed to load colorized image');
      };
      img.src = result.image;
    } catch (error) {
      console.error('Colorize failed:', error);
      resetButton();
      alert(error instanceof Error ? error.message : 'Colorize failed');
    }
  }

  private async restore(): Promise<void> {
    if (!this.config.apiEndpoint) return;

    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image') as ImageLayer | undefined;
    if (!imageLayer) return;

    const btn = this.root.querySelector('[data-action="restore"]') as HTMLButtonElement;
    const resetButton = () => {
      this.setAiProcessing(false);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `${icons.magic} Restore Photo`;
      }
    };

    this.setAiProcessing(true);
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `${icons.magic} Processing...`;
    }

    try {

      const source = imageLayer.source;
      const canvas = document.createElement('canvas');
      const originalWidth = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
      const originalHeight = source instanceof HTMLImageElement ? source.naturalHeight : source.height;
      canvas.width = originalWidth;
      canvas.height = originalHeight;
      const ctx = canvas.getContext('2d')!;
      ctx.drawImage(source, 0, 0);
      const dataUrl = canvas.toDataURL('image/jpeg', 0.95);

      const response = await fetch(`${this.config.apiEndpoint}/api/v1/restore`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: dataUrl }),
      });

      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.error || 'Failed to restore image');
      }

      const img = new Image();
      img.onload = () => {
        const resizedCanvas = document.createElement('canvas');
        resizedCanvas.width = originalWidth;
        resizedCanvas.height = originalHeight;
        const resizedCtx = resizedCanvas.getContext('2d')!;
        resizedCtx.drawImage(img, 0, 0, originalWidth, originalHeight);
        
        this.editor.getLayerManager().updateLayer(imageLayer.id, { source: resizedCanvas });
        this.editor.saveToHistory('Restore image');
        this.originalImageData = null;
        resetButton();
      };
      img.onerror = () => {
        resetButton();
        alert('Failed to load restored image');
      };
      img.src = result.image;
    } catch (error) {
      console.error('Restore failed:', error);
      resetButton();
      alert(error instanceof Error ? error.message : 'Restore failed');
    }
  }

  private startInpaintMode(): void {
    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') return;

    this.inpaintMode = true;
    
    const source = imageLayer.source;
    const width = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    const height = source instanceof HTMLImageElement ? source.naturalHeight : source.height;

    this.maskCanvas = document.createElement('canvas');
    this.maskCanvas.width = width;
    this.maskCanvas.height = height;
    this.maskCtx = this.maskCanvas.getContext('2d')!;
    this.maskCtx.fillStyle = 'black';
    this.maskCtx.fillRect(0, 0, width, height);

    const canvasContainer = this.root.querySelector('.brighten-canvas-container') as HTMLElement;
    if (!canvasContainer) return;

    const zoom = this.editor.getZoom();
    const pan = this.editor.getPan();
    const imageDisplayWidth = width * zoom;
    const imageDisplayHeight = height * zoom;
    
    this.maskOverlay = document.createElement('div');
    this.maskOverlay.className = 'brighten-mask-overlay';
    this.maskOverlay.style.cssText = `position: absolute; left: ${pan.x}px; top: ${pan.y}px; width: ${imageDisplayWidth}px; height: ${imageDisplayHeight}px; cursor: none; z-index: 100;`;
    
    const overlayCanvas = document.createElement('canvas');
    overlayCanvas.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;';
    
    const shimmerCanvas = document.createElement('canvas');
    shimmerCanvas.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;';
    
    const cursorCanvas = document.createElement('canvas');
    cursorCanvas.style.cssText = 'position: absolute; pointer-events: none; display: none;';
    
    this.maskOverlay.appendChild(overlayCanvas);
    this.maskOverlay.appendChild(shimmerCanvas);
    this.maskOverlay.appendChild(cursorCanvas);
    canvasContainer.appendChild(this.maskOverlay);

    this.setupInpaintDrawing(overlayCanvas, shimmerCanvas, cursorCanvas);
    this.showPanel('ai');
  }

  private setupInpaintDrawing(overlayCanvas: HTMLCanvasElement, shimmerCanvas: HTMLCanvasElement, cursorCanvas: HTMLCanvasElement): void {
    if (!this.maskOverlay || !this.maskCanvas || !this.maskCtx) return;

    interface StrokePoint {
      x: number;
      y: number;
      radius: number;
      timestamp: number;
    }
    
    const strokes: StrokePoint[] = [];
    let animationId: number | null = null;
    let isDrawing = false;
    let idleStartTime: number | null = null;
    
    const AGING_DURATION = 2000;
    const GRAY_COLOR = { r: 200, g: 200, b: 210 };
    
    const hslToRgb = (h: number, s: number, l: number) => {
      s /= 100;
      l /= 100;
      const k = (n: number) => (n + h / 30) % 12;
      const a = s * Math.min(l, 1 - l);
      const f = (n: number) => l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));
      return { r: Math.round(255 * f(0)), g: Math.round(255 * f(8)), b: Math.round(255 * f(4)) };
    };
    
    const animate = () => {
      if (!this.inpaintMode) return;
      
      const rect = this.maskOverlay!.getBoundingClientRect();
      if (overlayCanvas.width !== rect.width || overlayCanvas.height !== rect.height) {
        overlayCanvas.width = rect.width;
        overlayCanvas.height = rect.height;
        shimmerCanvas.width = rect.width;
        shimmerCanvas.height = rect.height;
      }
      
      const ctx = overlayCanvas.getContext('2d')!;
      const shimmerCtx = shimmerCanvas.getContext('2d')!;
      ctx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
      shimmerCtx.clearRect(0, 0, shimmerCanvas.width, shimmerCanvas.height);
      
      const now = Date.now();
      const scaleX = overlayCanvas.width / this.maskCanvas!.width;
      const scaleY = overlayCanvas.height / this.maskCanvas!.height;
      
      if (strokes.length > 0) {
        ctx.save();
        ctx.beginPath();
        for (const stroke of strokes) {
          ctx.moveTo(stroke.x * scaleX + stroke.radius * scaleX, stroke.y * scaleY);
          ctx.arc(stroke.x * scaleX, stroke.y * scaleY, stroke.radius * scaleX, 0, Math.PI * 2);
        }
        ctx.clip();
        
        const avgAge = strokes.reduce((sum, s) => sum + (now - s.timestamp), 0) / strokes.length;
        const ageRatio = Math.min(avgAge / AGING_DURATION, 1);
        const hue = (ageRatio * 300) % 360;
        const saturation = 90 - ageRatio * 50;
        const lightness = 60 + ageRatio * 25;
        
        let r: number, g: number, b: number;
        if (ageRatio >= 1) {
          r = GRAY_COLOR.r;
          g = GRAY_COLOR.g;
          b = GRAY_COLOR.b;
        } else {
          const rgb = hslToRgb(hue, saturation, lightness);
          const grayBlend = Math.pow(ageRatio, 2);
          r = Math.round(rgb.r * (1 - grayBlend) + GRAY_COLOR.r * grayBlend);
          g = Math.round(rgb.g * (1 - grayBlend) + GRAY_COLOR.g * grayBlend);
          b = Math.round(rgb.b * (1 - grayBlend) + GRAY_COLOR.b * grayBlend);
        }
        
        ctx.fillStyle = `rgba(${r}, ${g}, ${b}, 0.4)`;
        ctx.fillRect(0, 0, overlayCanvas.width, overlayCanvas.height);
        ctx.restore();
      }
      
      if (!isDrawing && strokes.length > 0) {
        if (idleStartTime === null) idleStartTime = now;
        const idleTime = now - idleStartTime;
        const shimmerHue = (idleTime * 0.1) % 360;
        const shimmerAlpha = 0.12 + Math.sin(idleTime * 0.005) * 0.08;
        
        const maskData = this.maskCtx!.getImageData(0, 0, this.maskCanvas!.width, this.maskCanvas!.height);
        const shimmerData = shimmerCtx.createImageData(shimmerCanvas.width, shimmerCanvas.height);
        
        for (let y = 0; y < shimmerCanvas.height; y++) {
          for (let x = 0; x < shimmerCanvas.width; x++) {
            const maskX = Math.floor(x / scaleX);
            const maskY = Math.floor(y / scaleY);
            const maskIdx = (maskY * this.maskCanvas!.width + maskX) * 4;
            
            if (maskData.data[maskIdx] > 128) {
              const localHue = (shimmerHue + x * 0.5 + y * 0.3) % 360;
              const rgb = hslToRgb(localHue, 70, 85);
              const idx = (y * shimmerCanvas.width + x) * 4;
              shimmerData.data[idx] = rgb.r;
              shimmerData.data[idx + 1] = rgb.g;
              shimmerData.data[idx + 2] = rgb.b;
              shimmerData.data[idx + 3] = Math.round(shimmerAlpha * 255);
            }
          }
        }
        shimmerCtx.putImageData(shimmerData, 0, 0);
      } else {
        idleStartTime = null;
      }
      
      animationId = requestAnimationFrame(animate);
    };

    animate();

    const getCanvasCoords = (e: MouseEvent) => {
      const rect = this.maskOverlay!.getBoundingClientRect();
      const scaleX = this.maskCanvas!.width / rect.width;
      const scaleY = this.maskCanvas!.height / rect.height;
      const x = (e.clientX - rect.left) * scaleX;
      const y = (e.clientY - rect.top) * scaleY;
      return { x, y, scaleX, scaleY };
    };

    const addStrokePoints = (fromX: number, fromY: number, toX: number, toY: number, scale: number) => {
      const dist = Math.sqrt((toX - fromX) ** 2 + (toY - fromY) ** 2);
      const steps = Math.max(1, Math.floor(dist / 3));
      const now = Date.now();
      const scaledBrushSize = this.inpaintBrushSize * scale;
      
      for (let i = 0; i <= steps; i++) {
        const t = i / steps;
        strokes.push({
          x: fromX + (toX - fromX) * t,
          y: fromY + (toY - fromY) * t,
          radius: scaledBrushSize / 2,
          timestamp: now + i * 5,
        });
      }
      
      this.maskCtx!.strokeStyle = 'white';
      this.maskCtx!.lineWidth = scaledBrushSize;
      this.maskCtx!.lineCap = 'round';
      this.maskCtx!.lineJoin = 'round';
      this.maskCtx!.beginPath();
      this.maskCtx!.moveTo(fromX, fromY);
      this.maskCtx!.lineTo(toX, toY);
      this.maskCtx!.stroke();
    };

    const addDot = (x: number, y: number, scale: number) => {
      const scaledBrushSize = this.inpaintBrushSize * scale;
      strokes.push({ x, y, radius: scaledBrushSize / 2, timestamp: Date.now() });
      
      this.maskCtx!.fillStyle = 'white';
      this.maskCtx!.beginPath();
      this.maskCtx!.arc(x, y, scaledBrushSize / 2, 0, Math.PI * 2);
      this.maskCtx!.fill();
    };

    const draw = (e: MouseEvent) => {
      if (!this.inpaintDrawState.drawing || !this.maskCtx) return;
      const { x, y, scaleX } = getCanvasCoords(e);
      addStrokePoints(this.inpaintDrawState.lastX, this.inpaintDrawState.lastY, x, y, scaleX);
      this.inpaintDrawState.lastX = x;
      this.inpaintDrawState.lastY = y;
    };

    this.maskOverlay.addEventListener('mousedown', (e: MouseEvent) => {
      const { x, y, scaleX } = getCanvasCoords(e);
      this.inpaintDrawState = { drawing: true, lastX: x, lastY: y };
      isDrawing = true;
      addDot(x, y, scaleX);
    });

    const updateCursorPreview = (e: MouseEvent) => {
      const rect = this.maskOverlay!.getBoundingClientRect();
      const displaySize = this.inpaintBrushSize;
      const padding = 4;
      const canvasSize = displaySize + padding * 2;
      
      cursorCanvas.width = canvasSize;
      cursorCanvas.height = canvasSize;
      cursorCanvas.style.width = `${canvasSize}px`;
      cursorCanvas.style.height = `${canvasSize}px`;
      
      const ctx = cursorCanvas.getContext('2d')!;
      ctx.clearRect(0, 0, canvasSize, canvasSize);
      
      const center = canvasSize / 2;
      const radius = displaySize / 2;
      
      ctx.beginPath();
      ctx.arc(center, center, radius, 0, Math.PI * 2);
      ctx.strokeStyle = 'rgba(0, 0, 0, 0.7)';
      ctx.lineWidth = 2;
      ctx.stroke();
      
      ctx.beginPath();
      ctx.arc(center, center, radius, 0, Math.PI * 2);
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.9)';
      ctx.lineWidth = 1;
      ctx.stroke();
      
      cursorCanvas.style.left = `${e.clientX - rect.left - center}px`;
      cursorCanvas.style.top = `${e.clientY - rect.top - center}px`;
      cursorCanvas.style.display = 'block';
    };
    
    this.maskOverlay.addEventListener('mousemove', (e: MouseEvent) => {
      draw(e);
      updateCursorPreview(e);
    });
    
    this.maskOverlay.addEventListener('mouseenter', (e: MouseEvent) => {
      updateCursorPreview(e);
    });
    
    this.maskOverlay.addEventListener('mouseup', () => {
      this.inpaintDrawState.drawing = false;
      isDrawing = false;
    });

    this.maskOverlay.addEventListener('mouseleave', () => {
      this.inpaintDrawState.drawing = false;
      isDrawing = false;
      cursorCanvas.style.display = 'none';
    });

    const cleanup = () => {
      if (animationId) cancelAnimationFrame(animationId);
    };
    
    (this.maskOverlay as HTMLElement & { _cleanup?: () => void })._cleanup = cleanup;
  }

  private cancelInpaintMode(): void {
    this.inpaintMode = false;
    this.maskCanvas = null;
    this.maskCtx = null;
    if (this.maskOverlay) {
      const cleanup = (this.maskOverlay as HTMLElement & { _cleanup?: () => void })._cleanup;
      if (cleanup) cleanup();
      this.maskOverlay.remove();
      this.maskOverlay = null;
    }
    this.showPanel('ai');
  }

  private async applyInpaint(): Promise<void> {
    if (!this.config.apiEndpoint || !this.maskCanvas) {
      this.cancelInpaintMode();
      return;
    }

    const layers = this.editor.getLayerManager().getLayers();
    const imageLayer = layers.find(l => l.type === 'image');
    if (!imageLayer || imageLayer.type !== 'image') {
      this.cancelInpaintMode();
      return;
    }

    const source = imageLayer.source;
    const canvas = document.createElement('canvas');
    canvas.width = source instanceof HTMLImageElement ? source.naturalWidth : source.width;
    canvas.height = source instanceof HTMLImageElement ? source.naturalHeight : source.height;
    const ctx = canvas.getContext('2d')!;
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(source, 0, 0);
    const base64Image = canvas.toDataURL('image/jpeg', 0.95);
    
    const maskExport = document.createElement('canvas');
    maskExport.width = canvas.width;
    maskExport.height = canvas.height;
    const maskExportCtx = maskExport.getContext('2d')!;
    maskExportCtx.fillStyle = 'black';
    maskExportCtx.fillRect(0, 0, maskExport.width, maskExport.height);
    maskExportCtx.drawImage(this.maskCanvas, 0, 0, maskExport.width, maskExport.height);
    const base64Mask = maskExport.toDataURL('image/jpeg', 1.0);
    
    const btn = this.root.querySelector('[data-action="apply-inpaint"]') as HTMLButtonElement;
    const cancelBtn = this.root.querySelector('[data-action="cancel-inpaint"]') as HTMLButtonElement;
    
    this.setAiProcessing(true);
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Processing...';
    }
    if (cancelBtn) cancelBtn.disabled = true;

    try {
      const response = await fetch(`${this.config.apiEndpoint}/api/v1/inpaint`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ image: base64Image, options: { mask: base64Mask } }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Failed to remove objects');
      }

      const result = await response.json();
      const originalWidth = canvas.width;
      const originalHeight = canvas.height;
      
      const img = new Image();
      img.onload = () => {
        const resizedCanvas = document.createElement('canvas');
        resizedCanvas.width = originalWidth;
        resizedCanvas.height = originalHeight;
        const resizedCtx = resizedCanvas.getContext('2d')!;
        resizedCtx.drawImage(img, 0, 0, originalWidth, originalHeight);
        
        this.editor.getLayerManager().updateLayer(imageLayer.id, { source: resizedCanvas });
        this.editor.saveToHistory('Remove objects');
        this.originalImageData = null;
        this.setAiProcessing(false);
        this.cancelInpaintMode();
      };
      img.onerror = () => {
        this.setAiProcessing(false);
        this.cancelInpaintMode();
        alert('Failed to load processed image');
      };
      img.src = result.image;
    } catch (error) {
      console.error('Inpaint failed:', error);
      this.setAiProcessing(false);
      this.cancelInpaintMode();
      alert(error instanceof Error ? error.message : 'Remove objects failed');
    }
  }

  private openFilePicker(): void {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = async () => {
      if (input.files?.[0]) {
        await this.editor.loadFromFile(input.files[0]);
        this.resetAdjustments();
        this.originalImageData = null;
        this.currentPreset = null;
        this.filterPreviewSource = null;
        this.filterPreviewCache.clear();
      }
    };
    input.click();
  }

  private async exportImage(): Promise<void> {
    const blob = await this.editor.export({ format: 'png', quality: 0.92 });

    if (this.config.onExport) {
      this.config.onExport(blob);
    } else {
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'edited-image.png';
      link.click();
      URL.revokeObjectURL(url);
    }
  }

  private addText(): void {
    const layerManager = this.editor.getLayerManager();
    const size = this.editor.getCanvasSize();

    layerManager.addTextLayer('Double-click to edit', {
      fontSize: 32,
      color: '#ffffff',
      transform: {
        x: size.width / 2 - 100,
        y: size.height / 2 - 20,
        scaleX: 1,
        scaleY: 1,
        rotation: 0,
        skewX: 0,
        skewY: 0,
      },
    });

    this.editor.saveToHistory('Add text');
    this.showPanel('layers');
  }

  private addLayer(): void {
    const layerManager = this.editor.getLayerManager();
    layerManager.addDrawingLayer({ name: 'New Layer' });
    this.editor.saveToHistory('Add layer');
    this.showPanel('layers');
  }

  private addShape(shapeType: 'rectangle' | 'ellipse' | 'line'): void {
    const layerManager = this.editor.getLayerManager();
    const size = this.editor.getCanvasSize();

    layerManager.addShapeLayer(shapeType, {
      fill: '#3b82f6',
      transform: {
        x: size.width / 2 - 50,
        y: size.height / 2 - 50,
        scaleX: 1,
        scaleY: 1,
        rotation: 0,
        skewX: 0,
        skewY: 0,
      },
    });

    this.editor.saveToHistory(`Add ${shapeType}`);
    this.showPanel('layers');
  }

  private toggleLayerVisibility(layerId: string): void {
    const layer = this.editor.getLayerManager().getLayer(layerId);
    if (layer) {
      this.editor.getLayerManager().updateLayer(layerId, { visible: !layer.visible });
    }
  }

  async loadImage(source: string): Promise<void> {
    await this.editor.loadImage(source);
    this.resetAdjustments();
    this.originalImageData = null;
    this.currentPreset = null;
    this.filterPreviewSource = null;
    this.filterPreviewCache.clear();
  }

  getEditor(): Editor {
    return this.editor;
  }

  destroy(): void {
    if (this.keyboardHandler) {
      document.removeEventListener('keydown', this.keyboardHandler);
    }
    this.removeCropOverlay();
    this.editor.destroy();
    this.root.remove();
  }
}
