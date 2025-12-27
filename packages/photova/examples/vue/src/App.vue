<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { createEditorUI, EditorUI } from 'brighten';

const containerRef = ref<HTMLElement | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
let editor: EditorUI | null = null;

onMounted(() => {
  if (containerRef.value) {
    editor = createEditorUI({
      container: containerRef.value,
      theme: 'dark',
      onExport: (blob) => {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `edited-image-${Date.now()}.png`;
        link.click();
        URL.revokeObjectURL(url);
      },
    });

    editor.loadImage('https://picsum.photos/id/1015/1200/800');
  }
});

onUnmounted(() => {
  editor?.destroy();
});

const openFile = () => {
  fileInputRef.value?.click();
};

const handleFileSelect = async (event: Event) => {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (file && editor) {
    await editor.loadImage(file);
  }
};

const handleExport = () => {
  editor?.export();
};
</script>

<template>
  <header class="header">
    <h1 class="title">Brighten - Vue Example</h1>
  </header>

  <main class="main">
    <div class="controls">
      <button class="button primary" @click="openFile">Open Image</button>
      <button class="button" @click="handleExport">Export</button>
      <input
        ref="fileInputRef"
        type="file"
        accept="image/*"
        style="display: none"
        @change="handleFileSelect"
      />
    </div>

    <div ref="containerRef" class="editor-container" />
  </main>
</template>

<style scoped>
.header {
  padding: 16px 24px;
  background: #111;
  border-bottom: 1px solid #333;
}

.title {
  font-size: 18px;
  font-weight: 600;
}

.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 24px;
  gap: 16px;
}

.controls {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.button {
  padding: 10px 20px;
  border-radius: 8px;
  border: 1px solid #444;
  background: #2a2a2a;
  color: #fff;
  font-size: 14px;
  cursor: pointer;
}

.button:hover {
  background: #3a3a3a;
}

.button.primary {
  background: #3b82f6;
  border-color: #3b82f6;
}

.button.primary:hover {
  background: #2563eb;
}

.editor-container {
  flex: 1;
  min-height: 500px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #333;
}
</style>
