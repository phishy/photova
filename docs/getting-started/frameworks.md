# Framework Examples

Brighten works with any JavaScript framework. Here are integration patterns for popular frameworks.

## React

```tsx
import { useEffect, useRef } from 'react';
import { EditorUI } from 'brighten';

function ImageEditor({ image, onExport }) {
  const containerRef = useRef<HTMLDivElement>(null);
  const editorRef = useRef<EditorUI | null>(null);

  useEffect(() => {
    if (!containerRef.current) return;

    editorRef.current = new EditorUI({
      container: containerRef.current,
      image,
      theme: 'dark',
      onExport
    });

    return () => {
      editorRef.current?.destroy();
    };
  }, []);

  // Update image when prop changes
  useEffect(() => {
    if (image && editorRef.current) {
      editorRef.current.loadImage(image);
    }
  }, [image]);

  return <div ref={containerRef} style={{ width: '100%', height: '600px' }} />;
}

export default ImageEditor;
```

!!! tip "Full Example"
    See the complete React example in [`examples/react/`](https://github.com/phishy/photova/tree/main/packages/photova/examples/react)

## Vue 3

```vue
<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { EditorUI } from 'brighten';

const props = defineProps<{
  image?: string;
}>();

const emit = defineEmits<{
  export: [blob: Blob];
}>();

const container = ref<HTMLElement | null>(null);
let editor: EditorUI | null = null;

onMounted(() => {
  if (!container.value) return;

  editor = new EditorUI({
    container: container.value,
    image: props.image,
    theme: 'dark',
    onExport: (blob) => emit('export', blob)
  });
});

watch(() => props.image, (newImage) => {
  if (newImage && editor) {
    editor.loadImage(newImage);
  }
});

onUnmounted(() => {
  editor?.destroy();
});
</script>

<template>
  <div ref="container" style="width: 100%; height: 600px;"></div>
</template>
```

!!! tip "Full Example"
    See the complete Vue example in [`examples/vue/`](https://github.com/phishy/photova/tree/main/packages/photova/examples/vue)

## Next.js (App Router)

```tsx
'use client';

import { useEffect, useRef } from 'react';
import type { EditorUI as EditorUIType } from 'brighten';

interface Props {
  image?: string;
  onExport?: (blob: Blob) => void;
}

export default function ImageEditor({ image, onExport }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);
  const editorRef = useRef<EditorUIType | null>(null);

  useEffect(() => {
    // Dynamic import to avoid SSR issues
    import('brighten').then(({ EditorUI }) => {
      if (!containerRef.current || editorRef.current) return;

      editorRef.current = new EditorUI({
        container: containerRef.current,
        image,
        theme: 'dark',
        onExport
      });
    });

    return () => {
      editorRef.current?.destroy();
      editorRef.current = null;
    };
  }, []);

  return <div ref={containerRef} className="w-full h-[600px]" />;
}
```

!!! warning "SSR Note"
    Brighten uses browser APIs (Canvas, DOM) and must be imported dynamically in Next.js to avoid server-side rendering errors.

!!! tip "Full Example"
    See the complete Next.js example in [`examples/nextjs/`](https://github.com/phishy/photova/tree/main/packages/photova/examples/nextjs)

## Vanilla JavaScript

```html
<!DOCTYPE html>
<html>
<head>
  <script src="https://unpkg.com/brighten@latest/dist/brighten.umd.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/brighten@latest/dist/style.css">
</head>
<body>
  <input type="file" id="file-input" accept="image/*">
  <div id="editor" style="width: 100%; height: 600px;"></div>

  <script>
    const editor = new Brighten.EditorUI({
      container: '#editor',
      theme: 'dark',
      onExport: (blob) => {
        const url = URL.createObjectURL(blob);
        window.open(url);
      }
    });

    document.getElementById('file-input').addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) editor.loadImage(file);
    });
  </script>
</body>
</html>
```

!!! tip "Full Example"
    See the complete vanilla example in [`examples/vanilla/`](https://github.com/phishy/photova/tree/main/packages/photova/examples/vanilla)
