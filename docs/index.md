# Brighten

**The AI-native image editor SDK.**

Background removal, object erasing, and enhancement — built in, not bolted on.

![Brighten Screenshot](assets/screenshot.png)

## Features

- [x] **Drop-in UI Component** - Complete, production-ready editor interface
- [x] **AI-Powered Tools** - Background removal, object erasing, photo restoration, colorization
- [x] **Filters & Presets** - 15+ professional filters (Vintage, Noir, Dramatic, etc.)
- [x] **Advanced Adjustments** - Real-time control over brightness, contrast, saturation, and more
- [x] **Essential Tools** - Crop with presets, Transform, Brush, Text, and Shapes
- [x] **Layer System** - Photoshop-style layers for non-destructive editing
- [x] **Fully Customizable** - Filter tools, custom themes, or go fully headless
- [x] **Framework Agnostic** - Works with React, Vue, Next.js, or vanilla JS

## Quick Install

```bash
npm install brighten
```

## Quick Start

```typescript
import { EditorUI } from 'brighten';

const editor = new EditorUI({
  container: '#editor',
  image: './photo.jpg',
  theme: 'dark',
  onExport: (blob) => {
    // Handle the exported image
    const url = URL.createObjectURL(blob);
    window.open(url);
  }
});
```

[Get Started :material-arrow-right:](getting-started/installation.md){ .md-button .md-button--primary }
[Try the Demo :material-play:](demo/){ .md-button }
[View on GitHub :material-github:](https://github.com/phishy/brighten){ .md-button }

## Links

- [**Live Demo**](demo/) - Try Brighten in your browser
- [**API Reference**](api/editor-ui.md) - Full API documentation
- [**OpenAPI Spec**](openapi.json) - Brighten API OpenAPI specification
