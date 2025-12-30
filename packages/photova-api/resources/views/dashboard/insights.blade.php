@extends('dashboard.layout')

@section('title', 'Insights')

@section('content')
<div 
    x-data="insightsPage()" 
    x-init="init()"
    @keydown.escape.window="closeLightbox()"
    @keydown.arrow-left.window="lightboxIndex !== null && goPrev()"
    @keydown.arrow-right.window="lightboxIndex !== null && goNext()"
>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Insights</h1>
    </div>

    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <svg class="animate-spin h-6 w-6 text-[#8b949e]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </template>

    <template x-if="!loading">
        <div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-5">
                    <div class="text-[#8b949e] text-xs uppercase tracking-wide mb-1">Total Files</div>
                    <div class="text-3xl font-semibold text-[#c9d1d9]" x-text="stats.totalAssets"></div>
                </div>
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-5">
                    <div class="text-[#8b949e] text-xs uppercase tracking-wide mb-1">AI Analyzed</div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-semibold text-[#c9d1d9]" x-text="stats.analyzedAssets"></span>
                        <span class="text-sm text-[#8b949e]" x-text="'(' + stats.analyzedPercent + '%)'"></span>
                    </div>
                </div>
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-5">
                    <div class="text-[#8b949e] text-xs uppercase tracking-wide mb-1">Total Size</div>
                    <div class="text-3xl font-semibold text-[#c9d1d9]" x-text="formatFileSize(stats.totalSize)"></div>
                </div>
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-5">
                    <div class="text-[#8b949e] text-xs uppercase tracking-wide mb-1">Unique Terms</div>
                    <div class="text-3xl font-semibold text-[#c9d1d9]" x-text="Object.keys(wordCloud).length"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-[#161b22] border border-[#30363d] rounded-lg p-6">
                    <h2 class="text-lg font-medium text-[#c9d1d9] mb-4">What's In Your Photos</h2>
                    <template x-if="Object.keys(wordCloud).length === 0">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="w-16 h-16 rounded-full bg-[#21262d] flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <p class="text-[#8b949e] text-sm">No analyzed photos yet.</p>
                            <p class="text-[#6e7681] text-xs mt-1">Upload images and they'll be automatically analyzed.</p>
                        </div>
                    </template>
                    <template x-if="Object.keys(wordCloud).length > 0">
                        <div class="flex flex-wrap gap-2 justify-center items-center min-h-[200px]">
                            <template x-for="(count, word) in wordCloud" :key="word">
                                <button 
                                    @click="searchWord(word)"
                                    class="px-3 py-1.5 rounded-full transition-all hover:scale-110 cursor-pointer"
                                    :style="getWordStyle(word, count)"
                                    x-text="word"
                                ></button>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-6">
                    <h2 class="text-lg font-medium text-[#c9d1d9] mb-4">File Types</h2>
                    <template x-if="Object.keys(mimeTypes).length === 0">
                        <p class="text-[#8b949e] text-sm text-center py-8">No files yet.</p>
                    </template>
                    <template x-if="Object.keys(mimeTypes).length > 0">
                        <div class="space-y-3">
                            <template x-for="(count, mime) in mimeTypes" :key="mime">
                                <div 
                                    @click="searchMimeType(mime)"
                                    class="cursor-pointer hover:bg-[#21262d] rounded-lg p-2 -mx-2 transition-colors"
                                >
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-[#c9d1d9]" x-text="formatMimeType(mime)"></span>
                                        <span class="text-[#8b949e]" x-text="count"></span>
                                    </div>
                                    <div class="h-2 bg-[#21262d] rounded-full overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all duration-500"
                                            :style="'width: ' + (count / stats.totalAssets * 100) + '%; background-color: ' + getMimeColor(mime)"
                                        ></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-6 bg-[#161b22] border border-[#30363d] rounded-lg p-6">
                <h2 class="text-lg font-medium text-[#c9d1d9] mb-4">Recently Analyzed</h2>
                <template x-if="recentlyAnalyzed.length === 0">
                    <p class="text-[#8b949e] text-sm text-center py-8">No recently analyzed files.</p>
                </template>
                <template x-if="recentlyAnalyzed.length > 0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                        <template x-for="(asset, index) in recentlyAnalyzed" :key="asset.id">
                            <div @click="openLightbox(index)" class="group cursor-pointer">
                                <div class="aspect-square bg-[#0d1117] rounded-lg overflow-hidden mb-2 relative">
                                    <img 
                                        :src="'/api/assets/' + asset.id + '/thumb?w=200&h=200'" 
                                        :alt="asset.filename"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                    >
                                </div>
                                <p class="text-xs text-[#8b949e] truncate" x-text="asset.filename"></p>
                                <p class="text-xs text-[#58a6ff] line-clamp-2 mt-0.5" x-text="asset.caption || 'No caption'"></p>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- Lightbox -->
    <template x-if="lightboxIndex !== null && recentlyAnalyzed[lightboxIndex]">
        <div
            @click="closeLightbox()"
            class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center"
        >
            <button
                @click.stop="closeLightbox()"
                class="absolute top-5 right-5 text-3xl text-[#c9d1d9] opacity-80 hover:opacity-100 transition-opacity"
            >✕</button>

            <template x-if="recentlyAnalyzed.length > 1">
                <button
                    @click.stop="goPrev()"
                    class="absolute left-5 top-1/2 -translate-y-1/2 px-5 py-4 bg-white/10 rounded-lg text-2xl text-[#c9d1d9] hover:bg-white/20 transition-colors"
                >‹</button>
            </template>

            <template x-if="recentlyAnalyzed.length > 1">
                <button
                    @click.stop="goNext()"
                    class="absolute right-5 top-1/2 -translate-y-1/2 px-5 py-4 bg-white/10 rounded-lg text-2xl text-[#c9d1d9] hover:bg-white/20 transition-colors"
                >›</button>
            </template>

            <img
                @click.stop
                :src="'/api/assets/' + recentlyAnalyzed[lightboxIndex].id + '?inline=true'"
                :alt="recentlyAnalyzed[lightboxIndex].filename"
                class="max-w-[90vw] max-h-[85vh] object-contain rounded"
            >

            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex items-center gap-3">
                <button
                    @click.stop="editAsset(recentlyAnalyzed[lightboxIndex].id)"
                    class="px-4 py-2 bg-[#2563eb] rounded-md text-white text-[13px] font-medium hover:bg-[#1d4ed8] transition-colors"
                >Edit in Photova</button>
                <span class="text-[#8b949e] text-sm bg-black/60 px-3 py-1.5 rounded" x-text="(lightboxIndex + 1) + ' / ' + recentlyAnalyzed.length"></span>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    function insightsPage() {
        return {
            loading: true,
            stats: { totalAssets: 0, analyzedAssets: 0, analyzedPercent: 0, totalSize: 0 },
            wordCloud: {},
            mimeTypes: {},
            recentlyAnalyzed: [],
            maxCount: 1,
            lightboxIndex: null,

            async init() {
                await this.loadInsights();
            },

            async loadInsights() {
                try {
                    const res = await window.apiFetch('/api/assets/insights');
                    if (res.ok) {
                        const data = await res.json();
                        this.stats = data.stats;
                        this.wordCloud = data.wordCloud;
                        this.mimeTypes = data.mimeTypes;
                        this.recentlyAnalyzed = data.recentlyAnalyzed;
                        this.maxCount = Math.max(...Object.values(this.wordCloud), 1);
                    }
                } catch (e) {
                    console.error('Failed to load insights:', e);
                }
                this.loading = false;
            },

            formatFileSize(bytes) {
                if (!bytes) return '0 B';
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
            },

            formatMimeType(mime) {
                const parts = mime.split('/');
                return parts[1]?.toUpperCase() || mime;
            },

            getWordStyle(word, count) {
                const ratio = count / this.maxCount;
                const minSize = 12;
                const maxSize = 32;
                const size = minSize + (ratio * (maxSize - minSize));
                
                const colors = ['#58a6ff', '#3fb950', '#f0883e', '#a371f7', '#f778ba', '#79c0ff'];
                const colorIndex = word.charCodeAt(0) % colors.length;
                const color = colors[colorIndex];
                
                const opacity = 0.6 + (ratio * 0.4);
                
                return `font-size: ${size}px; color: ${color}; opacity: ${opacity}; background-color: ${color}20;`;
            },

            getMimeColor(mime) {
                const colors = {
                    'image/jpeg': '#f0883e',
                    'image/jpg': '#f0883e',
                    'image/png': '#58a6ff',
                    'image/gif': '#a371f7',
                    'image/webp': '#3fb950',
                    'image/svg+xml': '#f778ba',
                    'image/heic': '#a371f7',
                    'image/heif': '#a371f7',
                };
                return colors[mime] || '#8b949e';
            },

            searchWord(word) {
                window.location.href = '/dashboard?search=' + encodeURIComponent(word);
            },

            searchMimeType(mime) {
                window.location.href = '/dashboard?mime_type=' + encodeURIComponent(mime);
            },

            openLightbox(index) {
                this.lightboxIndex = index;
            },

            closeLightbox() {
                this.lightboxIndex = null;
            },

            goNext() {
                if (this.lightboxIndex !== null && this.recentlyAnalyzed.length > 0) {
                    this.lightboxIndex = (this.lightboxIndex + 1) % this.recentlyAnalyzed.length;
                }
            },

            goPrev() {
                if (this.lightboxIndex !== null && this.recentlyAnalyzed.length > 0) {
                    this.lightboxIndex = (this.lightboxIndex - 1 + this.recentlyAnalyzed.length) % this.recentlyAnalyzed.length;
                }
            },

            editAsset(id) {
                window.location.href = '/dashboard/playground?asset=' + id;
            }
        }
    }
</script>
@endsection
