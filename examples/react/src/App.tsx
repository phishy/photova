import { useRef, useEffect, useCallback } from 'react';
import { createEditorUI, EditorUI } from 'brighten';

function App() {
  const containerRef = useRef<HTMLDivElement>(null);
  const editorRef = useRef<EditorUI | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (containerRef.current && !editorRef.current) {
      editorRef.current = createEditorUI({
        container: containerRef.current,
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

      editorRef.current.loadImage('https://picsum.photos/id/1015/1200/800');
    }

    return () => {
      if (editorRef.current) {
        editorRef.current.destroy();
        editorRef.current = null;
      }
    };
  }, []);

  const handleFileSelect = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file && editorRef.current) {
      await editorRef.current.loadImage(file);
    }
  }, []);

  const handleExport = useCallback(() => {
    editorRef.current?.export();
  }, []);

  return (
    <>
      <header style={styles.header}>
        <h1 style={styles.title}>Brighten - React Example</h1>
      </header>

      <main style={styles.main}>
        <div style={styles.controls}>
          <button
            style={{ ...styles.button, ...styles.primaryButton }}
            onClick={() => fileInputRef.current?.click()}
          >
            Open Image
          </button>
          <button style={styles.button} onClick={handleExport}>
            Export
          </button>
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            style={{ display: 'none' }}
            onChange={handleFileSelect}
          />
        </div>

        <div ref={containerRef} style={styles.editorContainer} />
      </main>
    </>
  );
}

const styles: Record<string, React.CSSProperties> = {
  header: {
    padding: '16px 24px',
    background: '#111',
    borderBottom: '1px solid #333',
  },
  title: {
    fontSize: '18px',
    fontWeight: 600,
  },
  main: {
    flex: 1,
    display: 'flex',
    flexDirection: 'column',
    padding: '24px',
    gap: '16px',
  },
  controls: {
    display: 'flex',
    gap: '12px',
    flexWrap: 'wrap',
  },
  button: {
    padding: '10px 20px',
    borderRadius: '8px',
    border: '1px solid #444',
    background: '#2a2a2a',
    color: '#fff',
    fontSize: '14px',
    cursor: 'pointer',
  },
  primaryButton: {
    background: '#3b82f6',
    borderColor: '#3b82f6',
  },
  editorContainer: {
    flex: 1,
    minHeight: '500px',
    borderRadius: '12px',
    overflow: 'hidden',
    border: '1px solid #333',
  },
};

export default App;
