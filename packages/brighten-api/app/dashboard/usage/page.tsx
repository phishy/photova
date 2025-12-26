'use client';

import { useEffect, useState } from 'react';

interface UsageData {
  period: { start: string; end: string };
  totalRequests: number;
  totalErrors: number;
  avgLatencyMs: number;
  byOperation: Record<string, { requests: number; errors: number; avgLatencyMs: number }>;
  byDay: Array<{ date: string; requests: number; errors: number }>;
}

export default function UsagePage() {
  const [usage, setUsage] = useState<UsageData | null>(null);
  const [days, setDays] = useState(30);

  useEffect(() => {
    fetch(`/api/usage?days=${days}`, { credentials: 'include' })
      .then(r => r.ok ? r.json() : null)
      .then(data => { if (data) setUsage(data); });
  }, [days]);

  if (!usage) {
    return <div style={{ padding: 48, textAlign: 'center', color: '#8b949e' }}>Loading...</div>;
  }

  const maxRequests = Math.max(...usage.byDay.map(d => d.requests), 1);
  const successRate = usage.totalRequests > 0
    ? Math.round(((usage.totalRequests - usage.totalErrors) / usage.totalRequests) * 100)
    : 100;

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 32 }}>
        <h1 style={{ fontSize: 24, fontWeight: 600, letterSpacing: '-0.02em', color: '#c9d1d9' }}>Usage</h1>
        <select
          value={days}
          onChange={e => setDays(Number(e.target.value))}
          style={{
            padding: '8px 12px',
            background: '#0d1117',
            border: '1px solid #30363d',
            borderRadius: 6,
            color: '#c9d1d9',
            fontSize: 13,
            outline: 'none',
          }}
        >
          <option value={7}>Last 7 days</option>
          <option value={30}>Last 30 days</option>
          <option value={90}>Last 90 days</option>
        </select>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: 16, marginBottom: 32 }}>
        <StatCard label="Total requests" value={usage.totalRequests} />
        <StatCard label="Success rate" value={`${successRate}%`} accent />
        <StatCard label="Total errors" value={usage.totalErrors} />
        <StatCard label="Avg latency" value={`${usage.avgLatencyMs}ms`} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: 16 }}>
        <div style={{
          background: '#161b22',
          borderRadius: 6,
          border: '1px solid #30363d',
          padding: 24,
        }}>
          <h2 style={{ fontSize: 14, fontWeight: 500, marginBottom: 20, color: '#8b949e' }}>Requests over time</h2>
          <div style={{ display: 'flex', alignItems: 'flex-end', gap: 2, height: 160 }}>
            {usage.byDay.map((day, i) => (
              <div
                key={i}
                style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center' }}
                title={`${day.date}: ${day.requests} requests`}
              >
                <div
                  style={{
                    width: '100%',
                    background: '#58a6ff',
                    borderRadius: '4px 4px 0 0',
                    height: `${(day.requests / maxRequests) * 140}px`,
                    minHeight: day.requests > 0 ? 4 : 0,
                    transition: 'height 0.3s ease',
                  }}
                />
              </div>
            ))}
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 12, fontSize: 11, color: '#6e7681' }}>
            <span>{usage.period.start}</span>
            <span>{usage.period.end}</span>
          </div>
        </div>

        <div style={{
          background: '#161b22',
          borderRadius: 6,
          border: '1px solid #30363d',
          padding: 24,
        }}>
          <h2 style={{ fontSize: 14, fontWeight: 500, marginBottom: 20, color: '#8b949e' }}>By operation</h2>
          {Object.keys(usage.byOperation).length === 0 ? (
            <div style={{ color: '#8b949e', fontSize: 13, textAlign: 'center', padding: '32px 0' }}>No data yet</div>
          ) : (
            Object.entries(usage.byOperation).map(([op, stats]) => (
              <div key={op} style={{ marginBottom: 16 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 6, fontSize: 13, color: '#c9d1d9' }}>
                  <span>{op}</span>
                  <span style={{ color: '#8b949e' }}>{stats.requests}</span>
                </div>
                <div style={{ background: '#21262d', borderRadius: 4, height: 6, overflow: 'hidden' }}>
                  <div style={{
                    background: '#58a6ff',
                    height: '100%',
                    width: `${(stats.requests / usage.totalRequests) * 100}%`,
                    borderRadius: 4,
                    transition: 'width 0.3s ease',
                  }} />
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      <div style={{
        marginTop: 16,
        background: '#161b22',
        borderRadius: 6,
        border: '1px solid #30363d',
        overflow: 'hidden',
      }}>
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead>
            <tr style={{ borderBottom: '1px solid #21262d' }}>
              <th style={{ textAlign: 'left', padding: 16, fontSize: 12, color: '#8b949e', fontWeight: 500 }}>Operation</th>
              <th style={{ textAlign: 'right', padding: 16, fontSize: 12, color: '#8b949e', fontWeight: 500 }}>Requests</th>
              <th style={{ textAlign: 'right', padding: 16, fontSize: 12, color: '#8b949e', fontWeight: 500 }}>Errors</th>
              <th style={{ textAlign: 'right', padding: 16, fontSize: 12, color: '#8b949e', fontWeight: 500 }}>Avg Latency</th>
              <th style={{ textAlign: 'right', padding: 16, fontSize: 12, color: '#8b949e', fontWeight: 500 }}>Success</th>
            </tr>
          </thead>
          <tbody>
            {Object.entries(usage.byOperation).map(([op, stats]) => (
              <tr key={op} style={{ borderBottom: '1px solid #21262d' }}>
                <td style={{ padding: 16, fontSize: 13, color: '#c9d1d9' }}>{op}</td>
                <td style={{ textAlign: 'right', padding: 16, fontSize: 13, color: '#c9d1d9' }}>{stats.requests}</td>
                <td style={{ textAlign: 'right', padding: 16, fontSize: 13, color: stats.errors > 0 ? '#f85149' : '#8b949e' }}>{stats.errors}</td>
                <td style={{ textAlign: 'right', padding: 16, fontSize: 13, color: '#8b949e' }}>{stats.avgLatencyMs}ms</td>
                <td style={{ textAlign: 'right', padding: 16, fontSize: 13, color: '#58a6ff' }}>
                  {stats.requests > 0 ? Math.round(((stats.requests - stats.errors) / stats.requests) * 100) : 100}%
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

function StatCard({ label, value, accent }: { label: string; value: string | number; accent?: boolean }) {
  return (
    <div style={{
      padding: 20,
      background: '#161b22',
      border: '1px solid #30363d',
      borderRadius: 6,
    }}>
      <div style={{ fontSize: 12, color: '#8b949e', marginBottom: 6 }}>{label}</div>
      <div style={{
        fontSize: 24,
        fontWeight: 600,
        letterSpacing: '-0.02em',
        color: accent ? '#58a6ff' : '#c9d1d9',
      }}>
        {value}
      </div>
    </div>
  );
}
