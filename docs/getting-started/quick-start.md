# Quick Start

Get a fully-functional image editor running in under 5 minutes.

## Basic Setup

### 1. Create a Container

Add a container element to your HTML:

```html
<div id="editor" style="width: 100%; height: 600px;"></div>
```

### 2. Initialize the Editor

```typescript
import { EditorUI } from 'brighten';

const editor = new EditorUI({
  container: '#editor',
  theme: 'dark',
  onExport: (blob) => {
    // Download the edited image
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'edited-image.png';
    a.click();
  }
});
```

### 3. Load an Image

```typescript
// Load from URL
await editor.loadImage('./photo.jpg');

// Or load from file input
document.getElementById('file-input').addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file) {
    editor.loadImage(file);
  }
});
```

## Complete Example

```html
<!DOCTYPE html>
<html>
<head>
  <title>Brighten Editor</title>
  <script src="https://unpkg.com/brighten@latest/dist/brighten.umd.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/brighten@latest/dist/style.css">
  <style>
    body { margin: 0; font-family: system-ui; }
    #editor { width: 100vw; height: 100vh; }
  </style>
</head>
<body>
  <div id="editor"></div>
  <script>
    const editor = new Brighten.EditorUI({
      container: '#editor',
      theme: 'dark',
      onExport: (blob) => {
        const url = URL.createObjectURL(blob);
        window.open(url);
      }
    });
  </script>
</body>
</html>
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `container` | `HTMLElement \| string` | required | DOM element or selector |
| `image` | `string \| Blob \| HTMLImageElement` | `null` | Initial image to load |
| `theme` | `'light' \| 'dark'` | `'light'` | UI theme |
| `tools` | `ToolType[]` | all tools | Enabled tools/tabs |
| `styles` | `EditorUIStyles` | `undefined` | Custom styling |
| `unstyled` | `boolean` | `false` | Skip default CSS |
| `apiEndpoint` | `string` | `undefined` | Brighten API URL for AI features |
| `onExport` | `(blob: Blob) => void` | `undefined` | Export callback |

## Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Undo | ++ctrl+z++ |
| Redo | ++ctrl+y++ / ++ctrl+shift+z++ |
| Delete Layer | ++delete++ / ++backspace++ |
| Select Tool | ++v++ |
| Crop Tool | ++c++ |
| Brush Tool | ++b++ |
| Text Tool | ++t++ |
| Pan | ++h++ / ++space++ (hold) |

## Next Steps

- [Framework Examples](frameworks.md) - React, Vue, Next.js integration
- [Customization](../customization/tools.md) - Customize tools and appearance
- [AI Features](../ai/overview.md) - Enable AI-powered editing
