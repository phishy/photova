'use client';

import { useEffect, useState } from 'react';

interface Stats {
  used: number;
  totalRequests: number;
  totalErrors: number;
  avgLatencyMs: number;
  activeKeys: number;
}

export default function DashboardPage() {
  const [stats, setStats] = useState<Stats | null>(null);

  useEffect(() => {
    Promise.all([
      fetch('/api/auth/me', { credentials: 'include' }).then(r => r.ok ? r.json() : null),
      fetch('/api/usage?days=30', { credentials: 'include' }).then(r => r.ok ? r.json() : null),
      fetch('/api/keys', { credentials: 'include' }).then(r => r.ok ? r.json() : null),
    ]).then(([user, usage, keys]) => {
      if (!user) return;
      setStats({
        used: user.usage?.used || 0,
        totalRequests: usage?.totalRequests || 0,
        totalErrors: usage?.totalErrors || 0,
        avgLatencyMs: usage?.avgLatencyMs || 0,
        activeKeys: keys?.keys?.filter((k: { status: string }) => k.status === 'active').length || 0,
      });
    });
  }, []);

  const successRate = stats && stats.totalRequests > 0
    ? Math.round(((stats.totalRequests - stats.totalErrors) / stats.totalRequests) * 100)
    : 100;

  return (
    <div>
      <h1 style={{ fontSize: 24, fontWeight: 600, letterSpacing: '-0.02em', marginBottom: 8 }}>Overview</h1>
      <p style={{ color: '#8b949e', fontSize: 14, marginBottom: 32 }}>Welcome back! Here&apos;s your API usage summary.</p>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16 }}>
        <StatCard label="Requests This Month" value={stats?.used ?? '0'} sub="this billing period" />
        <StatCard label="Success Rate" value={`${successRate}%`} sub="last 30 days" />
        <StatCard label="Avg Latency" value={stats?.avgLatencyMs ? `${stats.avgLatencyMs}ms` : '—'} sub="milliseconds" />
        <StatCard label="Active Keys" value={stats?.activeKeys ?? '0'} sub="API keys" />
      </div>
    </div>
  );
}

function StatCard({ label, value, sub }: { label: string; value: string | number; sub?: string }) {
  return (
    <div style={{
      padding: 20,
      background: '#161b22',
      border: '1px solid #30363d',
      borderRadius: 8,
    }}>
      <div style={{ fontSize: 13, color: '#8b949e', marginBottom: 12 }}>{label}</div>
      <div style={{
        fontSize: 28,
        fontWeight: 600,
        letterSpacing: '-0.02em',
        color: '#c9d1d9',
        marginBottom: 4,
      }}>
        {value}
      </div>
      {sub && <div style={{ fontSize: 12, color: '#6e7681' }}>{sub}</div>}
    </div>
  );
}
