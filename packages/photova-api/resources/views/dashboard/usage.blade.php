@extends('dashboard.layout')

@section('title', 'Usage')

@section('content')
<div x-data="usagePage()" x-init="loadUsage()">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Usage</h1>
        <select
            x-model="days"
            @change="loadUsage()"
            class="px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-[13px] outline-none focus:border-[#58a6ff] transition-colors"
        >
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>

    <!-- Top Row: Quota Ring + Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">
        <!-- Quota Ring -->
        <div class="lg:col-span-1 bg-gradient-to-br from-[#161b22] to-[#0d1117] rounded-xl border border-[#30363d] p-6 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-[#58a6ff]/5 to-[#a371f7]/5"></div>
            <div class="relative">
                <svg class="w-32 h-32 transform -rotate-90">
                    <!-- Background ring -->
                    <circle cx="64" cy="64" r="56" fill="none" stroke="#21262d" stroke-width="8"/>
                    <!-- Progress ring -->
                    <circle 
                        cx="64" cy="64" r="56" 
                        fill="none" 
                        stroke="url(#quotaGradient)" 
                        stroke-width="8"
                        stroke-linecap="round"
                        :stroke-dasharray="351.86"
                        :stroke-dashoffset="351.86 - (351.86 * Math.min(quotaPercent, 100) / 100)"
                        class="transition-all duration-1000 ease-out"
                    />
                    <defs>
                        <linearGradient id="quotaGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#58a6ff"/>
                            <stop offset="100%" stop-color="#a371f7"/>
                        </linearGradient>
                    </defs>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold text-[#c9d1d9]" x-text="quotaPercent + '%'"></span>
                    <span class="text-xs text-[#8b949e]">of quota</span>
                </div>
            </div>
            <div class="mt-4 text-center relative">
                <div class="text-sm text-[#c9d1d9]" x-text="(current.used || 0).toLocaleString() + ' / ' + (current.limit || 100).toLocaleString()"></div>
                <div class="text-xs text-[#8b949e]">requests this month</div>
            </div>
            <!-- Current Period Charges -->
            <div class="mt-4 pt-4 border-t border-[#30363d] w-full text-center relative">
                <div class="text-xs text-[#8b949e] mb-1">Current Charges</div>
                <div class="text-xl font-semibold text-[#c9d1d9]" x-text="'$' + (current.totalPrice || 0).toFixed(2)"></div>
                <div class="text-xs text-[#8b949e] mt-1">this billing period</div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="lg:col-span-3 grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden group hover:border-[#30363d]/80 transition-colors">
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#58a6ff]/10 to-transparent rounded-bl-full"></div>
                <div class="text-xs text-[#8b949e] mb-1">Total Requests</div>
                <div class="text-2xl font-semibold text-[#c9d1d9]" x-text="(usage.totalRequests || 0).toLocaleString()"></div>
                <div class="text-xs text-[#3fb950] mt-1" x-show="usage.totalRequests > 0">
                    <span x-text="'+' + usage.totalRequests"></span> this period
                </div>
            </div>
            <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#3fb950]/10 to-transparent rounded-bl-full"></div>
                <div class="text-xs text-[#8b949e] mb-1">Success Rate</div>
                <div class="text-2xl font-semibold" :class="successRate >= 95 ? 'text-[#3fb950]' : successRate >= 80 ? 'text-[#d29922]' : 'text-[#f85149]'" x-text="successRate + '%'"></div>
                <div class="w-full h-1.5 bg-[#21262d] rounded-full mt-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500" :class="successRate >= 95 ? 'bg-[#3fb950]' : successRate >= 80 ? 'bg-[#d29922]' : 'bg-[#f85149]'" :style="'width: ' + successRate + '%'"></div>
                </div>
            </div>
            <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#f85149]/10 to-transparent rounded-bl-full"></div>
                <div class="text-xs text-[#8b949e] mb-1">Errors</div>
                <div class="text-2xl font-semibold" :class="usage.totalErrors > 0 ? 'text-[#f85149]' : 'text-[#8b949e]'" x-text="usage.totalErrors || 0"></div>
                <div class="text-xs text-[#8b949e] mt-1" x-text="errorRate + '% error rate'"></div>
            </div>
            <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#a371f7]/10 to-transparent rounded-bl-full"></div>
                <div class="text-xs text-[#8b949e] mb-1">Avg Latency</div>
                <div class="text-2xl font-semibold text-[#c9d1d9]" x-text="formatLatency(usage.averageLatencyMs)"></div>
                <div class="text-xs mt-1" :class="(usage.averageLatencyMs || 0) < 1000 ? 'text-[#3fb950]' : 'text-[#d29922]'" x-text="(usage.averageLatencyMs || 0) < 1000 ? 'Fast' : 'Moderate'"></div>
            </div>
            <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#58a6ff]/10 to-transparent rounded-bl-full"></div>
                <div class="text-xs text-[#8b949e] mb-1">Files Stored</div>
                <div class="text-2xl font-semibold text-[#c9d1d9]" x-text="assetStats.totalAssets || 0"></div>
                <div class="text-xs text-[#8b949e] mt-1" x-text="formatBytes(assetStats.totalSize || 0)"></div>
            </div>
            <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#d29922]/10 to-transparent rounded-bl-full"></div>
                <div class="text-xs text-[#8b949e] mb-1">Operations</div>
                <div class="text-2xl font-semibold text-[#c9d1d9]" x-text="Object.keys(usage.byOperation || {}).length"></div>
                <div class="text-xs text-[#8b949e] mt-1">unique types</div>
            </div>
        </div>
    </div>

    <!-- Main Chart -->
    <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-6 mb-4 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-[#58a6ff]/[0.02] to-transparent pointer-events-none"></div>
        <div class="flex justify-between items-center mb-4 relative">
            <h2 class="text-sm font-medium text-[#c9d1d9]">Requests over time</h2>
            <div class="flex gap-4 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#58a6ff]"></span>
                    <span class="text-[#8b949e]">Requests</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#f85149]"></span>
                    <span class="text-[#8b949e]">Errors</span>
                </span>
            </div>
        </div>
        <div class="relative h-64">
            <canvas x-ref="mainChart"></canvas>
        </div>
    </div>

    <!-- Operation Spark Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <template x-for="(stats, op) in usage.byOperation || {}" :key="op">
            <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-4 hover:border-[#58a6ff]/50 transition-colors cursor-default group">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm" :class="opColors[op]?.bg || 'bg-[#58a6ff]/20'" x-text="opIcons[op] || '?'"></div>
                    <div class="text-xs text-[#8b949e] truncate" x-text="opNames[op] || op"></div>
                </div>
                <div class="text-xl font-semibold text-[#c9d1d9] mb-1" x-text="stats.requests.toLocaleString()"></div>
                <!-- Mini sparkline -->
                <div class="h-8 flex items-end gap-px">
                    <template x-for="(val, i) in getOpSparkline(op)" :key="i">
                        <div 
                            class="flex-1 rounded-t transition-all duration-300"
                            :class="opColors[op]?.bar || 'bg-[#58a6ff]'"
                            :style="'height: ' + val + '%'"
                        ></div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Bottom Row: Table + Storage -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Operations Table -->
        <div class="lg:col-span-2 bg-[#161b22] rounded-xl border border-[#30363d] overflow-hidden">
            <div class="p-4 border-b border-[#21262d]">
                <h2 class="text-sm font-medium text-[#c9d1d9]">Operation breakdown</h2>
            </div>
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#21262d] bg-[#0d1117]/50">
                        <th class="text-left p-3 text-xs text-[#8b949e] font-medium">Operation</th>
                        <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Requests</th>
                        <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Errors</th>
                        <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Latency</th>
                        <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Success</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(stats, op) in usage.byOperation || {}" :key="op">
                        <tr class="border-b border-[#21262d] hover:bg-[#21262d]/30 transition-colors">
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm" x-text="opIcons[op] || '?'"></span>
                                    <span class="text-[13px] text-[#c9d1d9]" x-text="opNames[op] || op"></span>
                                </div>
                            </td>
                            <td class="text-right p-3 text-[13px] text-[#c9d1d9] font-medium" x-text="stats.requests.toLocaleString()"></td>
                            <td class="text-right p-3 text-[13px]" :class="stats.errors > 0 ? 'text-[#f85149]' : 'text-[#8b949e]'" x-text="stats.errors"></td>
                            <td class="text-right p-3 text-[13px] text-[#8b949e]" x-text="formatLatency(stats.avgLatencyMs)"></td>
                            <td class="text-right p-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium" 
                                    :class="getSuccessRate(stats) >= 95 ? 'bg-[#3fb950]/20 text-[#3fb950]' : getSuccessRate(stats) >= 80 ? 'bg-[#d29922]/20 text-[#d29922]' : 'bg-[#f85149]/20 text-[#f85149]'"
                                    x-text="getSuccessRate(stats) + '%'">
                                </span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <template x-if="Object.keys(usage.byOperation || {}).length === 0">
                <div class="p-8 text-center text-[#8b949e] text-sm">No operations yet</div>
            </template>
        </div>

        <!-- Storage Breakdown -->
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5">
            <h2 class="text-sm font-medium text-[#c9d1d9] mb-4">Storage breakdown</h2>
            <template x-if="Object.keys(assetStats.byType || {}).length === 0">
                <div class="text-center text-[#8b949e] text-sm py-8">No files stored</div>
            </template>
            <template x-for="(stats, type) in assetStats.byType || {}" :key="type">
                <div class="mb-4">
                    <div class="flex justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" :class="storageColors[type] || 'bg-[#8b949e]'"></span>
                            <span class="text-sm text-[#c9d1d9] capitalize" x-text="type"></span>
                        </div>
                        <span class="text-sm text-[#8b949e]" x-text="formatBytes(stats.size)"></span>
                    </div>
                    <div class="bg-[#21262d] rounded-full h-2 overflow-hidden">
                        <div 
                            class="h-full rounded-full transition-all duration-500"
                            :class="storageColors[type] || 'bg-[#8b949e]'"
                            :style="'width: ' + (assetStats.totalSize > 0 ? (stats.size / assetStats.totalSize) * 100 : 0) + '%'"
                        ></div>
                    </div>
                    <div class="text-xs text-[#6e7681] mt-1" x-text="stats.count + ' file' + (stats.count !== 1 ? 's' : '')"></div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    function usagePage() {
        return {
            days: 30,
            usage: {},
            current: { used: 0, limit: 100 },
            assetStats: { totalAssets: 0, totalSize: 0, byType: {} },
            chart: null,
            timeseries: [],

            opColors: {
                'background-remove': { bg: 'bg-[#58a6ff]/20', bar: 'bg-[#58a6ff]' },
                'upscale': { bg: 'bg-[#a371f7]/20', bar: 'bg-[#a371f7]' },
                'restore': { bg: 'bg-[#f78166]/20', bar: 'bg-[#f78166]' },
                'colorize': { bg: 'bg-[#3fb950]/20', bar: 'bg-[#3fb950]' },
                'unblur': { bg: 'bg-[#d29922]/20', bar: 'bg-[#d29922]' },
                'inpaint': { bg: 'bg-[#db61a2]/20', bar: 'bg-[#db61a2]' },
            },
            opIcons: {
                'background-remove': '✂️',
                'upscale': '🔍',
                'restore': '🖼️',
                'colorize': '🎨',
                'unblur': '✨',
                'inpaint': '🧹',
            },
            opNames: {
                'background-remove': 'Bg Remove',
                'upscale': 'Upscale',
                'restore': 'Restore',
                'colorize': 'Colorize',
                'unblur': 'Unblur',
                'inpaint': 'Inpaint',
            },
            storageColors: {
                'image': 'bg-[#58a6ff]',
                'video': 'bg-[#a371f7]',
                'audio': 'bg-[#3fb950]',
                'other': 'bg-[#8b949e]',
            },

            get successRate() {
                if (!this.usage.totalRequests) return 100;
                return Math.round(((this.usage.totalRequests - (this.usage.totalErrors || 0)) / this.usage.totalRequests) * 100);
            },

            get errorRate() {
                if (!this.usage.totalRequests) return 0;
                return Math.round(((this.usage.totalErrors || 0) / this.usage.totalRequests) * 100);
            },

            get quotaPercent() {
                if (!this.current.limit) return 0;
                return Math.min(Math.round((this.current.used / this.current.limit) * 100), 100);
            },

            getSuccessRate(stats) {
                if (!stats.requests) return 100;
                return Math.round(((stats.requests - (stats.errors || 0)) / stats.requests) * 100);
            },

            getOpSparkline(op) {
                const data = this.timeseries.map(d => d.byOp?.[op] || 0);
                const max = Math.max(...data, 1);
                return data.slice(-7).map(v => Math.max((v / max) * 100, v > 0 ? 10 : 0));
            },

            formatBytes(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
            },

            formatLatency(ms) {
                if (!ms) return '—';
                if (ms < 1000) return Math.round(ms) + 'ms';
                return (ms / 1000).toFixed(1) + 's';
            },

            async loadUsage() {
                try {
                    const [summaryRes, timeseriesRes, currentRes, assetsRes] = await Promise.all([
                        window.apiFetch(`/api/usage/summary?days=${this.days}`),
                        window.apiFetch(`/api/usage/timeseries?days=${this.days}`),
                        window.apiFetch('/api/usage/current'),
                        window.apiFetch('/api/assets')
                    ]);

                    if (summaryRes.ok) {
                        const data = await summaryRes.json();
                        this.usage = data.summary || {};
                    }

                    if (timeseriesRes.ok) {
                        const data = await timeseriesRes.json();
                        this.timeseries = data.timeseries || [];
                        this.renderChart();
                    }

                    if (currentRes.ok) {
                        const data = await currentRes.json();
                        this.current = data.current || { used: 0, limit: 100 };
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
            },

            renderChart() {
                const ctx = this.$refs.mainChart;
                if (!ctx) return;

                if (this.chart) {
                    this.chart.destroy();
                }

                const labels = this.timeseries.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                const requests = this.timeseries.map(d => d.requests || 0);
                const errors = this.timeseries.map(d => d.errors || 0);

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Requests',
                                data: requests,
                                borderColor: '#58a6ff',
                                backgroundColor: (context) => {
                                    const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 250);
                                    gradient.addColorStop(0, 'rgba(88, 166, 255, 0.3)');
                                    gradient.addColorStop(1, 'rgba(88, 166, 255, 0)');
                                    return gradient;
                                },
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#58a6ff',
                                pointHoverBorderColor: '#0d1117',
                                pointHoverBorderWidth: 2,
                            },
                            {
                                label: 'Errors',
                                data: errors,
                                borderColor: '#f85149',
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                borderDash: [4, 4],
                                tension: 0.4,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#f85149',
                                pointHoverBorderColor: '#0d1117',
                                pointHoverBorderWidth: 2,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#161b22',
                                borderColor: '#30363d',
                                borderWidth: 1,
                                titleColor: '#c9d1d9',
                                bodyColor: '#8b949e',
                                padding: 12,
                                displayColors: true,
                                boxPadding: 4,
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: '#21262d', drawBorder: false },
                                ticks: { color: '#6e7681', font: { size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#21262d', drawBorder: false },
                                ticks: { color: '#6e7681', font: { size: 11 } }
                            }
                        }
                    }
                });
            }
        }
    }
</script>
@endsection
