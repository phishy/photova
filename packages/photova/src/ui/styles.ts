export const defaultStyles = `
.brighten-editor {
  --brighten-bg: #1a1a1a;
  --brighten-surface: #2d2d2d;
  --brighten-surface-hover: #3d3d3d;
  --brighten-border: #404040;
  --brighten-text: #ffffff;
  --brighten-text-secondary: #a0a0a0;
  --brighten-primary: #3b82f6;
  --brighten-primary-hover: #2563eb;
  --brighten-danger: #ef4444;
  --brighten-success: #22c55e;
  --brighten-radius: 6px;
  --brighten-transition: 150ms ease;

  position: relative;
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
  background: var(--brighten-bg);
  color: var(--brighten-text);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
  overflow: hidden;
  user-select: none;
}

.brighten-editor.brighten-light {
  --brighten-bg: #f5f5f5;
  --brighten-surface: #ffffff;
  --brighten-surface-hover: #e5e5e5;
  --brighten-border: #d4d4d4;
  --brighten-text: #171717;
  --brighten-text-secondary: #525252;
}

.brighten-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  background: var(--brighten-surface);
  border-bottom: 1px solid var(--brighten-border);
  min-height: 48px;
}

.brighten-header-left,
.brighten-header-center,
.brighten-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.brighten-header-center {
  flex: 1;
  justify-content: center;
}

.brighten-main {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.brighten-sidebar {
  display: flex;
  flex-direction: column;
  width: 64px;
  background: var(--brighten-surface);
  border-right: 1px solid var(--brighten-border);
}

.brighten-canvas-container {
  flex: 1;
  position: relative;
  overflow: hidden;
  background: var(--brighten-bg);
}

.brighten-panel {
  width: 280px;
  background: var(--brighten-surface);
  border-left: 1px solid var(--brighten-border);
  overflow-y: auto;
}

.brighten-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid var(--brighten-border);
  font-weight: 600;
}

.brighten-panel-content {
  padding: 16px;
}

.brighten-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  background: var(--brighten-surface);
  border: 1px solid var(--brighten-border);
  border-radius: var(--brighten-radius);
  color: var(--brighten-text);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all var(--brighten-transition);
}

.brighten-btn:hover {
  background: var(--brighten-surface-hover);
}

.brighten-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.brighten-btn-primary {
  background: var(--brighten-primary);
  border-color: var(--brighten-primary);
}

.brighten-btn-primary:hover {
  background: var(--brighten-primary-hover);
  border-color: var(--brighten-primary-hover);
}

.brighten-btn-icon {
  padding: 8px;
  min-width: 36px;
  min-height: 36px;
}

.brighten-btn svg {
  width: 16px;
  height: 16px;
  vertical-align: middle;
  flex-shrink: 0;
}

.brighten-tool-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  width: 100%;
  padding: 12px 8px;
  background: transparent;
  border: none;
  color: var(--brighten-text-secondary);
  font-size: 10px;
  cursor: pointer;
  transition: all var(--brighten-transition);
}

.brighten-tool-btn:hover {
  background: var(--brighten-surface-hover);
  color: var(--brighten-text);
}

.brighten-tool-btn.active {
  background: var(--brighten-primary);
  color: white;
}

.brighten-tool-btn svg {
  width: 24px;
  height: 24px;
}

.brighten-slider-group {
  margin-bottom: 16px;
}

.brighten-slider-label {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 13px;
  color: var(--brighten-text-secondary);
}

.brighten-slider {
  width: 100%;
  height: 4px;
  background: var(--brighten-border);
  border-radius: 2px;
  appearance: none;
  cursor: pointer;
}

.brighten-slider::-webkit-slider-thumb {
  appearance: none;
  width: 16px;
  height: 16px;
  background: var(--brighten-primary);
  border-radius: 50%;
  cursor: pointer;
}

.brighten-presets-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.brighten-preset {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 8px;
  background: var(--brighten-bg);
  border: 2px solid transparent;
  border-radius: var(--brighten-radius);
  cursor: pointer;
  transition: all var(--brighten-transition);
}

.brighten-preset:hover {
  border-color: var(--brighten-border);
}

.brighten-preset.active {
  border-color: var(--brighten-primary);
}

.brighten-preset-preview {
  width: 100%;
  aspect-ratio: 1;
  background: var(--brighten-surface);
  border-radius: 4px;
  overflow: hidden;
}

.brighten-preset-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.brighten-preset-name {
  font-size: 11px;
  color: var(--brighten-text-secondary);
}

.brighten-layers-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.brighten-layer-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: var(--brighten-bg);
  border-radius: var(--brighten-radius);
  cursor: pointer;
  transition: all var(--brighten-transition);
}

.brighten-layer-item:hover {
  background: var(--brighten-surface-hover);
}

.brighten-layer-item.active {
  background: var(--brighten-primary);
}

.brighten-layer-thumb {
  width: 32px;
  height: 32px;
  background: var(--brighten-surface);
  border-radius: 4px;
}

.brighten-layer-info {
  flex: 1;
  min-width: 0;
}

.brighten-layer-name {
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.brighten-layer-type {
  font-size: 11px;
  color: var(--brighten-text-secondary);
}

.brighten-zoom-controls {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px;
  background: var(--brighten-surface);
  border-radius: var(--brighten-radius);
}

.brighten-zoom-value {
  min-width: 48px;
  text-align: center;
  font-size: 12px;
  color: var(--brighten-text-secondary);
}

.brighten-toast {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 12px 20px;
  background: var(--brighten-surface);
  border: 1px solid var(--brighten-border);
  border-radius: var(--brighten-radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  z-index: 1000;
}

.brighten-modal-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.brighten-modal {
  background: var(--brighten-surface);
  border-radius: var(--brighten-radius);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  max-width: 90%;
  max-height: 90%;
  overflow: auto;
}

.brighten-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--brighten-border);
}

.brighten-modal-title {
  font-size: 16px;
  font-weight: 600;
}

.brighten-modal-body {
  padding: 20px;
}

.brighten-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 16px 20px;
  border-top: 1px solid var(--brighten-border);
}

.brighten-crop-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.brighten-crop-mask {
  fill: rgba(0, 0, 0, 0.5);
}

.brighten-crop-area {
  stroke: white;
  stroke-width: 2;
  fill: none;
}

.brighten-crop-handle {
  fill: white;
  stroke: var(--brighten-primary);
  stroke-width: 2;
  cursor: pointer;
  pointer-events: auto;
}

.brighten-loading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  z-index: 100;
}

.brighten-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--brighten-border);
  border-top-color: var(--brighten-primary);
  border-radius: 50%;
  animation: brighten-spin 0.8s linear infinite;
}

@keyframes brighten-spin {
  to { transform: rotate(360deg); }
}

@keyframes brighten-ai-glow-rotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.brighten-ai-border {
  position: absolute;
  inset: 0;
  z-index: 200;
  pointer-events: none;
  border-radius: 4px;
  padding: 3px;
  background: conic-gradient(
    from var(--glow-angle, 0deg),
    #BC82F3,
    #F5B9EA,
    #8D9FFF,
    #AA6EEE,
    #FF6778,
    #FFBA71,
    #C686FF,
    #BC82F3
  );
  -webkit-mask: 
    linear-gradient(#fff 0 0) content-box, 
    linear-gradient(#fff 0 0);
  mask: 
    linear-gradient(#fff 0 0) content-box, 
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  animation: brighten-ai-border-rotate 2s linear infinite;
}

@keyframes brighten-ai-border-rotate {
  to { --glow-angle: 360deg; }
}

@property --glow-angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}
`;

export function injectStyles(): void {
  if (typeof document === 'undefined') return;
  if (document.getElementById('brighten-styles')) return;

  const style = document.createElement('style');
  style.id = 'brighten-styles';
  style.textContent = defaultStyles;
  document.head.appendChild(style);
}
