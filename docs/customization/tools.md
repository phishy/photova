# Tools & Tabs

Brighten allows you to customize which tools and sidebar tabs are available in the editor.

## Filtering Tools

Use the `tools` configuration option to specify which tabs appear in the sidebar:

```typescript
import { EditorUI } from 'brighten';

const editor = new EditorUI({
  container: '#editor',
  tools: ['select', 'crop', 'filter', 'adjust'],  // Only these tabs
  theme: 'dark'
});
```

## Available Tools

| Tool | Description |
|------|-------------|
| `select` | Selection and transform tool |
| `crop` | Crop tool with aspect ratio presets |
| `brush` | Freehand drawing brush |
| `text` | Text layer tool |
| `ai` | AI-powered features (background removal, etc.) |
| `filter` | Filter presets (Vintage, Noir, etc.) |
| `adjust` | Manual adjustments (brightness, contrast, etc.) |
| `layers` | Layer management panel |

## Examples

### Minimal Editor (Crop Only)

```typescript
const editor = new EditorUI({
  container: '#editor',
  tools: ['select', 'crop'],
  onExport: (blob) => downloadImage(blob)
});
```

### Photo Enhancement Editor

```typescript
const editor = new EditorUI({
  container: '#editor',
  tools: ['select', 'filter', 'adjust'],
  theme: 'dark'
});
```

### AI-Focused Editor

```typescript
const editor = new EditorUI({
  container: '#editor',
  tools: ['select', 'ai'],
  apiEndpoint: 'https://api.brighten.dev',
  theme: 'dark'
});
```

### Full Featured (Default)

```typescript
const editor = new EditorUI({
  container: '#editor',
  // tools: ['select', 'crop', 'brush', 'text', 'ai', 'filter', 'adjust', 'layers']
  // All tools enabled by default
});
```

## Tool Dependencies

Some tools require additional configuration:

| Tool | Requirement |
|------|-------------|
| `ai` | Requires `apiEndpoint` to be set |
| `brush` | Works standalone |
| `text` | Works standalone |
| `layers` | Works standalone |

!!! note "AI Features"
    The `ai` tab will show buttons for background removal, colorize, restore, etc., but these require a running [Photova API](../ai/photova-api.md) server to function.
