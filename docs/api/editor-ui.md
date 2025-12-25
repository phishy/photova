# EditorUI

The drop-in UI component that provides a complete, production-ready image editor interface.

## Constructor

```typescript
new EditorUI(config: EditorUIConfig)
```

### EditorUIConfig

| Property | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `container` | `HTMLElement \| string` | Yes | - | DOM element or CSS selector |
| `image` | `string \| Blob \| HTMLImageElement` | No | `null` | Initial image to load |
| `theme` | `'light' \| 'dark'` | No | `'light'` | Color theme |
| `tools` | `ToolType[]` | No | All tools | Enabled toolbar tabs |
| `styles` | `EditorUIStyles` | No | `undefined` | Custom styling |
| `unstyled` | `boolean` | No | `false` | Skip default CSS injection |
| `apiEndpoint` | `string` | No | `undefined` | Brighten API URL for AI features |
| `onExport` | `(blob: Blob) => void` | No | `undefined` | Export callback |

### ToolType

```typescript
type ToolType = 
  | 'select' 
  | 'crop' 
  | 'brush' 
  | 'text' 
  | 'ai' 
  | 'filter' 
  | 'adjust' 
  | 'layers';
```

### EditorUIStyles

```typescript
interface EditorUIStyles {
  primary?: string;
  primaryHover?: string;
  background?: string;
  surface?: string;
  surfaceHover?: string;
  border?: string;
  text?: string;
  textSecondary?: string;
  danger?: string;
  success?: string;
  radius?: string;
  fontFamily?: string;
}
```

## Methods

### loadImage

Load an image into the editor.

```typescript
async loadImage(source: string | Blob | HTMLImageElement): Promise<void>
```

**Parameters:**

- `source` - Image URL, Blob, or HTMLImageElement

**Example:**

```typescript
// From URL
await editor.loadImage('https://example.com/photo.jpg');

// From file input
const file = inputElement.files[0];
await editor.loadImage(file);

// From existing image element
await editor.loadImage(document.getElementById('myImage'));
```

### getEditor

Get the underlying core `Editor` instance.

```typescript
getEditor(): Editor
```

**Example:**

```typescript
const coreEditor = editorUI.getEditor();
const layers = coreEditor.getLayerManager();
```

### destroy

Clean up and remove the editor from the DOM.

```typescript
destroy(): void
```

**Example:**

```typescript
// In React useEffect cleanup
useEffect(() => {
  const editor = new EditorUI({ container: ref.current });
  return () => editor.destroy();
}, []);
```

## Example Usage

### Basic

```typescript
import { EditorUI } from 'brighten';

const editor = new EditorUI({
  container: '#editor',
  image: './photo.jpg',
  theme: 'dark',
  onExport: (blob) => {
    const url = URL.createObjectURL(blob);
    window.open(url);
  }
});
```

### Filtered Tools

```typescript
const editor = new EditorUI({
  container: '#editor',
  tools: ['select', 'crop', 'filter'],  // Minimal toolset
  theme: 'dark'
});
```

### Custom Styling

```typescript
const editor = new EditorUI({
  container: '#editor',
  theme: 'dark',
  styles: {
    primary: '#8b5cf6',
    radius: '12px',
    fontFamily: 'Inter, sans-serif'
  }
});
```

### With AI Features

```typescript
const editor = new EditorUI({
  container: '#editor',
  tools: ['select', 'ai', 'filter', 'adjust'],
  apiEndpoint: 'http://localhost:3001',  // Brighten API server
  theme: 'dark'
});
```

## Events

EditorUI exposes events through the underlying Editor instance:

```typescript
const coreEditor = editorUI.getEditor();

coreEditor.on('layer:add', ({ layer }) => {
  console.log('Layer added:', layer.id);
});

coreEditor.on('history:change', ({ canUndo, canRedo }) => {
  console.log('Can undo:', canUndo, 'Can redo:', canRedo);
});
```

See [Editor](editor.md) for the full list of events.
