# Brighten Monorepo - Agent Guidelines

## Project Overview

Brighten is a photo editor ecosystem with two packages:

1. **brighten** (`packages/brighten/`) - JavaScript/TypeScript photo editor SDK with drop-in UI component
2. **brighten-api** (`packages/brighten-api/`) - Unified media processing API with configurable AI backends

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

- **docs/** - Update when changing:
  - `competition.md` - Competitive analysis, market positioning, feature comparisons

## Monorepo Structure

```
brighten/
├── packages/
│   ├── brighten/           # Photo Editor SDK
│   └── brighten-api/       # Media Processing API
├── .github/workflows/      # CI/CD
├── AGENTS.md               # This file
└── package.json            # Workspace config
```

---

# Package: brighten (Photo Editor SDK)

## Architecture

```
packages/brighten/src/
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

All commands can be run from the monorepo root with workspace flag:
```bash
npm test --workspace=packages/brighten -- --run
npm run build --workspace=packages/brighten
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

---

# Package: brighten-api (Media Processing API)

## Architecture

```
packages/brighten-api/src/
├── config/           # YAML config loading with Zod validation
│   ├── schema.ts     # Config types and Zod schemas
│   ├── loader.ts     # Loads YAML, interpolates ${ENV_VARS}
│   └── index.ts
├── operations/       # Operation type definitions
│   ├── types.ts      # OperationType, OperationInput, OperationResult
│   └── index.ts
├── providers/        # Backend implementations
│   ├── base.ts       # BaseProvider abstract class
│   ├── remote/
│   │   ├── replicate.ts   # Replicate API integration
│   │   ├── fal.ts         # fal.ai integration
│   │   └── removebg.ts    # remove.bg integration
│   └── index.ts
├── router/           # Routes operations to providers based on config
│   └── index.ts
├── server/           # Express API server
│   └── index.ts
└── index.ts
```

## Key Components

### Config Loader (`src/config/loader.ts`)
Loads YAML config with environment variable interpolation (`${VAR_NAME}`). Supports `.env.local` via dotenv.

### OperationRouter (`src/router/index.ts`)
Routes operations to configured providers with fallback support.

### Providers (`src/providers/remote/`)
- **ReplicateProvider** - Replicate API (background-remove, unblur, colorize, inpaint)
- **FalProvider** - fal.ai integration (not yet configured)
- **RemoveBgProvider** - remove.bg API (not yet configured)

### Server (`src/server/index.ts`)
Express server with endpoints:
- `POST /v1/:operation` - Execute an operation (e.g., `/v1/background-remove`)
- `GET /health` - Health check

## Configuration

Config file: `config.yaml` (or `config.yml`)

```yaml
server:
  port: 3001
  host: 0.0.0.0

operations:
  background-remove:
    provider: replicate
    fallback: removebg
  unblur:
    provider: replicate

providers:
  replicate:
    api_key: ${REPLICATE_API_KEY}
  removebg:
    api_key: ${REMOVEBG_API_KEY}
```

## Environment Variables

Store secrets in `.env.local` (gitignored):
```
REPLICATE_API_KEY=r8_xxxxx
```

## Development Commands

```bash
cd packages/brighten-api
npm run dev          # Development server with hot reload (tsx watch)
npm run build        # Build to dist/
npm start            # Run built server
npm test             # Run tests
```

Or from monorepo root:
```bash
npm run dev --workspace=packages/brighten-api
npm run build --workspace=packages/brighten-api
```

## Adding a New Operation

1. Add operation type to `OperationType` in `src/operations/types.ts`
2. Add model config in the provider (e.g., `REPLICATE_MODELS` in `replicate.ts`)
3. Add to `supportedOperations` array in the provider
4. Add operation config to `config.yaml`
5. Update `scripts/generate-openapi.ts`:
   - Add operation to `ALL_OPERATIONS` array
   - Add description in `getOperationDescription()`
6. Regenerate OpenAPI spec: `npm run generate:openapi`
7. Copy to brighten for local dev: `cp openapi.json ../brighten/public/`

## Adding a New Provider

1. Create new file in `src/providers/remote/`
2. Extend `BaseProvider` class
3. Implement `execute(operation, input)` method
4. Export from `src/providers/index.ts`
5. Register in `OperationRouter.initializeProviders()`

## API Request/Response Format

### Request
```json
POST /v1/background-remove
{
  "image": "data:image/png;base64,..."
}
```

### Response
```json
{
  "image": "data:image/png;base64,...",
  "metadata": {
    "provider": "replicate",
    "model": "...",
    "processingTime": 1234
  }
}
```

## Integration with brighten SDK

The EditorUI component connects to brighten-api via the `apiEndpoint` config:

```typescript
const editor = createEditorUI({
  container: '#editor',
  apiEndpoint: 'http://localhost:3001',  // brighten-api URL
});
```

For local development, set `VITE_API_ENDPOINT` in `packages/brighten/.env.local`:
```
VITE_API_ENDPOINT=http://localhost:3001
```
