# LayerManager

Manages layers in the editor, including image, text, shape, and drawing layers.

## Access

```typescript
const layers = editor.getLayerManager();
```

## Methods

### addTextLayer

Add a text layer.

```typescript
addTextLayer(text: string, options?: TextLayerOptions): string
```

**TextLayerOptions:**

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `x` | `number` | `0` | X position |
| `y` | `number` | `0` | Y position |
| `fontSize` | `number` | `24` | Font size in pixels |
| `fontFamily` | `string` | `'Arial'` | Font family |
| `color` | `string` | `'#000000'` | Text color |
| `fontWeight` | `string` | `'normal'` | Font weight |
| `fontStyle` | `string` | `'normal'` | Font style (italic, etc.) |
| `textAlign` | `string` | `'left'` | Text alignment |
| `opacity` | `number` | `1` | Layer opacity (0-1) |

**Returns:** Layer ID

**Example:**

```typescript
const textId = layers.addTextLayer('Hello World', {
  fontSize: 48,
  color: '#ffffff',
  fontFamily: 'Inter',
  x: 100,
  y: 200
});
```

### addDrawingLayer

Add a drawing layer for brush strokes.

```typescript
addDrawingLayer(): string
```

**Returns:** Layer ID

### addShapeLayer

Add a shape layer.

```typescript
addShapeLayer(shapeType: ShapeType, options?: ShapeLayerOptions): string
```

**ShapeType:** `'rectangle' | 'ellipse' | 'line' | 'arrow'`

**ShapeLayerOptions:**

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `x` | `number` | `0` | X position |
| `y` | `number` | `0` | Y position |
| `width` | `number` | `100` | Shape width |
| `height` | `number` | `100` | Shape height |
| `fill` | `string` | `'transparent'` | Fill color |
| `stroke` | `string` | `'#000000'` | Stroke color |
| `strokeWidth` | `number` | `2` | Stroke width |
| `opacity` | `number` | `1` | Layer opacity |

### addStickerLayer

Add a sticker/image layer.

```typescript
addStickerLayer(imageSource: string | HTMLImageElement, options?: StickerLayerOptions): string
```

### getLayers

Get all layers.

```typescript
getLayers(): Layer[]
```

**Returns:** Array of all layers, ordered bottom to top.

### getLayer

Get a specific layer by ID.

```typescript
getLayer<T extends Layer>(id: string): T | undefined
```

**Example:**

```typescript
const textLayer = layers.getLayer<TextLayer>(textId);
if (textLayer) {
  console.log(textLayer.text, textLayer.fontSize);
}
```

### updateLayer

Update layer properties.

```typescript
updateLayer(id: string, changes: Partial<Layer>): void
```

**Example:**

```typescript
// Update text content
layers.updateLayer(textId, { text: 'New Text' });

// Update position and opacity
layers.updateLayer(layerId, { 
  x: 200, 
  y: 150, 
  opacity: 0.8 
});
```

### deleteLayer

Delete a layer.

```typescript
deleteLayer(id: string): void
```

### selectLayer

Select a layer.

```typescript
selectLayer(id: string | null): void
```

Pass `null` to deselect all layers.

### getSelectedLayer

Get the currently selected layer.

```typescript
getSelectedLayer(): Layer | null
```

### moveLayer

Move a layer to a new position in the stack.

```typescript
moveLayer(id: string, newIndex: number): void
```

**Example:**

```typescript
// Move to bottom
layers.moveLayer(layerId, 0);

// Move to top
const allLayers = layers.getLayers();
layers.moveLayer(layerId, allLayers.length - 1);
```

### duplicateLayer

Duplicate a layer.

```typescript
duplicateLayer(id: string): string
```

**Returns:** New layer ID

## Layer Types

### Base Layer Properties

All layers share these properties:

```typescript
interface Layer {
  id: string;
  type: LayerType;
  name: string;
  visible: boolean;
  locked: boolean;
  opacity: number;
  x: number;
  y: number;
  width: number;
  height: number;
  rotation: number;
}
```

### TextLayer

```typescript
interface TextLayer extends Layer {
  type: 'text';
  text: string;
  fontSize: number;
  fontFamily: string;
  color: string;
  fontWeight: string;
  fontStyle: string;
  textAlign: string;
}
```

### DrawingLayer

```typescript
interface DrawingLayer extends Layer {
  type: 'drawing';
  paths: DrawingPath[];
}
```

### ShapeLayer

```typescript
interface ShapeLayer extends Layer {
  type: 'shape';
  shapeType: ShapeType;
  fill: string;
  stroke: string;
  strokeWidth: number;
}
```

### ImageLayer

```typescript
interface ImageLayer extends Layer {
  type: 'image';
  src: string;
  originalWidth: number;
  originalHeight: number;
}
```

## Example Usage

### Building a Layer Panel

```typescript
function renderLayerPanel() {
  const layers = editor.getLayerManager();
  const allLayers = layers.getLayers();
  const selectedLayer = layers.getSelectedLayer();
  
  return allLayers.map(layer => `
    <div 
      class="layer ${layer.id === selectedLayer?.id ? 'selected' : ''}"
      onclick="selectLayer('${layer.id}')"
    >
      <span class="type">${layer.type}</span>
      <span class="name">${layer.name}</span>
      <button onclick="toggleVisibility('${layer.id}')">
        ${layer.visible ? '👁' : '👁‍🗨'}
      </button>
      <button onclick="deleteLayer('${layer.id}')">🗑</button>
    </div>
  `).join('');
}

// Event handlers
function selectLayer(id) {
  editor.getLayerManager().selectLayer(id);
}

function toggleVisibility(id) {
  const layer = editor.getLayerManager().getLayer(id);
  editor.getLayerManager().updateLayer(id, { visible: !layer.visible });
}

function deleteLayer(id) {
  editor.getLayerManager().deleteLayer(id);
}
```

### Programmatic Layer Manipulation

```typescript
const layers = editor.getLayerManager();

// Create watermark
const watermarkId = layers.addTextLayer('© 2025 My Company', {
  fontSize: 16,
  color: 'rgba(255, 255, 255, 0.5)',
  x: 20,
  y: 20
});

// Position at bottom-right (after image loads)
editor.on('image:load', ({ width, height }) => {
  layers.updateLayer(watermarkId, {
    x: width - 150,
    y: height - 30
  });
});
```
