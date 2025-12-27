@extends('dashboard.layout')

@section('title', 'Overview')

@section('content')
<div x-data="overviewPage()" x-init="loadStats()">
    <h1 class="text-2xl font-semibold tracking-tight mb-2">Overview</h1>
    <p class="text-[#8b949e] text-sm mb-8">Welcome back! Here's your API usage summary.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-lg">
            <div class="text-[13px] text-[#8b949e] mb-3">Requests This Month</div>
            <div class="text-[28px] font-semibold tracking-tight text-[#c9d1d9] mb-1" x-text="stats.used"></div>
            <div class="text-xs text-[#6e7681]">this billing period</div>
        </div>

        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-lg">
            <div class="text-[13px] text-[#8b949e] mb-3">Success Rate</div>
            <div class="text-[28px] font-semibold tracking-tight text-[#c9d1d9] mb-1" x-text="successRate + '%'"></div>
            <div class="text-xs text-[#6e7681]">last 30 days</div>
        </div>

        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-lg">
            <div class="text-[13px] text-[#8b949e] mb-3">Avg Latency</div>
            <div class="text-[28px] font-semibold tracking-tight text-[#c9d1d9] mb-1" x-text="stats.avgLatencyMs ? stats.avgLatencyMs + 'ms' : '—'"></div>
            <div class="text-xs text-[#6e7681]">milliseconds</div>
        </div>

        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-lg">
            <div class="text-[13px] text-[#8b949e] mb-3">Active Keys</div>
            <div class="text-[28px] font-semibold tracking-tight text-[#c9d1d9] mb-1" x-text="stats.activeKeys"></div>
            <div class="text-xs text-[#6e7681]">API keys</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function overviewPage() {
        return {
            stats: {
                used: 0,
                totalRequests: 0,
                totalErrors: 0,
                avgLatencyMs: 0,
                activeKeys: 0
            },

            get successRate() {
                if (this.stats.totalRequests === 0) return 100;
                return Math.round(((this.stats.totalRequests - this.stats.totalErrors) / this.stats.totalRequests) * 100);
            },

            async loadStats() {
                try {
                    const [userRes, usageRes, keysRes] = await Promise.all([
                        window.apiFetch('/api/auth/me'),
                        window.apiFetch('/api/usage/summary?days=30'),
                        window.apiFetch('/api/keys')
                    ]);

                    const user = userRes.ok ? await userRes.json() : null;
                    const usage = usageRes.ok ? await usageRes.json() : null;
                    const keys = keysRes.ok ? await keysRes.json() : null;

                    const summary = usage?.summary || {};
                    this.stats = {
                        used: summary?.totalRequests || 0,
                        totalRequests: summary?.totalRequests || 0,
                        totalErrors: summary?.totalErrors || 0,
                        avgLatencyMs: summary?.averageLatencyMs || 0,
                        activeKeys: keys?.keys?.filter(k => k.status === 'active').length || 0
                    };
                } catch (e) {
                    console.error('Failed to load stats:', e);
                }
            }
        }
    }
</script>
@endsection
