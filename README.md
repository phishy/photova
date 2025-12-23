# Brighten Photo Editor SDK

[![npm version](https://img.shields.io/npm/v/brighten.svg)](https://www.npmjs.com/package/brighten)
[![License: BSL 1.1](https://img.shields.io/badge/License-BSL_1.1-blue.svg)](LICENSE)
[![Bundle Size](https://img.shields.io/bundlephobia/minzip/brighten)](https://bundlephobia.com/package/brighten)

Brighten is a powerful, extensible JavaScript/TypeScript photo editor SDK for the web. It provides a drop-in UI component and a comprehensive programmatic API for image manipulation.

![Brighten Screenshot](screenshot.png)

## Features

- [x] **Drop-in UI Component** - Complete, production-ready editor interface
- [x] **Filters & Presets** - 15+ professional filters (Vintage, Noir, Dramatic, etc.)
- [x] **Advanced Adjustments** - Real-time control over brightness, contrast, saturation, exposure, and more
- [x] **Essential Tools** - Crop with presets, Transform (rotate/flip), Brush, Text, and Shapes
- [x] **Layer System** - Photoshop-style layers for non-destructive editing
- [x] **History Management** - Robust undo/redo with state serialization
- [x] **AI Integration** - Extensible architecture supporting providers like remove.bg and Replicate
- [x] **Plugin System** - Hook-based architecture for easy extensibility
- [x] **Theming** - Built-in support for light and dark modes
- [x] **Flexible Export** - multiple formats (PNG, JPEG, WebP) with quality control

## Installation

```bash
npm install brighten
# or
yarn add brighten
# or
pnpm add brighten
```

### CDN / UMD
```html
<script src="https://unpkg.com/brighten@0.1.0/dist/brighten.umd.js"></script>
```

## Examples

Check out the framework-specific examples in the [`examples/`](examples/) directory:

- [**Vanilla JS**](examples/vanilla/) - Plain HTML + JavaScript
- [**React**](examples/react/) - React 18 + TypeScript + Vite
- [**Vue**](examples/vue/) - Vue 3 + TypeScript + Vite
- [**Next.js**](examples/nextjs/) - Next.js 14 (App Router)

Each example includes setup instructions and demonstrates core features.

## Quick Start

The easiest way to use Brighten is via the drop-in UI component.

```typescript
import { EditorUI } from 'brighten';
import 'brighten/dist/style.css'; // Import styles if required by your setup

const editor = new EditorUI({
  container: document.getElementById('editor'),
  image: './path/to/image.jpg',
  theme: 'dark',
  onExport: (blob) => {
    // Handle the exported image blob
    const url = URL.createObjectURL(blob);
    window.open(url);
  }
});
```

## API Overview

### Programmatic Usage

For custom UIs or backend processing, use the core `Editor` class directly.

```typescript
import { Editor } from 'brighten';

// Initialize the core editor
const editor = new Editor({
  container: document.getElementById('canvas-container'),
  width: 800,
  height: 600
});

// Load an image
await editor.loadImage('image.jpg');

// Add a text layer
editor.getLayerManager().addTextLayer('Hello World', {
  fontSize: 32,
  color: '#ffffff',
  fontFamily: 'Arial'
});

// Apply changes
editor.getLayerManager().updateLayer(layerId, { opacity: 0.8 });

// History control
editor.undo();
editor.redo();

// Export result
const blob = await editor.export({ format: 'png', quality: 0.92 });
```

### Applying Filters

Brighten includes a powerful filter engine with 15+ built-in presets.

```typescript
import { FilterEngine } from 'brighten';

const filterEngine = new FilterEngine();
const imageData = editor.getImageData();

// Apply a preset
const vintageImage = filterEngine.applyPreset(imageData, 'vintage');

// Or apply custom adjustments
const customImage = filterEngine.process(imageData, {
  brightness: 0.1,
  contrast: 0.2,
  saturation: -0.1
});
```

### AI Integration

Enable powerful AI features like background removal by registering providers.

```typescript
import { AIManager, RemoveBgProvider } from 'brighten';

const aiManager = new AIManager();

// Register the remove.bg provider
aiManager.registerProvider('backgroundRemoval', new RemoveBgProvider({ 
  apiKey: 'your-api-key' 
}));

// Execute AI operation
try {
  const result = await aiManager.removeBackground(imageBlob);
  // result includes the processed image blob
} catch (error) {
  console.error('AI operation failed:', error);
}
```

## Configuration Options

### `EditorUI` Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `container` | `HTMLElement \| string` | required | DOM element or selector to mount the editor |
| `image` | `string \| Blob \| HTMLImageElement` | `null` | Initial image to load |
| `theme` | `'light' \| 'dark'` | `'light'` | UI theme preference |
| `tools` | `string[]` | `['all']` | List of enabled tools (optional) |
| `onExport` | `(blob: Blob) => void` | `undefined` | Callback triggered when user clicks export |

### `Editor` (Core) Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `container` | `HTMLElement` | required | DOM element for the canvas |
| `width` | `number` | `800` | Canvas width |
| `height` | `number` | `600` | Canvas height |
| `transparent` | `boolean` | `true` | Enable transparency support |

## Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| **Undo** | <kbd>Ctrl</kbd> + <kbd>Z</kbd> |
| **Redo** | <kbd>Ctrl</kbd> + <kbd>Y</kbd> / <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>Z</kbd> |
| **Delete Layer** | <kbd>Delete</kbd> / <kbd>Backspace</kbd> |
| **Select Tool** | <kbd>V</kbd> |
| **Crop Tool** | <kbd>C</kbd> |
| **Brush Tool** | <kbd>B</kbd> |
| **Text Tool** | <kbd>T</kbd> |
| **Pan Tool** | <kbd>H</kbd> / <kbd>Space</kbd> (drag) |

## Browser Support

Brighten works in all modern browsers that support Canvas 2D and ES6.

- Chrome 80+
- Firefox 75+
- Safari 13.1+
- Edge 80+

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details on setting up the development environment and submitting pull requests.

## License

This project is licensed under the Business Source License 1.1 - see the [LICENSE](LICENSE) file for details. The license converts to Apache 2.0 after 4 years.
