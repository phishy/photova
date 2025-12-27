@extends('dashboard.layout')

@section('title', 'Usage')

@section('content')
<div x-data="usagePage()" x-init="loadUsage()">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Usage</h1>
        <select
            x-model="days"
            @change="loadUsage()"
            class="px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-[13px] outline-none"
        >
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-md">
            <div class="text-xs text-[#8b949e] mb-1.5">Total requests</div>
            <div class="text-2xl font-semibold tracking-tight text-[#c9d1d9]" x-text="usage.totalRequests || 0"></div>
        </div>
        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-md">
            <div class="text-xs text-[#8b949e] mb-1.5">Success rate</div>
            <div class="text-2xl font-semibold tracking-tight text-[#58a6ff]" x-text="successRate + '%'"></div>
        </div>
        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-md">
            <div class="text-xs text-[#8b949e] mb-1.5">Total errors</div>
            <div class="text-2xl font-semibold tracking-tight text-[#c9d1d9]" x-text="usage.totalErrors || 0"></div>
        </div>
        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-md">
            <div class="text-xs text-[#8b949e] mb-1.5">Avg latency</div>
            <div class="text-2xl font-semibold tracking-tight text-[#c9d1d9]" x-text="(usage.averageLatencyMs || 0) + 'ms'"></div>
        </div>
        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-md">
            <div class="text-xs text-[#8b949e] mb-1.5">Total assets</div>
            <div class="text-2xl font-semibold tracking-tight text-[#c9d1d9]" x-text="assetStats.totalAssets || 0"></div>
        </div>
        <div class="p-5 bg-[#161b22] border border-[#30363d] rounded-md">
            <div class="text-xs text-[#8b949e] mb-1.5">Storage used</div>
            <div class="text-2xl font-semibold tracking-tight text-[#c9d1d9]" x-text="formatBytes(assetStats.totalSize || 0)"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Chart -->
        <div class="lg:col-span-2 bg-[#161b22] rounded-md border border-[#30363d] p-6">
            <h2 class="text-sm font-medium text-[#8b949e] mb-5">Requests over time</h2>
            <div class="flex items-end gap-0.5 h-40">
                <template x-for="(day, i) in usage.byDay || []" :key="i">
                    <div class="flex-1 flex flex-col items-center" :title="day.date + ': ' + day.requests + ' requests'">
                        <div
                            class="w-full bg-[#58a6ff] rounded-t transition-all duration-300"
                            :style="'height: ' + (maxRequests > 0 ? (day.requests / maxRequests) * 140 : 0) + 'px; min-height: ' + (day.requests > 0 ? '4px' : '0')"
                        ></div>
                    </div>
                </template>
            </div>
            <div class="flex justify-between mt-3 text-[11px] text-[#6e7681]">
                <span x-text="usage.period?.start || ''"></span>
                <span x-text="usage.period?.end || ''"></span>
            </div>
        </div>

        <!-- By Operation -->
        <div class="bg-[#161b22] rounded-md border border-[#30363d] p-6">
            <h2 class="text-sm font-medium text-[#8b949e] mb-5">By operation</h2>
            <template x-if="Object.keys(usage.byOperation || {}).length === 0">
                <div class="text-[#8b949e] text-[13px] text-center py-8">No data yet</div>
            </template>
            <template x-for="(stats, op) in usage.byOperation || {}" :key="op">
                <div class="mb-4">
                    <div class="flex justify-between mb-1.5 text-[13px] text-[#c9d1d9]">
                        <span x-text="op"></span>
                        <span class="text-[#8b949e]" x-text="stats.requests"></span>
                    </div>
                    <div class="bg-[#21262d] rounded h-1.5 overflow-hidden">
                        <div
                            class="bg-[#58a6ff] h-full rounded transition-all duration-300"
                            :style="'width: ' + (usage.totalRequests > 0 ? (stats.requests / usage.totalRequests) * 100 : 0) + '%'"
                        ></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Operations Table -->
    <div class="mt-4 bg-[#161b22] rounded-md border border-[#30363d] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#21262d]">
                    <th class="text-left p-4 text-xs text-[#8b949e] font-medium">Operation</th>
                    <th class="text-right p-4 text-xs text-[#8b949e] font-medium">Requests</th>
                    <th class="text-right p-4 text-xs text-[#8b949e] font-medium">Errors</th>
                    <th class="text-right p-4 text-xs text-[#8b949e] font-medium">Avg Latency</th>
                    <th class="text-right p-4 text-xs text-[#8b949e] font-medium">Success</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(stats, op) in usage.byOperation || {}" :key="op">
                    <tr class="border-b border-[#21262d]">
                        <td class="p-4 text-[13px] text-[#c9d1d9]" x-text="op"></td>
                        <td class="text-right p-4 text-[13px] text-[#c9d1d9]" x-text="stats.requests"></td>
                        <td class="text-right p-4 text-[13px]" :class="stats.errors > 0 ? 'text-[#f85149]' : 'text-[#8b949e]'" x-text="stats.errors"></td>
                        <td class="text-right p-4 text-[13px] text-[#8b949e]" x-text="stats.avgLatencyMs + 'ms'"></td>
                        <td class="text-right p-4 text-[13px] text-[#58a6ff]" x-text="stats.requests > 0 ? Math.round(((stats.requests - stats.errors) / stats.requests) * 100) + '%' : '100%'"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Storage by Type -->
    <template x-if="Object.keys(assetStats.byType || {}).length > 0">
        <div class="mt-4 bg-[#161b22] rounded-md border border-[#30363d] p-6">
            <h2 class="text-sm font-medium text-[#8b949e] mb-5">Storage by type</h2>
            <template x-for="(stats, type) in assetStats.byType || {}" :key="type">
                <div class="mb-4">
                    <div class="flex justify-between mb-1.5 text-[13px] text-[#c9d1d9]">
                        <span class="capitalize" x-text="type"></span>
                        <span class="text-[#8b949e]" x-text="stats.count + ' files • ' + formatBytes(stats.size)"></span>
                    </div>
                    <div class="bg-[#21262d] rounded h-1.5 overflow-hidden">
                        <div
                            class="bg-[#a371f7] h-full rounded transition-all duration-300"
                            :style="'width: ' + (assetStats.totalSize > 0 ? (stats.size / assetStats.totalSize) * 100 : 0) + '%'"
                        ></div>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    function usagePage() {
        return {
            days: 30,
            usage: {},
            assetStats: { totalAssets: 0, totalSize: 0, byType: {} },

            get successRate() {
                if (!this.usage.totalRequests) return 100;
                return Math.round(((this.usage.totalRequests - this.usage.totalErrors) / this.usage.totalRequests) * 100);
            },

            get maxRequests() {
                return Math.max(...(this.usage.byDay || []).map(d => d.requests), 1);
            },

            formatBytes(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
            },

            async loadUsage() {
                try {
                    const [summaryRes, timeseriesRes, assetsRes] = await Promise.all([
                        window.apiFetch(`/api/usage/summary?days=${this.days}`),
                        window.apiFetch(`/api/usage/timeseries?days=${this.days}`),
                        window.apiFetch('/api/assets')
                    ]);

                    if (summaryRes.ok) {
                        const data = await summaryRes.json();
                        this.usage = data.summary || {};
                    }

                    if (timeseriesRes.ok) {
                        const data = await timeseriesRes.json();
                        const timeseries = data.timeseries || [];
                        this.usage.byDay = timeseries.map(d => ({ date: d.date, requests: d.requests }));
                        if (timeseries.length > 0) {
                            this.usage.period = {
                                start: timeseries[0].date,
                                end: timeseries[timeseries.length - 1].date
                            };
                        }
                    }

                    if (assetsRes.ok) {
                        const data = await assetsRes.json();
                        const assets = data.assets || [];
                        const byType = {};
                        let totalSize = 0;

                        for (const asset of assets) {
                            totalSize += asset.size || 0;
                            const type = (asset.mimeType || asset.mime_type || '').split('/')[0] || 'other';
                            if (!byType[type]) byType[type] = { count: 0, size: 0 };
                            byType[type].count++;
                            byType[type].size += asset.size || 0;
                        }

                        this.assetStats = { totalAssets: assets.length, totalSize, byType };
                    }
                } catch (e) {
                    console.error('Failed to load usage:', e);
                }
            }
        }
    }
</script>
@endsection
