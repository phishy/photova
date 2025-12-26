'use client';

import { useEffect, useState } from 'react';

interface ApiKey {
  id: string;
  name: string;
  keyPrefix: string;
  status: 'active' | 'revoked';
  created: string;
  lastUsedAt?: string;
}

export default function KeysPage() {
  const [keys, setKeys] = useState<ApiKey[]>([]);
  const [newKeyName, setNewKeyName] = useState('');
  const [createdKey, setCreatedKey] = useState<string | null>(null);

  const loadKeys = () => {
    fetch('/api/keys', { credentials: 'include' })
      .then(r => r.ok ? r.json() : null)
      .then(data => { if (data) setKeys(data.keys || []); });
  };

  useEffect(() => { loadKeys(); }, []);

  const createKey = async () => {
    const res = await fetch('/api/keys', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: newKeyName || 'API Key' }),
      credentials: 'include',
    });
    const data = await res.json();
    setCreatedKey(data.key);
    setNewKeyName('');
    loadKeys();
  };

  const revokeKey = async (id: string) => {
    await fetch(`/api/keys/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: 'revoked' }),
      credentials: 'include',
    });
    loadKeys();
  };

  const deleteKey = async (id: string) => {
    await fetch(`/api/keys/${id}`, { method: 'DELETE', credentials: 'include' });
    loadKeys();
  };

  return (
    <div>
      <h1 style={{ fontSize: 24, fontWeight: 600, letterSpacing: '-0.02em', marginBottom: 32 }}>API Keys</h1>

      <div style={{ display: 'flex', gap: 12, marginBottom: 24 }}>
        <input
          type="text"
          placeholder="Key name (optional)"
          value={newKeyName}
          onChange={e => setNewKeyName(e.target.value)}
          style={{
            flex: 1,
            padding: '10px 14px',
            background: '#0d1117',
            border: '1px solid #30363d',
            borderRadius: 6,
            color: '#c9d1d9',
            fontSize: 14,
            outline: 'none',
          }}
          onFocus={e => e.currentTarget.style.borderColor = '#58a6ff'}
          onBlur={e => e.currentTarget.style.borderColor = '#30363d'}
        />
        <button
          onClick={createKey}
          style={{
            padding: '10px 20px',
            background: '#238636',
            border: '1px solid #238636',
            borderRadius: 6,
            color: '#fff',
            fontSize: 14,
            fontWeight: 500,
            transition: 'background 0.15s',
          }}
          onMouseEnter={e => e.currentTarget.style.background = '#2ea043'}
          onMouseLeave={e => e.currentTarget.style.background = '#238636'}
        >
          Create key
        </button>
      </div>

      {createdKey && (
        <div style={{
          background: 'rgba(35,134,54,0.15)',
          border: '1px solid #238636',
          borderRadius: 6,
          padding: 20,
          marginBottom: 24,
        }}>
          <div style={{ fontSize: 13, color: '#8b949e', marginBottom: 12 }}>
            Your new API key (copy it now — you won&apos;t see it again)
          </div>
          <code style={{
            display: 'block',
            padding: 14,
            background: '#0d1117',
            border: '1px solid #30363d',
            borderRadius: 6,
            fontFamily: 'SF Mono, Monaco, monospace',
            fontSize: 13,
            color: '#c9d1d9',
            marginBottom: 12,
            wordBreak: 'break-all',
          }}>
            {createdKey}
          </code>
          <button
            onClick={() => navigator.clipboard.writeText(createdKey)}
            style={{
              padding: '8px 16px',
              background: '#238636',
              border: 'none',
              borderRadius: 6,
              color: '#fff',
              fontSize: 13,
              fontWeight: 500,
            }}
          >
            Copy
          </button>
        </div>
      )}

      <div style={{
        background: '#161b22',
        borderRadius: 6,
        border: '1px solid #30363d',
        overflow: 'hidden',
      }}>
        {keys.length === 0 ? (
          <div style={{ padding: 48, textAlign: 'center', color: '#8b949e', fontSize: 14 }}>
            No API keys yet. Create one to get started.
          </div>
        ) : (
          keys.map((key, i) => (
            <div
              key={key.id}
              style={{
                display: 'flex',
                alignItems: 'center',
                padding: 16,
                borderBottom: i < keys.length - 1 ? '1px solid #21262d' : 'none',
              }}
            >
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontSize: 14, fontWeight: 500, marginBottom: 4, color: '#c9d1d9' }}>{key.name}</div>
                <code style={{ fontSize: 12, color: '#8b949e', fontFamily: 'SF Mono, Monaco, monospace' }}>{key.keyPrefix}</code>
              </div>
              <div style={{
                padding: '4px 10px',
                borderRadius: 100,
                fontSize: 11,
                fontWeight: 500,
                textTransform: 'uppercase',
                letterSpacing: '0.05em',
                background: key.status === 'active' ? 'rgba(56,139,253,0.15)' : 'rgba(110,118,129,0.15)',
                color: key.status === 'active' ? '#58a6ff' : '#8b949e',
                marginRight: 12,
              }}>
                {key.status}
              </div>
              {key.status === 'active' && (
                <button
                  onClick={() => revokeKey(key.id)}
                  style={{
                    padding: '6px 12px',
                    background: 'transparent',
                    border: '1px solid #30363d',
                    borderRadius: 6,
                    color: '#8b949e',
                    fontSize: 12,
                    marginRight: 8,
                    transition: 'all 0.15s',
                  }}
                  onMouseEnter={e => { e.currentTarget.style.borderColor = '#8b949e'; e.currentTarget.style.color = '#c9d1d9'; }}
                  onMouseLeave={e => { e.currentTarget.style.borderColor = '#30363d'; e.currentTarget.style.color = '#8b949e'; }}
                >
                  Revoke
                </button>
              )}
              <button
                onClick={() => deleteKey(key.id)}
                style={{
                  padding: '6px 12px',
                  background: 'transparent',
                  border: '1px solid #f8514966',
                  borderRadius: 6,
                  color: '#f85149',
                  fontSize: 12,
                  transition: 'all 0.15s',
                }}
                onMouseEnter={e => { e.currentTarget.style.background = 'rgba(248,81,73,0.1)'; }}
                onMouseLeave={e => { e.currentTarget.style.background = 'transparent'; }}
              >
                Delete
              </button>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
