# Editor

The core editor class for programmatic image manipulation. Use this for headless mode or custom UIs.

## Constructor

```typescript
new Editor(config: EditorConfig)
```

### EditorConfig

| Property | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `container` | `HTMLElement` | Yes | - | DOM element to mount canvas |
| `width` | `number` | No | `800` | Canvas width in pixels |
| `height` | `number` | No | `600` | Canvas height in pixels |
| `transparent` | `boolean` | No | `true` | Enable transparency support |

## Methods

### loadImage

Load an image into the editor.

```typescript
async loadImage(source: string | Blob | HTMLImageElement): Promise<void>
```

### export

Export the current canvas to a Blob.

```typescript
async export(options?: ExportOptions): Promise<Blob>
```

**ExportOptions:**

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `format` | `'png' \| 'jpeg' \| 'webp'` | `'png'` | Output format |
| `quality` | `number` | `0.92` | Quality for JPEG/WebP (0-1) |

**Example:**

```typescript
// Export as PNG
const pngBlob = await editor.export({ format: 'png' });

// Export as JPEG with quality
const jpegBlob = await editor.export({ format: 'jpeg', quality: 0.85 });
```

### undo / redo

Navigate history.

```typescript
undo(): void
redo(): void
```

### getLayerManager

Get the LayerManager instance.

```typescript
getLayerManager(): LayerManager
```

### getCanvasManager

Get the CanvasManager instance.

```typescript
getCanvasManager(): CanvasManager
```

### getHistoryManager

Get the HistoryManager instance.

```typescript
getHistoryManager(): HistoryManager
```

### getImageData

Get the current canvas ImageData.

```typescript
getImageData(): ImageData
```

### setImageData

Set canvas ImageData directly.

```typescript
setImageData(imageData: ImageData): void
```

### destroy

Clean up and remove the editor.

```typescript
destroy(): void
```

## Events

Subscribe using `on(event, callback)` and unsubscribe with `off(event, callback)`.

### Layer Events

```typescript
// Layer added
editor.on('layer:add', ({ layer }) => {
  console.log('Added:', layer.id, layer.type);
});

// Layer updated
editor.on('layer:update', ({ layerId, changes }) => {
  console.log('Updated:', layerId, changes);
});

// Layer deleted
editor.on('layer:delete', ({ layerId }) => {
  console.log('Deleted:', layerId);
});

// Layer selected
editor.on('layer:select', ({ layerId }) => {
  console.log('Selected:', layerId);
});

// Layer order changed
editor.on('layer:reorder', ({ layers }) => {
  console.log('New order:', layers.map(l => l.id));
});
```

### History Events

```typescript
editor.on('history:change', ({ canUndo, canRedo }) => {
  undoBtn.disabled = !canUndo;
  redoBtn.disabled = !canRedo;
});

editor.on('history:undo', ({ state }) => {
  console.log('Undo performed');
});

editor.on('history:redo', ({ state }) => {
  console.log('Redo performed');
});
```

### Canvas Events

```typescript
editor.on('canvas:render', () => {
  console.log('Canvas rendered');
});

editor.on('image:load', ({ width, height }) => {
  console.log('Image loaded:', width, 'x', height);
});
```

## Example Usage

### Basic Editing

```typescript
import { Editor } from 'brighten';

const editor = new Editor({
  container: document.getElementById('canvas'),
  width: 1200,
  height: 800
});

// Load image
await editor.loadImage('photo.jpg');

// Add text
const layers = editor.getLayerManager();
layers.addTextLayer('Watermark', {
  fontSize: 24,
  color: '#ffffff',
  x: 50,
  y: 50
});

// Export
const blob = await editor.export({ format: 'png' });
```

### Batch Processing

```typescript
import { Editor, FilterEngine } from 'brighten';

async function processImage(imageUrl: string, preset: string): Promise<Blob> {
  const container = document.createElement('div');
  document.body.appendChild(container);
  
  const editor = new Editor({ container, width: 800, height: 600 });
  const filterEngine = new FilterEngine();
  
  await editor.loadImage(imageUrl);
  
  const imageData = editor.getImageData();
  const filtered = filterEngine.applyPreset(imageData, preset);
  editor.setImageData(filtered);
  
  const blob = await editor.export({ format: 'jpeg', quality: 0.9 });
  
  editor.destroy();
  container.remove();
  
  return blob;
}
```
