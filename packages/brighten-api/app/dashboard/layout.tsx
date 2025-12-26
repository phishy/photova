'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useEffect, useState, useRef } from 'react';

const navItems = [
  { href: '/dashboard', label: 'Overview', icon: '◉' },
  { href: '/dashboard/keys', label: 'API Keys', icon: '⚿' },
  { href: '/dashboard/usage', label: 'Usage', icon: '◔' },
  { href: '/dashboard/playground', label: 'Playground', icon: '▷' },
];

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const [user, setUser] = useState<{ name: string; email: string } | null>(null);
  const authCheckRef = useRef(false);

  useEffect(() => {
    if (authCheckRef.current) return;
    authCheckRef.current = true;
    
    fetch('/api/auth/me', { credentials: 'include' })
      .then(res => {
        if (res.status === 401) {
          window.location.href = '/';
          return null;
        }
        if (!res.ok) return null;
        return res.json();
      })
      .then(data => {
        if (data) setUser(data);
      })
      .catch(() => {});
  }, []);

  const handleLogout = async () => {
    await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' });
    window.location.href = '/';
  };

  return (
    <div style={{ display: 'flex', minHeight: '100vh', background: '#0d1117' }}>
      <aside style={{
        width: 200,
        background: '#0d1117',
        borderRight: '1px solid rgba(255,255,255,0.08)',
        padding: 16,
        display: 'flex',
        flexDirection: 'column',
        position: 'fixed',
        top: 0,
        left: 0,
        bottom: 0,
      }}>
        <Link href="/" style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 15, fontWeight: 600, marginBottom: 32, padding: '0 8px', textDecoration: 'none', color: '#fff' }}>
          <span style={{ fontSize: 18 }}>☀️</span>
          <span>Brighten</span>
        </Link>
        
        <nav style={{ flex: 1 }}>
          {navItems.map(item => {
            const isActive = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 10,
                  padding: '10px 12px',
                  borderRadius: 6,
                  marginBottom: 2,
                  background: isActive ? 'rgba(56,139,253,0.15)' : 'transparent',
                  color: isActive ? '#58a6ff' : '#8b949e',
                  textDecoration: 'none',
                  fontSize: 14,
                  fontWeight: 500,
                  transition: 'all 0.15s',
                }}
                onMouseEnter={e => { if (!isActive) e.currentTarget.style.color = '#c9d1d9'; }}
                onMouseLeave={e => { if (!isActive) e.currentTarget.style.color = '#8b949e'; }}
              >
                <span style={{ fontSize: 14 }}>{item.icon}</span>
                {item.label}
              </Link>
            );
          })}
        </nav>

        <div style={{ borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: 16 }}>
          <Link
            href="/docs"
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 10,
              padding: '10px 12px',
              borderRadius: 6,
              marginBottom: 2,
              color: '#8b949e',
              textDecoration: 'none',
              fontSize: 14,
              transition: 'color 0.15s',
            }}
            onMouseEnter={e => e.currentTarget.style.color = '#c9d1d9'}
            onMouseLeave={e => e.currentTarget.style.color = '#8b949e'}
          >
            <span style={{ fontSize: 14 }}>◧</span>
            Documentation
          </Link>
          <button
            onClick={handleLogout}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 10,
              width: '100%',
              padding: '10px 12px',
              borderRadius: 6,
              background: 'transparent',
              border: 'none',
              color: '#8b949e',
              cursor: 'pointer',
              fontSize: 14,
              textAlign: 'left',
              transition: 'color 0.15s',
            }}
            onMouseEnter={e => e.currentTarget.style.color = '#c9d1d9'}
            onMouseLeave={e => e.currentTarget.style.color = '#8b949e'}
          >
            <span style={{ fontSize: 14 }}>⎋</span>
            Sign Out
          </button>
        </div>
      </aside>

      <main style={{ flex: 1, marginLeft: 200, padding: 32, minHeight: '100vh', background: '#0d1117' }}>
        {children}
      </main>
    </div>
  );
}
