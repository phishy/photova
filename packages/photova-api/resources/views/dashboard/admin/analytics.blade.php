@extends('dashboard.layout')

@section('title', 'Admin Analytics')

@section('content')
<div x-data="adminAnalytics()" x-init="loadAnalytics()">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Analytics</h1>
        <select
            x-model="days"
            @change="loadAnalytics()"
            class="px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-[13px] outline-none focus:border-[#58a6ff] transition-colors"
        >
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
        </select>
    </div>

    <!-- Financial Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#f85149]/10 to-transparent rounded-bl-full"></div>
            <div class="text-xs text-[#8b949e] mb-1">Total Cost</div>
            <div class="text-2xl font-semibold text-[#f85149]" x-text="'$' + (usage.totalCost || 0).toFixed(2)"></div>
            <div class="text-xs text-[#8b949e] mt-1">provider spend</div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#3fb950]/10 to-transparent rounded-bl-full"></div>
            <div class="text-xs text-[#8b949e] mb-1">Total Revenue</div>
            <div class="text-2xl font-semibold text-[#3fb950]" x-text="'$' + (usage.totalRevenue || 0).toFixed(2)"></div>
            <div class="text-xs text-[#8b949e] mt-1">customer billing</div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#a371f7]/10 to-transparent rounded-bl-full"></div>
            <div class="text-xs text-[#8b949e] mb-1">Gross Margin</div>
            <div class="text-2xl font-semibold" :class="(usage.totalMargin || 0) >= 0 ? 'text-[#a371f7]' : 'text-[#f85149]'" x-text="'$' + (usage.totalMargin || 0).toFixed(2)"></div>
            <div class="text-xs text-[#8b949e] mt-1" x-text="marginPercent + '% margin'"></div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#58a6ff]/10 to-transparent rounded-bl-full"></div>
            <div class="text-xs text-[#8b949e] mb-1">Total Requests</div>
            <div class="text-2xl font-semibold text-[#c9d1d9]" x-text="(usage.totalRequests || 0).toLocaleString()"></div>
            <div class="text-xs text-[#8b949e] mt-1">API calls</div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-6 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-[#3fb950]/[0.02] to-transparent pointer-events-none"></div>
        <div class="flex justify-between items-center mb-4 relative">
            <h2 class="text-sm font-medium text-[#c9d1d9]">Revenue vs Cost over time</h2>
            <div class="flex gap-4 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#3fb950]"></span>
                    <span class="text-[#8b949e]">Revenue</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#f85149]"></span>
                    <span class="text-[#8b949e]">Cost</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#a371f7]"></span>
                    <span class="text-[#8b949e]">Margin</span>
                </span>
            </div>
        </div>
        <div class="relative h-64">
            <canvas x-ref="revenueChart"></canvas>
        </div>
    </div>

    <!-- Platform Stats Row -->
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-4 mb-6">
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-4 relative overflow-hidden">
            <div class="text-xs text-[#8b949e] mb-1">Users</div>
            <div class="text-xl font-semibold text-[#c9d1d9]" x-text="(platform.users?.total || 0).toLocaleString()"></div>
            <div class="text-xs text-[#3fb950] mt-1" x-show="platform.users?.new > 0">+<span x-text="platform.users?.new"></span> new</div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-4 relative overflow-hidden">
            <div class="text-xs text-[#8b949e] mb-1">Assets</div>
            <div class="text-xl font-semibold text-[#c9d1d9]" x-text="(platform.assets?.total || 0).toLocaleString()"></div>
            <div class="text-xs text-[#8b949e] mt-1" x-text="formatBytes(platform.assets?.totalSize || 0)"></div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-4 relative overflow-hidden">
            <div class="text-xs text-[#8b949e] mb-1">Shares</div>
            <div class="text-xl font-semibold text-[#c9d1d9]" x-text="(platform.shares?.total || 0).toLocaleString()"></div>
            <div class="text-xs text-[#3fb950] mt-1"><span x-text="platform.shares?.active || 0"></span> active</div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-4 relative overflow-hidden">
            <div class="text-xs text-[#8b949e] mb-1">Share Views</div>
            <div class="text-xl font-semibold text-[#58a6ff]" x-text="(platform.analytics?.shareViews || 0).toLocaleString()"></div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-4 relative overflow-hidden">
            <div class="text-xs text-[#8b949e] mb-1">Downloads</div>
            <div class="text-xl font-semibold text-[#3fb950]" x-text="(platform.analytics?.shareDownloads || 0).toLocaleString()"></div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-4 relative overflow-hidden">
            <div class="text-xs text-[#8b949e] mb-1">Asset Views</div>
            <div class="text-xl font-semibold text-[#a371f7]" x-text="(platform.analytics?.assetViews || 0).toLocaleString()"></div>
        </div>
    </div>

    <!-- Operation Profitability Table -->
    <div class="bg-[#161b22] rounded-xl border border-[#30363d] overflow-hidden">
        <div class="p-4 border-b border-[#21262d]">
            <h2 class="text-sm font-medium text-[#c9d1d9]">Profitability by Operation</h2>
        </div>
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#21262d] bg-[#0d1117]/50">
                    <th class="text-left p-3 text-xs text-[#8b949e] font-medium">Operation</th>
                    <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Requests</th>
                    <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Cost</th>
                    <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Revenue</th>
                    <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Margin</th>
                    <th class="text-right p-3 text-xs text-[#8b949e] font-medium">Margin %</th>
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
                        <td class="text-right p-3 text-[13px] text-[#f85149]" x-text="'$' + (stats.cost || 0).toFixed(2)"></td>
                        <td class="text-right p-3 text-[13px] text-[#3fb950]" x-text="'$' + (stats.revenue || 0).toFixed(2)"></td>
                        <td class="text-right p-3 text-[13px]" :class="(stats.revenue - stats.cost) >= 0 ? 'text-[#a371f7]' : 'text-[#f85149]'" x-text="'$' + ((stats.revenue || 0) - (stats.cost || 0)).toFixed(2)"></td>
                        <td class="text-right p-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" 
                                :class="getMarginPercent(stats) >= 70 ? 'bg-[#3fb950]/20 text-[#3fb950]' : getMarginPercent(stats) >= 50 ? 'bg-[#d29922]/20 text-[#d29922]' : 'bg-[#f85149]/20 text-[#f85149]'"
                                x-text="getMarginPercent(stats) + '%'">
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

    <!-- Top Shares & Assets -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Top Shares -->
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] overflow-hidden">
            <div class="p-4 border-b border-[#21262d]">
                <h2 class="text-sm font-medium text-[#c9d1d9]">Top Shares by Views</h2>
            </div>
            <div class="divide-y divide-[#21262d]">
                <template x-for="share in topShares.slice(0, 5)" :key="share.id">
                    <div class="p-3 flex items-center justify-between">
                        <div>
                            <div class="text-[13px] text-[#c9d1d9]" x-text="share.name || share.slug"></div>
                            <div class="text-xs text-[#8b949e]" x-text="share.user?.name || 'Unknown'"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-[#58a6ff]" x-text="share.viewCount.toLocaleString()"></div>
                            <div class="text-xs text-[#8b949e]">views</div>
                        </div>
                    </div>
                </template>
                <template x-if="topShares.length === 0">
                    <div class="p-6 text-center text-[#8b949e] text-sm">No shares yet</div>
                </template>
            </div>
        </div>

        <!-- Top Assets -->
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] overflow-hidden">
            <div class="p-4 border-b border-[#21262d]">
                <h2 class="text-sm font-medium text-[#c9d1d9]">Top Assets by Views</h2>
            </div>
            <div class="divide-y divide-[#21262d]">
                <template x-for="asset in topAssets.slice(0, 5)" :key="asset.id">
                    <div class="p-3 flex items-center justify-between">
                        <div>
                            <div class="text-[13px] text-[#c9d1d9] truncate max-w-[200px]" x-text="asset.filename"></div>
                            <div class="text-xs text-[#8b949e]" x-text="asset.user?.name || 'Unknown'"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-[#a371f7]" x-text="asset.viewCount.toLocaleString()"></div>
                            <div class="text-xs text-[#8b949e]">views</div>
                        </div>
                    </div>
                </template>
                <template x-if="topAssets.length === 0">
                    <div class="p-6 text-center text-[#8b949e] text-sm">No assets yet</div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    function adminAnalytics() {
        return {
            days: 30,
            usage: {},
            timeseries: [],
            platform: {},
            topShares: [],
            topAssets: [],
            chart: null,

            opIcons: {
                'background-remove': '✂️',
                'upscale': '🔍',
                'restore': '🖼️',
                'colorize': '🎨',
                'unblur': '✨',
                'inpaint': '🧹',
                'analyze': '🔬',
            },
            opNames: {
                'background-remove': 'Background Remove',
                'upscale': 'Upscale',
                'restore': 'Restore',
                'colorize': 'Colorize',
                'unblur': 'Unblur',
                'inpaint': 'Inpaint',
                'analyze': 'Analyze',
            },

            get marginPercent() {
                if (!this.usage.totalRevenue) return 0;
                return Math.round(((this.usage.totalMargin || 0) / this.usage.totalRevenue) * 100);
            },

            getMarginPercent(stats) {
                if (!stats.revenue) return 0;
                return Math.round(((stats.revenue - (stats.cost || 0)) / stats.revenue) * 100);
            },

            formatBytes(bytes) {
                if (!bytes) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            },

            async loadAnalytics() {
                try {
                    const [summaryRes, timeseriesRes, platformRes, topSharesRes, topAssetsRes] = await Promise.all([
                        window.apiFetch(`/api/usage/summary?days=${this.days}`),
                        window.apiFetch(`/api/usage/timeseries?days=${this.days}`),
                        window.apiFetch(`/api/admin/dashboard?days=${this.days}`),
                        window.apiFetch('/api/admin/top-shares'),
                        window.apiFetch('/api/admin/top-assets')
                    ]);

                    this.usage = summaryRes.summary || {};
                    this.timeseries = timeseriesRes.timeseries || [];
                    this.platform = platformRes || {};
                    this.topShares = topSharesRes.shares || [];
                    this.topAssets = topAssetsRes.byViews || [];
                    
                    this.renderChart();
                } catch (e) {
                    console.error('Failed to load analytics:', e);
                }
            },

            renderChart() {
                const ctx = this.$refs.revenueChart;
                if (!ctx) return;

                if (this.chart) {
                    this.chart.destroy();
                }

                const labels = this.timeseries.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });
                const revenue = this.timeseries.map(d => d.revenue || 0);
                const cost = this.timeseries.map(d => d.cost || 0);
                const margin = this.timeseries.map(d => (d.revenue || 0) - (d.cost || 0));

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: revenue,
                                borderColor: '#3fb950',
                                backgroundColor: (context) => {
                                    const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 250);
                                    gradient.addColorStop(0, 'rgba(63, 185, 80, 0.2)');
                                    gradient.addColorStop(1, 'rgba(63, 185, 80, 0)');
                                    return gradient;
                                },
                                fill: true,
                                tension: 0.4,
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#3fb950',
                                pointHoverBorderColor: '#0d1117',
                                pointHoverBorderWidth: 2,
                            },
                            {
                                label: 'Cost',
                                data: cost,
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
                            },
                            {
                                label: 'Margin',
                                data: margin,
                                borderColor: '#a371f7',
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                tension: 0.4,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                pointHoverBackgroundColor: '#a371f7',
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
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': $' + context.raw.toFixed(2);
                                    }
                                }
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
                                ticks: { 
                                    color: '#6e7681', 
                                    font: { size: 11 },
                                    callback: function(value) {
                                        return '$' + value.toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    }
</script>
@endsection
