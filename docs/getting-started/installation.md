# Installation

## Package Manager

Install Brighten using your preferred package manager:

=== "npm"

    ```bash
    npm install brighten
    ```

=== "yarn"

    ```bash
    yarn add brighten
    ```

=== "pnpm"

    ```bash
    pnpm add brighten
    ```

## CDN / UMD

For quick prototyping or non-bundled environments, use the UMD build:

```html
<script src="https://unpkg.com/brighten@latest/dist/brighten.umd.js"></script>
<link rel="stylesheet" href="https://unpkg.com/brighten@latest/dist/style.css">
```

The library will be available as `window.Brighten`:

```javascript
const editor = new Brighten.EditorUI({
  container: '#editor',
  image: './photo.jpg'
});
```

## Requirements

- **Browser**: Chrome 80+, Firefox 75+, Safari 13.1+, Edge 80+
- **Node.js**: 18+ (for build tools only, Brighten runs in browsers)

## TypeScript

Brighten is written in TypeScript and ships with full type definitions. No additional `@types` packages needed.

```typescript
import { EditorUI, EditorUIConfig, Editor } from 'brighten';

const config: EditorUIConfig = {
  container: '#editor',
  theme: 'dark'
};

const editor = new EditorUI(config);
```

## Next Steps

- [Quick Start](quick-start.md) - Get your first editor running
- [Framework Examples](frameworks.md) - React, Vue, Next.js integration
