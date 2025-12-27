# Brighten Monorepo - Agent Guidelines

## Project Overview

Brighten is a photo editor ecosystem with two packages:

1. **brighten** (`packages/photova/`) - JavaScript/TypeScript photo editor SDK with drop-in UI component
2. **brighten-api** (`packages/photova-api/`) - Laravel 12/PostgreSQL media processing API (Photova)

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
│   └── brighten-api/    # Media Processing API (Laravel/PostgreSQL)
├── .github/workflows/      # CI/CD
├── AGENTS.md               # This file
└── package.json            # Workspace config
```

---

# Package: brighten (Photo Editor SDK)

## Requirements

### Image Loading & Export
- User should be able to load images from file, URL, or drag-and-drop
- User should be able to export edited images in PNG, JPEG, or WebP formats
- User should be able to control export quality

### Editing Tools
- User should be able to crop images with preset aspect ratios or freeform
- User should be able to rotate and flip images
- User should be able to draw/paint on images with configurable brush
- User should be able to add text layers with customizable font, size, and color
- User should be able to add shape layers (rectangle, circle, line)

### Filters & Adjustments
- User should be able to apply filter presets (Vintage, Noir, Dramatic, etc.)
- User should be able to adjust brightness, contrast, saturation, exposure
- User should be able to reset adjustments to original

### Layers
- User should be able to add multiple layers (image, text, shape, drawing)
- User should be able to reorder, show/hide, and delete layers
- User should be able to adjust layer opacity

### History
- User should be able to undo/redo edits
- User should be able to use keyboard shortcuts (Ctrl+Z, Ctrl+Y)

### AI Features (when API configured)
- User should be able to remove background from images
- User should be able to upscale images
- User should be able to unblur/enhance images
- User should be able to colorize black & white images
- User should be able to restore old photos
- User should be able to remove objects via inpainting

## Architecture

```
packages/photova/src/
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
npm test --workspace=packages/photova -- --run
npm run build --workspace=packages/photova
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

# Package: brighten-api (Photova API)

Laravel 12/PostgreSQL media processing API with dashboard UI.

## Requirements

### AI Operations
- User should be able to remove background from an image
- User should be able to upscale an image up to 4x
- User should be able to unblur/enhance an image
- User should be able to colorize a black & white image
- User should be able to restore old/damaged photos
- User should be able to remove objects from images via inpainting

### Asset Storage
- User should be able to upload images to storage
- User should be able to list uploaded assets
- User should be able to download/retrieve an asset by ID
- User should be able to delete an asset
- User should be able to configure multiple storage backends (filesystem, S3)
- User should be able to specify which bucket to use per request

### Authentication
- User should be able to sign up and log in
- User should be able to create and manage API keys
- User should be able to view usage analytics
- API operations should require valid API key when auth is enabled

### Dashboard
- User should be able to view API keys
- User should be able to view usage statistics
- User should be able to upload and manage assets
- User should be able to test operations in the playground

## Architecture

```
packages/photova-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # API controllers
│   │   └── Middleware/         # Auth middleware
│   ├── Models/                 # Eloquent models
│   ├── Providers/              # Service providers
│   └── Services/               # Business logic
│       └── Providers/          # AI provider implementations
├── config/
│   └── photova.php             # Operations and provider config
├── database/
│   ├── factories/              # Model factories for testing
│   └── migrations/             # Database migrations
├── routes/
│   └── api.php                 # API route definitions
└── tests/
    └── Feature/                # Feature tests (Pest PHP)
```

## Key Components

### Controllers (`app/Http/Controllers/Api/`)
- **AuthController** - signup, login, logout, me, update
- **ApiKeyController** - CRUD + regenerate
- **AssetController** - upload (file/base64), list, get, delete
- **UsageController** - summary, timeseries, current
- **OperationController** - AI operation execution with usage logging
- **SystemController** - health, operations, openapi.json

### Middleware (`app/Http/Middleware/`)
- **AuthenticateWithApiKey** - Validates `br_live_*` API keys via hash
- **OptionalAuth** - Skips auth when `AUTH_ENABLED=false`

### Services (`app/Services/`)
- **ProviderManager** - Provider registration and fallback routing
- **Providers/BaseProvider** - Abstract base with image encode/decode
- **Providers/ReplicateProvider** - Replicate API integration
- **Providers/FalProvider** - Fal.ai API integration
- **Providers/RemoveBgProvider** - Remove.bg API integration

### Models (`app/Models/`)
- **User** - Extended with plan, monthly_limit, verified fields
- **ApiKey** - API key with prefix, hash, status, scopes
- **Asset** - UUID-based asset storage with bucket support
- **UsageLog** - Individual request logging
- **UsageDaily** - Aggregated daily usage stats

## Configuration

Config file: `config/photova.php`

```php
return [
    'auth' => [
        'enabled' => env('AUTH_ENABLED', true),
    ],
    'operations' => [
        'background-remove' => [
            'provider' => 'replicate',
            'fallback' => 'removebg',
        ],
        'upscale' => ['provider' => 'replicate'],
        'unblur' => ['provider' => 'replicate'],
        'colorize' => ['provider' => 'replicate'],
        'inpaint' => ['provider' => 'replicate'],
        'restore' => ['provider' => 'replicate'],
    ],
    'providers' => [
        'replicate' => [
            'api_key' => env('REPLICATE_API_KEY'),
        ],
        'fal' => [
            'api_key' => env('FAL_API_KEY'),
        ],
        'removebg' => [
            'api_key' => env('REMOVEBG_API_KEY'),
        ],
    ],
    'storage' => [
        'default' => 'assets',
        'buckets' => [
            'assets' => [
                'disk' => 'local',
                'path' => 'assets',
            ],
        ],
    ],
];
```

## Environment Variables

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=brighten
DB_USERNAME=postgres
DB_PASSWORD=secret
AUTH_ENABLED=true
REPLICATE_API_KEY=r8_xxxxx
FAL_API_KEY=fal_xxxxx
REMOVEBG_API_KEY=xxxxx
```

## API Routes (22 endpoints)

| Category | Endpoints |
|----------|-----------|
| System | `GET /api/health`, `/api/operations`, `/api/openapi.json` |
| Auth | `POST /api/auth/signup`, `/login`, `/logout`, `GET/PATCH /api/auth/me` |
| API Keys | `GET/POST /api/keys`, `GET/PATCH/DELETE /api/keys/{id}`, `POST /api/keys/{id}/regenerate` |
| Usage | `GET /api/usage/summary`, `/timeseries`, `/current` |
| Assets | `GET/POST /api/assets`, `GET/DELETE /api/assets/{id}` |
| Operations | `POST /api/v1/{operation}` |

## Development Commands

```bash
cd packages/photova-api
composer install          # Install dependencies
cp .env.example .env      # Create env file
php artisan key:generate  # Generate app key
php artisan migrate       # Run migrations
php artisan serve         # Start dev server (port 8000)
./vendor/bin/pest         # Run tests
```

## Testing

Uses Pest PHP with 52 tests covering all endpoints:
- `SystemTest` - Health, operations, OpenAPI
- `AuthTest` - Signup, login, logout, profile
- `ApiKeyTest` - CRUD, regenerate, authorization
- `AssetTest` - Upload, list, get, delete, bucket filter
- `UsageTest` - Summary, timeseries, current
- `OperationTest` - All operations with mocked providers

## Adding a New Operation

1. Add to `VALID_OPERATIONS` in `OperationController.php`
2. Add operation config to `config/photova.php`
3. Ensure provider supports the operation
4. Add route constraint in `routes/api.php` (the `where` clause)

## Adding a New Provider

1. Create new file in `app/Services/Providers/`
2. Extend `BaseProvider` class
3. Implement `execute(string $operation, string $image, array $options): array`
4. Register in `ProviderManager::registerProviders()`

## API Key Format

- Prefix: `br_live_` (8 chars) + 32 random chars = 40 chars total
- Stored: `key_prefix` (first 20 chars) + `key_hash` (bcrypt)
- Validation: Match prefix → verify hash

## Integration with brighten SDK

The EditorUI component connects to the API via the `apiEndpoint` config:

```typescript
const editor = createEditorUI({
  container: '#editor',
  apiEndpoint: 'http://localhost:8000',  // Laravel API URL
});
```

For local development, set `VITE_API_ENDPOINT` in `packages/photova/.env.local`:
```
VITE_API_ENDPOINT=http://localhost:8000
```
