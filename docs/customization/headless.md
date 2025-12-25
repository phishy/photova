# Headless Mode

For complete control over the UI, use the core `Editor` class directly instead of `EditorUI`.

## When to Use Headless

- Building a custom UI from scratch
- Integrating into an existing design system
- Server-side or automated image processing
- Creating specialized editing workflows

## Basic Usage

```typescript
import { Editor, FilterEngine, LayerManager } from 'brighten';

// Create a canvas container
const container = document.getElementById('canvas-container');

// Initialize the core editor
const editor = new Editor({
  container,
  width: 800,
  height: 600
});

// Load an image
await editor.loadImage('photo.jpg');

// Get subsystems
const layers = editor.getLayerManager();
const canvas = editor.getCanvasManager();
```

## Core Components

### Editor

The main orchestrator. Provides access to all subsystems.

```typescript
const editor = new Editor({
  container: document.getElementById('canvas'),
  width: 800,
  height: 600,
  transparent: true
});

// Load image
await editor.loadImage(imageSource);

// History
editor.undo();
editor.redo();

// Export
const blob = await editor.export({ format: 'png', quality: 0.92 });

// Cleanup
editor.destroy();
```

### LayerManager

Manages all layers in the editor.

```typescript
const layers = editor.getLayerManager();

// Add layers
const textId = layers.addTextLayer('Hello World', {
  fontSize: 32,
  color: '#ffffff',
  fontFamily: 'Arial',
  x: 100,
  y: 100
});

const drawingId = layers.addDrawingLayer();

// Update layers
layers.updateLayer(textId, { opacity: 0.8 });

// Get layers
const allLayers = layers.getLayers();
const textLayer = layers.getLayer<TextLayer>(textId);

// Reorder
layers.moveLayer(textId, 0);  // Move to bottom

// Delete
layers.deleteLayer(textId);
```

### FilterEngine

Applies filters and adjustments to image data.

```typescript
import { FilterEngine } from 'brighten';

const filterEngine = new FilterEngine();

// Get image data from canvas
const imageData = ctx.getImageData(0, 0, width, height);

// Apply a preset
const vintage = filterEngine.applyPreset(imageData, 'vintage');

// Apply custom adjustments
const adjusted = filterEngine.process(imageData, {
  brightness: 0.1,
  contrast: 0.2,
  saturation: -0.1,
  exposure: 0.05
});

// Put back on canvas
ctx.putImageData(adjusted, 0, 0);
```

### Available Filter Presets

| Preset | Description |
|--------|-------------|
| `vintage` | Warm, faded look |
| `noir` | Black and white with high contrast |
| `dramatic` | High contrast, saturated |
| `warm` | Warm color temperature |
| `cool` | Cool color temperature |
| `fade` | Lifted blacks, reduced contrast |
| `vibrant` | Increased saturation |
| `muted` | Reduced saturation |
| `sepia` | Classic sepia tone |
| `chrome` | Chrome-like high contrast |

### Available Adjustments

| Adjustment | Range | Description |
|------------|-------|-------------|
| `brightness` | -1 to 1 | Image brightness |
| `contrast` | -1 to 1 | Image contrast |
| `saturation` | -1 to 1 | Color saturation |
| `exposure` | -1 to 1 | Exposure compensation |
| `highlights` | -1 to 1 | Highlight recovery |
| `shadows` | -1 to 1 | Shadow lifting |
| `temperature` | -1 to 1 | Color temperature |
| `tint` | -1 to 1 | Green/magenta tint |
| `sharpness` | 0 to 1 | Sharpening amount |
| `blur` | 0 to 50 | Blur radius |
| `vignette` | 0 to 1 | Vignette intensity |

## Events

Subscribe to editor events for reactive UIs:

```typescript
// Layer events
editor.on('layer:add', ({ layer }) => {
  console.log('Layer added:', layer);
});

editor.on('layer:update', ({ layerId, changes }) => {
  console.log('Layer updated:', layerId, changes);
});

editor.on('layer:delete', ({ layerId }) => {
  console.log('Layer deleted:', layerId);
});

editor.on('layer:select', ({ layerId }) => {
  console.log('Layer selected:', layerId);
});

// History events
editor.on('history:change', ({ canUndo, canRedo }) => {
  undoButton.disabled = !canUndo;
  redoButton.disabled = !canRedo;
});

// Canvas events
editor.on('canvas:render', () => {
  console.log('Canvas rendered');
});
```

## Custom Tool Example

Build a custom brush tool UI:

```typescript
import { Editor, BrushTool } from 'brighten';

const editor = new Editor({ container, width: 800, height: 600 });
const canvas = editor.getCanvasManager().getDisplayCanvas();

// Create brush tool
const brush = new BrushTool();
brush.setContext(editor);

// Wire up mouse events
let isDrawing = false;

canvas.addEventListener('mousedown', (e) => {
  isDrawing = true;
  brush.onPointerDown({ x: e.offsetX, y: e.offsetY });
});

canvas.addEventListener('mousemove', (e) => {
  if (!isDrawing) return;
  brush.onPointerMove({ x: e.offsetX, y: e.offsetY });
});

canvas.addEventListener('mouseup', () => {
  if (!isDrawing) return;
  isDrawing = false;
  brush.onPointerUp();
});

// Custom UI controls
colorPicker.addEventListener('change', (e) => {
  brush.setColor(e.target.value);
});

sizeSlider.addEventListener('input', (e) => {
  brush.setSize(parseInt(e.target.value));
});
```

## Building a Custom UI

Here's a minimal custom UI example:

```html
<div id="app">
  <div id="toolbar">
    <button id="undo">Undo</button>
    <button id="redo">Redo</button>
    <select id="filter">
      <option value="">No Filter</option>
      <option value="vintage">Vintage</option>
      <option value="noir">Noir</option>
    </select>
    <button id="export">Export</button>
  </div>
  <div id="canvas-container"></div>
</div>
```

```typescript
import { Editor, FilterEngine } from 'brighten';

const editor = new Editor({
  container: document.getElementById('canvas-container'),
  width: 800,
  height: 600
});

const filterEngine = new FilterEngine();

// Toolbar handlers
document.getElementById('undo').addEventListener('click', () => editor.undo());
document.getElementById('redo').addEventListener('click', () => editor.redo());

document.getElementById('filter').addEventListener('change', (e) => {
  const preset = e.target.value;
  if (preset) {
    const imageData = editor.getImageData();
    const filtered = filterEngine.applyPreset(imageData, preset);
    editor.setImageData(filtered);
  }
});

document.getElementById('export').addEventListener('click', async () => {
  const blob = await editor.export({ format: 'png' });
  const url = URL.createObjectURL(blob);
  window.open(url);
});

// Load initial image
editor.loadImage('photo.jpg');
```
