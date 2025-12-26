'use client';

import { useState, useEffect, useRef } from 'react';

interface ApiKey {
  id: string;
  name: string;
  key: string;
  keyPrefix: string;
  status: string;
}

export default function PlaygroundPage() {
  const [keys, setKeys] = useState<ApiKey[]>([]);
  const [selectedKeyId, setSelectedKeyId] = useState('');
  const [loading, setLoading] = useState(true);
  const [editorLoaded, setEditorLoaded] = useState(false);
  const editorContainerRef = useRef<HTMLDivElement>(null);
  const editorInstanceRef = useRef<unknown>(null);

  useEffect(() => {
    const loadKeys = async () => {
      try {
        const res = await fetch('/api/keys', { credentials: 'include' });
        if (res.ok) {
          const data = await res.json();
          const activeKeys = (data.keys || []).filter((k: ApiKey) => k.status === 'active');
          setKeys(activeKeys);
          
          const lastUsed = localStorage.getItem('playground-last-key-id');
          if (lastUsed && activeKeys.some((k: ApiKey) => k.id === lastUsed)) {
            setSelectedKeyId(lastUsed);
          } else if (activeKeys.length > 0) {
            setSelectedKeyId(activeKeys[0].id);
          }
        }
      } catch (err) {
        console.error('Failed to load keys:', err);
      }
      setLoading(false);
    };
    
    loadKeys();
  }, []);

  const selectedKey = keys.find(k => k.id === selectedKeyId);

  useEffect(() => {
    if (!selectedKey?.key || !editorContainerRef.current || editorLoaded) return;

    localStorage.setItem('playground-last-key-id', selectedKey.id);

    const initEditor = async () => {
      try {
        const { EditorUI } = await import('brighten');
        
        if (editorContainerRef.current && !editorInstanceRef.current) {
          editorContainerRef.current.innerHTML = '';
          
          const editor = new EditorUI({
            container: editorContainerRef.current,
            theme: 'dark',
            apiEndpoint: window.location.origin,
            apiKey: selectedKey.key,
            showHeader: true,
            showSidebar: true,
            showPanel: true,
            image: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80',
          });
          
          editorInstanceRef.current = editor;
          setEditorLoaded(true);
        }
      } catch (err) {
        console.error('Failed to load editor:', err);
      }
    };

    initEditor();
  }, [selectedKey, editorLoaded]);

  const handleKeyChange = (keyId: string) => {
    setSelectedKeyId(keyId);
    setEditorLoaded(false);
    editorInstanceRef.current = null;
    if (editorContainerRef.current) {
      editorContainerRef.current.innerHTML = '';
    }
  };

  if (loading) {
    return <div style={{ padding: 48, textAlign: 'center', color: '#8b949e' }}>Loading...</div>;
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: 'calc(100vh - 64px)' }}>
      <div style={{ 
        display: 'flex', 
        justifyContent: 'space-between', 
        alignItems: 'center',
        marginBottom: 16,
        flexShrink: 0,
        gap: 16,
        flexWrap: 'wrap',
      }}>
        <h1 style={{ fontSize: 24, fontWeight: 600, letterSpacing: '-0.02em', color: '#c9d1d9' }}>Playground</h1>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
          <label style={{ fontSize: 13, color: '#8b949e' }}>API Key:</label>
          {keys.length > 0 ? (
            <select
              value={selectedKeyId}
              onChange={e => handleKeyChange(e.target.value)}
              style={{
                padding: '8px 12px',
                background: '#0d1117',
                border: '1px solid #30363d',
                borderRadius: 6,
                color: '#c9d1d9',
                fontSize: 13,
                minWidth: 200,
                outline: 'none',
              }}
            >
              {keys.map(k => (
                <option key={k.id} value={k.id}>
                  {k.name} ({k.keyPrefix})
                </option>
              ))}
            </select>
          ) : (
            <span style={{ fontSize: 13, color: '#8b949e' }}>No API keys</span>
          )}
          <a
            href="/dashboard/keys"
            style={{
              padding: '8px 14px',
              background: '#238636',
              border: 'none',
              borderRadius: 6,
              color: '#fff',
              fontSize: 13,
              fontWeight: 500,
              textDecoration: 'none',
            }}
          >
            Manage Keys
          </a>
        </div>
      </div>

      {!selectedKey ? (
        <div style={{ 
          flex: 1,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: '#161b22',
          borderRadius: 6,
          border: '1px solid #30363d',
        }}>
          <div style={{ textAlign: 'center', padding: 32, maxWidth: 400 }}>
            <h2 style={{ fontSize: 18, fontWeight: 600, marginBottom: 12, color: '#c9d1d9' }}>No API Keys Found</h2>
            <p style={{ color: '#8b949e', marginBottom: 24, lineHeight: 1.6, fontSize: 14 }}>
              Create an API key to use the editor with AI features.
            </p>
            <a
              href="/dashboard/keys"
              style={{
                display: 'inline-block',
                padding: '10px 20px',
                background: '#238636',
                color: '#fff',
                border: 'none',
                borderRadius: 6,
                fontWeight: 500,
                fontSize: 14,
                textDecoration: 'none',
              }}
            >
              Create API Key
            </a>
          </div>
        </div>
      ) : !selectedKey.key ? (
        <div style={{ 
          flex: 1,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: '#161b22',
          borderRadius: 6,
          border: '1px solid #30363d',
        }}>
          <div style={{ textAlign: 'center', padding: 32, maxWidth: 450 }}>
            <h2 style={{ fontSize: 18, fontWeight: 600, marginBottom: 12, color: '#c9d1d9' }}>Key Needs Refresh</h2>
            <p style={{ color: '#8b949e', marginBottom: 24, lineHeight: 1.6, fontSize: 14 }}>
              This API key was created before we started storing full keys. 
              Please delete it and create a new one to use in the Playground.
            </p>
            <a
              href="/dashboard/keys"
              style={{
                display: 'inline-block',
                padding: '10px 20px',
                background: '#238636',
                color: '#fff',
                border: 'none',
                borderRadius: 6,
                fontWeight: 500,
                fontSize: 14,
                textDecoration: 'none',
              }}
            >
              Manage API Keys
            </a>
          </div>
        </div>
      ) : (
        <div 
          ref={editorContainerRef}
          style={{ 
            flex: 1,
            minHeight: 0,
            background: '#161b22',
            borderRadius: 6,
            border: '1px solid #30363d',
            overflow: 'hidden',
          }}
        />
      )}
    </div>
  );
}
