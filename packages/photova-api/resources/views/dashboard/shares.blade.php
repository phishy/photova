@extends('dashboard.layout')

@section('title', 'Shares')

@section('content')
<div x-data="sharesPage()" x-init="loadShares()">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Shares</h1>
        <select
            x-model="days"
            @change="loadShares()"
            class="px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-[13px] outline-none focus:border-[#58a6ff] transition-colors"
        >
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
            <option value="365">Last year</option>
        </select>
    </div>

    <!-- Aggregate Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#58a6ff]/10 to-transparent rounded-bl-full"></div>
            <div class="text-xs text-[#8b949e] mb-1">Total Shares</div>
            <div class="text-2xl font-semibold text-[#c9d1d9]" x-text="stats.totalShares || 0"></div>
            <div class="text-xs text-[#8b949e] mt-1"><span class="text-[#3fb950]" x-text="stats.activeShares || 0"></span> active</div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#3fb950]/10 to-transparent rounded-bl-full"></div>
            <div class="text-xs text-[#8b949e] mb-1">Total Views</div>
            <div class="text-2xl font-semibold text-[#3fb950]" x-text="(stats.totalViews || 0).toLocaleString()"></div>
            <div class="text-xs text-[#8b949e] mt-1">across all shares</div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#a371f7]/10 to-transparent rounded-bl-full"></div>
            <div class="text-xs text-[#8b949e] mb-1">Downloads</div>
            <div class="text-2xl font-semibold text-[#a371f7]" x-text="(stats.totalDownloads || 0).toLocaleString()"></div>
            <div class="text-xs text-[#8b949e] mt-1">files downloaded</div>
        </div>
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-[#d29922]/10 to-transparent rounded-bl-full"></div>
            <div class="text-xs text-[#8b949e] mb-1">ZIP Downloads</div>
            <div class="text-2xl font-semibold text-[#d29922]" x-text="(stats.totalZipDownloads || 0).toLocaleString()"></div>
            <div class="text-xs text-[#8b949e] mt-1">bulk downloads</div>
        </div>
    </div>

    <!-- Views Chart -->
    <div class="bg-[#161b22] rounded-xl border border-[#30363d] p-6 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-[#58a6ff]/[0.02] to-transparent pointer-events-none"></div>
        <div class="flex justify-between items-center mb-4 relative">
            <h2 class="text-sm font-medium text-[#c9d1d9]">Share Activity</h2>
            <div class="flex gap-4 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#58a6ff]"></span>
                    <span class="text-[#8b949e]">Views</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#3fb950]"></span>
                    <span class="text-[#8b949e]">Downloads</span>
                </span>
            </div>
        </div>
        <div class="relative h-48">
            <canvas x-ref="sharesChart"></canvas>
        </div>
    </div>

    <!-- Shares List -->
    <div class="bg-[#161b22] rounded-xl border border-[#30363d] overflow-hidden">
        <div class="p-4 border-b border-[#21262d] flex justify-between items-center">
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer" x-show="filteredShares.length > 0">
                    <input 
                        type="checkbox" 
                        :checked="selectedIds.length === filteredShares.length && filteredShares.length > 0"
                        @change="toggleSelectAll"
                        class="w-4 h-4 rounded border-[#30363d] bg-[#0d1117] text-[#58a6ff] focus:ring-[#58a6ff] focus:ring-offset-0"
                    >
                    <span class="text-xs text-[#8b949e]" x-show="selectedIds.length > 0" x-text="selectedIds.length + ' selected'"></span>
                </label>
                <h2 class="text-sm font-medium text-[#c9d1d9]" x-show="selectedIds.length === 0">Your Shares</h2>
                <button 
                    x-show="selectedIds.length > 0"
                    @click="bulkDelete"
                    class="px-3 py-1.5 bg-[#f85149]/10 hover:bg-[#f85149]/20 text-[#f85149] text-xs rounded-md transition-colors flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete selected
                </button>
            </div>
            <div class="flex gap-2">
                <button 
                    @click="filter = 'all'; selectedIds = []" 
                    :class="filter === 'all' ? 'bg-[#21262d] text-[#c9d1d9]' : 'text-[#8b949e] hover:text-[#c9d1d9]'"
                    class="px-3 py-1 text-xs rounded-md transition-colors"
                >All</button>
                <button 
                    @click="filter = 'active'; selectedIds = []" 
                    :class="filter === 'active' ? 'bg-[#21262d] text-[#c9d1d9]' : 'text-[#8b949e] hover:text-[#c9d1d9]'"
                    class="px-3 py-1 text-xs rounded-md transition-colors"
                >Active</button>
                <button 
                    @click="filter = 'expired'; selectedIds = []" 
                    :class="filter === 'expired' ? 'bg-[#21262d] text-[#c9d1d9]' : 'text-[#8b949e] hover:text-[#c9d1d9]'"
                    class="px-3 py-1 text-xs rounded-md transition-colors"
                >Expired</button>
            </div>
        </div>
        
        <div class="divide-y divide-[#21262d]">
            <template x-for="share in filteredShares" :key="share.id">
                <div class="p-4 hover:bg-[#21262d]/30 transition-colors cursor-pointer" :class="selectedIds.includes(share.id) ? 'bg-[#21262d]/40' : ''" @click="selectedShare = share; showAnalyticsModal = true">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input 
                                type="checkbox" 
                                :checked="selectedIds.includes(share.id)"
                                @click.stop="toggleSelect(share.id)"
                                class="w-4 h-4 rounded border-[#30363d] bg-[#0d1117] text-[#58a6ff] focus:ring-[#58a6ff] focus:ring-offset-0"
                            >
                            <div class="w-10 h-10 rounded-lg bg-[#21262d] flex items-center justify-center text-lg">
                                <span x-text="share.assetCount > 1 ? '📁' : '🖼️'"></span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[13px] font-medium text-[#c9d1d9]" x-text="share.name || 'Untitled Share'"></span>
                                    <span 
                                        class="px-1.5 py-0.5 text-[10px] rounded-full font-medium"
                                        :class="share.isExpired ? 'bg-[#f85149]/20 text-[#f85149]' : 'bg-[#3fb950]/20 text-[#3fb950]'"
                                        x-text="share.isExpired ? 'Expired' : 'Active'"
                                    ></span>
                                    <span x-show="share.hasPassword" class="text-[#8b949e]" title="Password protected">🔒</span>
                                </div>
                                <div class="text-xs text-[#8b949e] mt-0.5">
                                    <span x-text="share.assetCount"></span> file<span x-show="share.assetCount !== 1">s</span>
                                    <span class="mx-1">·</span>
                                    <span x-text="formatDate(share.created)"></span>
                                    <template x-if="share.expiresAt">
                                        <span>
                                            <span class="mx-1">·</span>
                                            <span :class="share.isExpired ? 'text-[#f85149]' : ''" x-text="share.isExpired ? 'Expired ' + formatDate(share.expiresAt) : 'Expires ' + formatDate(share.expiresAt)"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 text-right">
                            <div>
                                <div class="text-sm font-medium text-[#c9d1d9]" x-text="share.viewCount.toLocaleString()"></div>
                                <div class="text-[10px] text-[#8b949e]">views</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-[#c9d1d9]" x-text="(share.analytics?.downloads || 0).toLocaleString()"></div>
                                <div class="text-[10px] text-[#8b949e]">downloads</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button 
                                    @click.stop="copyShareLink(share)" 
                                    class="p-2 rounded-md hover:bg-[#21262d] text-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                                    title="Copy link"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                                <button 
                                    @click.stop="deleteShare(share)" 
                                    class="p-2 rounded-md hover:bg-[#f85149]/10 text-[#8b949e] hover:text-[#f85149] transition-colors"
                                    title="Delete share"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        
        <template x-if="filteredShares.length === 0">
            <div class="p-12 text-center">
                <div class="text-4xl mb-3">🔗</div>
                <div class="text-[#c9d1d9] font-medium mb-1" x-text="filter === 'all' ? 'No shares yet' : 'No ' + filter + ' shares'"></div>
                <div class="text-sm text-[#8b949e]">Share files from the Assets page to see them here</div>
            </div>
        </template>
    </div>

    <!-- Share Analytics Modal -->
    <template x-if="showAnalyticsModal && selectedShare">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showAnalyticsModal = false" @keydown.escape.window="showAnalyticsModal = false">
            <div class="bg-[#161b22] rounded-xl border border-[#30363d] w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
                <div class="p-4 border-b border-[#21262d] flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-[#c9d1d9]" x-text="selectedShare.name || 'Share Analytics'"></h3>
                        <div class="text-xs text-[#8b949e] mt-0.5" x-text="selectedShare.assetCount + ' files · Created ' + formatDate(selectedShare.created)"></div>
                    </div>
                    <button @click="showAnalyticsModal = false" class="text-[#8b949e] hover:text-[#c9d1d9]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <div class="p-4 overflow-y-auto flex-1">
                    <!-- Share link -->
                    <div class="flex gap-2 mb-4">
                        <input
                            type="text"
                            :value="selectedShare.url"
                            readonly
                            class="flex-1 px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm"
                        >
                        <button
                            @click="copyShareLink(selectedShare)"
                            class="px-4 py-2 bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm transition-colors"
                        >Copy</button>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-[#0d1117] rounded-lg p-4 text-center">
                            <div class="text-2xl font-semibold text-[#58a6ff]" x-text="selectedShare.viewCount.toLocaleString()"></div>
                            <div class="text-xs text-[#8b949e] mt-1">Views</div>
                        </div>
                        <div class="bg-[#0d1117] rounded-lg p-4 text-center">
                            <div class="text-2xl font-semibold text-[#3fb950]" x-text="(selectedShare.analytics?.downloads || 0).toLocaleString()"></div>
                            <div class="text-xs text-[#8b949e] mt-1">Downloads</div>
                        </div>
                        <div class="bg-[#0d1117] rounded-lg p-4 text-center">
                            <div class="text-2xl font-semibold text-[#d29922]" x-text="(selectedShare.analytics?.zipDownloads || 0).toLocaleString()"></div>
                            <div class="text-xs text-[#8b949e] mt-1">ZIP Downloads</div>
                        </div>
                    </div>
                    
                    <!-- Detailed analytics chart -->
                    <div class="bg-[#0d1117] rounded-lg p-4 mb-4">
                        <h4 class="text-sm font-medium text-[#c9d1d9] mb-3">Activity over time</h4>
                        <div class="h-40 relative">
                            <canvas x-ref="shareDetailChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Recent events -->
                    <div>
                        <h4 class="text-sm font-medium text-[#c9d1d9] mb-3">Recent Activity</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            <template x-for="event in shareAnalytics.events?.slice(0, 20) || []" :key="event.id">
                                <div class="flex items-center justify-between text-xs bg-[#0d1117] rounded-md px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <span x-text="event.eventType === 'view' ? '👁️' : event.eventType === 'download' ? '⬇️' : '📦'"></span>
                                        <span class="text-[#c9d1d9] capitalize" x-text="event.eventType.replace('_', ' ')"></span>
                                    </div>
                                    <div class="text-[#8b949e]" x-text="formatDateTime(event.timestamp)"></div>
                                </div>
                            </template>
                            <template x-if="!shareAnalytics.events?.length">
                                <div class="text-center text-[#8b949e] py-4">No activity yet</div>
                            </template>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 border-t border-[#21262d] flex justify-between">
                    <button
                        @click="deleteShare(selectedShare); showAnalyticsModal = false"
                        class="px-4 py-2 text-[#f85149] hover:bg-[#f85149]/10 rounded-md text-sm transition-colors"
                    >Delete Share</button>
                    <button
                        @click="showAnalyticsModal = false"
                        class="px-4 py-2 bg-[#21262d] hover:bg-[#30363d] text-[#c9d1d9] rounded-md text-sm transition-colors"
                    >Close</button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    function sharesPage() {
        return {
            days: 30,
            shares: [],
            stats: {},
            timeseries: [],
            filter: 'all',
            selectedShare: null,
            showAnalyticsModal: false,
            shareAnalytics: {},
            chart: null,
            detailChart: null,
            selectedIds: [],

            get filteredShares() {
                if (this.filter === 'all') return this.shares;
                if (this.filter === 'active') return this.shares.filter(s => !s.isExpired);
                if (this.filter === 'expired') return this.shares.filter(s => s.isExpired);
                return this.shares;
            },

            async loadShares() {
                try {
                    const [sharesRes, analyticsRes] = await Promise.all([
                        window.apiFetch('/api/shares'),
                        window.apiFetch(`/api/assets/analytics?days=${this.days}`)
                    ]);

                    const sharesData = await sharesRes.json();
                    const analyticsData = await analyticsRes.json();

                    this.shares = sharesData.shares || [];
                    
                    // Calculate aggregate stats
                    let totalViews = 0, totalDownloads = 0, totalZipDownloads = 0;
                    this.shares.forEach(s => {
                        totalViews += s.viewCount || 0;
                        totalDownloads += s.analytics?.downloads || 0;
                        totalZipDownloads += s.analytics?.zipDownloads || 0;
                    });

                    this.stats = {
                        totalShares: this.shares.length,
                        activeShares: this.shares.filter(s => !s.isExpired).length,
                        totalViews,
                        totalDownloads,
                        totalZipDownloads
                    };

                    this.timeseries = analyticsData.timeseries || [];
                    this.renderChart();
                } catch (e) {
                    console.error('Failed to load shares:', e);
                }
            },

            async loadShareAnalytics(share) {
                try {
                    const res = await window.apiFetch(`/api/shares/${share.id}/analytics?days=${this.days}`);
                    this.shareAnalytics = await res.json();
                    this.$nextTick(() => this.renderDetailChart());
                } catch (e) {
                    console.error('Failed to load share analytics:', e);
                }
            },

            renderChart() {
                const ctx = this.$refs.sharesChart;
                if (!ctx) return;

                if (this.chart) this.chart.destroy();

                const labels = this.timeseries.map(t => t.date);
                const views = this.timeseries.map(t => t.views || 0);
                const downloads = this.timeseries.map(t => t.downloads || 0);

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Views',
                                data: views,
                                borderColor: '#58a6ff',
                                backgroundColor: 'rgba(88, 166, 255, 0.1)',
                                fill: true,
                                tension: 0.3,
                            },
                            {
                                label: 'Downloads',
                                data: downloads,
                                borderColor: '#3fb950',
                                backgroundColor: 'rgba(63, 185, 80, 0.1)',
                                fill: true,
                                tension: 0.3,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                grid: { color: '#21262d' },
                                ticks: { color: '#8b949e', font: { size: 10 } }
                            },
                            y: {
                                grid: { color: '#21262d' },
                                ticks: { color: '#8b949e', font: { size: 10 } },
                                beginAtZero: true
                            }
                        }
                    }
                });
            },

            renderDetailChart() {
                const ctx = this.$refs.shareDetailChart;
                if (!ctx || !this.shareAnalytics.timeseries) return;

                if (this.detailChart) this.detailChart.destroy();

                const labels = this.shareAnalytics.timeseries.map(t => t.date);
                const views = this.shareAnalytics.timeseries.map(t => t.views || 0);
                const downloads = this.shareAnalytics.timeseries.map(t => t.downloads || 0);

                this.detailChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            { label: 'Views', data: views, backgroundColor: '#58a6ff' },
                            { label: 'Downloads', data: downloads, backgroundColor: '#3fb950' }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: '#8b949e', font: { size: 10 } }, stacked: true },
                            y: { grid: { color: '#21262d' }, ticks: { color: '#8b949e', font: { size: 10 } }, stacked: true, beginAtZero: true }
                        }
                    }
                });
            },

            async copyShareLink(share) {
                try {
                    await navigator.clipboard.writeText(share.url);
                    this.$dispatch('toast', { message: 'Link copied!', type: 'success' });
                } catch (e) {
                    console.error('Copy failed:', e);
                }
            },

            async deleteShare(share) {
                if (!confirm('Delete this share? The link will no longer work.')) return;
                
                try {
                    await window.apiFetch(`/api/shares/${share.id}`, { method: 'DELETE' });
                    this.shares = this.shares.filter(s => s.id !== share.id);
                    this.selectedIds = this.selectedIds.filter(id => id !== share.id);
                    this.updateStats();
                    this.$dispatch('toast', { message: 'Share deleted', type: 'success' });
                } catch (e) {
                    this.$dispatch('toast', { message: 'Failed to delete share', type: 'error' });
                }
            },

            toggleSelect(id) {
                if (this.selectedIds.includes(id)) {
                    this.selectedIds = this.selectedIds.filter(i => i !== id);
                } else {
                    this.selectedIds.push(id);
                }
            },

            toggleSelectAll() {
                if (this.selectedIds.length === this.filteredShares.length) {
                    this.selectedIds = [];
                } else {
                    this.selectedIds = this.filteredShares.map(s => s.id);
                }
            },

            async bulkDelete() {
                const count = this.selectedIds.length;
                if (!confirm(`Delete ${count} share${count > 1 ? 's' : ''}? The links will no longer work.`)) return;
                
                try {
                    await Promise.all(
                        this.selectedIds.map(id => 
                            window.apiFetch(`/api/shares/${id}`, { method: 'DELETE' })
                        )
                    );
                    this.shares = this.shares.filter(s => !this.selectedIds.includes(s.id));
                    this.selectedIds = [];
                    this.updateStats();
                    this.$dispatch('toast', { message: `${count} share${count > 1 ? 's' : ''} deleted`, type: 'success' });
                } catch (e) {
                    this.$dispatch('toast', { message: 'Failed to delete some shares', type: 'error' });
                }
            },

            updateStats() {
                let totalViews = 0, totalDownloads = 0, totalZipDownloads = 0;
                this.shares.forEach(s => {
                    totalViews += s.viewCount || 0;
                    totalDownloads += s.analytics?.downloads || 0;
                    totalZipDownloads += s.analytics?.zipDownloads || 0;
                });
                this.stats = {
                    totalShares: this.shares.length,
                    activeShares: this.shares.filter(s => !s.isExpired).length,
                    totalViews,
                    totalDownloads,
                    totalZipDownloads
                };
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },

            formatDateTime(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' + 
                       date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            },

            $watch: {
                selectedShare(share) {
                    if (share) this.loadShareAnalytics(share);
                }
            }
        };
    }
</script>
@endsection
