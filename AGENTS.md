# Brighten Photo Editor SDK - Agent Guidelines

## Project Overview

Brighten is a JavaScript/TypeScript photo editor SDK that provides a drop-in component for web applications with comprehensive photo editing capabilities.

## Documentation Maintenance

**IMPORTANT**: When making changes that affect the public API, features, or usage patterns, you MUST update the relevant documentation:

- **README.md** - Update when adding/removing/changing:
  - Public API methods or classes
  - Features (tools, filters, capabilities)
  - Configuration options
  - Keyboard shortcuts
  - Installation or usage instructions
  - Browser support

- **AGENTS.md** (this file) - Update when changing:
  - Architecture or file structure
  - Development patterns or conventions
  - Build/test commands
  - Internal APIs that other agents need to know about

## Architecture

```
src/
├── core/           # Core engine (Editor, Canvas, Layers, History)
├── filters/        # Filter system with 15+ built-in filters
├── tools/          # Editing tools (Crop, Transform, Brush)
├── ai/             # AI provider integrations (background removal, etc.)
├── plugins/        # Plugin system for extensibility
├── ui/             # Drop-in UI component (EditorUI, styles, icons)
└── index.ts        # Main entry point
```

## Key Components

### Editor (`src/core/Editor.ts`)
Main entry point. Orchestrates all subsystems.

### CanvasManager (`src/core/CanvasManager.ts`)
Handles rendering pipeline with main canvas, work canvas, and display canvas.

### LayerManager (`src/core/LayerManager.ts`)
Manages layers: image, text, shape, drawing, sticker, adjustment.

### FilterEngine (`src/filters/FilterEngine.ts`)
Processes filters using Canvas 2D. Includes presets (Vintage, Noir, etc.).

### AIManager (`src/ai/AIManager.ts`)
Provider-agnostic AI integration. Supports remove.bg, Replicate.

### PluginManager (`src/plugins/PluginManager.ts`)
Hook-based plugin system for extensibility.

### EditorUI (`src/ui/EditorUI.ts`)
Drop-in UI component with toolbar, panels, and theming.

## Code Patterns

### Event System
```typescript
editor.on('layer:add', ({ layer }) => { ... });
editor.on('history:change', ({ canUndo, canRedo }) => { ... });
```

### Layer Operations
```typescript
const layerManager = editor.getLayerManager();
layerManager.addTextLayer('Hello', { fontSize: 32 });
layerManager.updateLayer(id, { opacity: 0.5 });
```

### Filter Application
```typescript
const filterEngine = new FilterEngine();
const imageData = editor.getImageData();
const filtered = filterEngine.applyPreset(imageData, 'vintage');
```

### AI Integration
```typescript
const aiManager = new AIManager();
aiManager.registerProvider('backgroundRemoval', new RemoveBgProvider({ apiKey: '...' }));
const result = await aiManager.removeBackground(imageBlob);
```

## Development Commands

```bash
npm install          # Install dependencies
npm run dev          # Development server
npm run build        # Build library
npm test -- --run    # Run tests
npm run lint         # Lint code
```

## Testing Requirements

**IMPORTANT**: After making any code changes, you MUST run `npm test -- --run` and ensure all tests pass before considering the task complete.

## Adding Features

### New Filter
1. Add type to `FilterType` in `src/core/types.ts`
2. Register processor in `FilterEngine.registerBuiltInFilters()`

### New Tool
1. Extend `BaseTool` class
2. Implement pointer event handlers
3. Export from `src/tools/index.ts`

### New AI Provider
1. Extend `AIProvider` class
2. Implement required methods
3. Export from `src/ai/index.ts`

### New Plugin
```typescript
const myPlugin: Plugin = {
  name: 'my-plugin',
  version: '1.0.0',
  initialize({ editor }) {
    // Setup
  },
  destroy() {
    // Cleanup
  }
};
```

## Type Safety

- All public APIs are fully typed
- Use generics for layer operations: `getLayer<TextLayer>(id)`
- Event system is type-safe via `EditorEvents` interface

## Performance Notes

- Canvas operations use `willReadFrequently: true` for pixel manipulation
- Rendering is queued via `requestAnimationFrame` to prevent thrashing
- Consider WebGL for heavy filter operations (future enhancement)
