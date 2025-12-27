# AI Features Overview

Brighten includes AI-powered editing features through the companion **Brighten API** server.

## Available AI Features

| Feature | Description |
|---------|-------------|
| **Background Removal** | Remove backgrounds from photos automatically |
| **Colorize** | Add color to black & white photos |
| **Restore** | Restore and enhance old or damaged photos |
| **Unblur** | Sharpen and deblur images |
| **Inpaint** | Remove objects and fill with AI-generated content |

## Architecture

```
┌─────────────────┐     HTTP      ┌─────────────────┐     API      ┌─────────────┐
│   Brighten UI   │ ────────────> │  Brighten API   │ ──────────> │  Replicate  │
│   (Browser)     │               │  (Your Server)  │             │  fal.ai     │
└─────────────────┘               └─────────────────┘             │  remove.bg  │
                                                                   └─────────────┘
```

1. **Brighten UI** - The editor in the browser
2. **Brighten API** - Your server that proxies requests (keeps API keys secure)
3. **AI Providers** - Replicate, fal.ai, remove.bg, etc.

## Quick Setup

### 1. Start the Brighten API Server

```bash
cd packages/photova-api

# Configure your API keys
cp config.example.yaml config.yaml
# Edit config.yaml with your Replicate API key

# Start the server
npm run dev
```

### 2. Connect the Editor

```typescript
import { EditorUI } from 'brighten';

const editor = new EditorUI({
  container: '#editor',
  apiEndpoint: 'http://localhost:3001',  // Your Brighten API URL
  tools: ['select', 'ai', 'filter', 'adjust'],
  theme: 'dark'
});
```

### 3. Use AI Features

The AI tab in the sidebar will now have functional buttons:

- **Remove Background** - One-click background removal
- **Colorize** - Colorize B&W photos
- **Restore Photo** - Enhance and restore old photos

## Programmatic Usage

You can also call AI features programmatically:

```typescript
// Using EditorUI
const editorUI = new EditorUI({ container: '#editor', apiEndpoint: 'http://localhost:3001' });

// The AI methods are available on the EditorUI instance
// (They're exposed via the UI buttons, but can be called directly)
```

Or call the API directly:

```typescript
// Direct API call
const response = await fetch('http://localhost:3001/v1/background-remove', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    image: 'data:image/png;base64,...'  // Base64 encoded image
  })
});

const result = await response.json();
// result.image contains the processed image as base64
```

## Next Steps

- [Photova API Setup](photova-api.md) - Detailed server configuration
- [API Endpoints](endpoints.md) - Full endpoint reference
