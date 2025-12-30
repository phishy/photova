<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Shared Files - Photova</title>
    @vite(['resources/css/app.css'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#0d1117] text-white min-h-screen" x-data="sharePage()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center gap-2 text-[15px] font-semibold text-[#c9d1d9] hover:text-white transition-colors">
                    <svg class="w-5 h-5 text-[#58a6ff]" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>Photova</span>
                </a>
            </div>
            <template x-if="share && share.allowZip && assets.length > 1">
                <button 
                    @click="downloadZip()"
                    :disabled="downloading"
                    class="flex items-center gap-2 px-4 py-2 bg-[#2563eb] hover:bg-[#1d4ed8] disabled:opacity-50 rounded-md text-white text-sm font-medium transition-colors"
                >
                    <template x-if="downloading">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="!downloading">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </template>
                    <span x-text="downloading ? 'Preparing...' : 'Download All (ZIP)'"></span>
                </button>
            </template>
        </div>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="flex flex-col items-center justify-center py-20">
                <svg class="animate-spin h-8 w-8 text-[#58a6ff] mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-[#8b949e]">Loading shared files...</p>
            </div>
        </template>

        <!-- Password Form -->
        <template x-if="!loading && needsPassword">
            <div class="max-w-md mx-auto mt-20">
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-[#21262d] flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-[#c9d1d9] mb-2">Password Protected</h2>
                    <p class="text-[#8b949e] text-sm mb-6">Enter the password to view these files.</p>
                    
                    <form @submit.prevent="submitPassword()">
                        <input
                            type="password"
                            x-model="password"
                            placeholder="Enter password"
                            class="w-full px-4 py-3 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm focus:outline-none focus:border-[#58a6ff] mb-4"
                            x-ref="passwordInput"
                            x-init="$nextTick(() => $refs.passwordInput?.focus())"
                        >
                        <p x-show="passwordError" class="text-[#f85149] text-sm mb-4" x-text="passwordError"></p>
                        <button
                            type="submit"
                            :disabled="!password || submitting"
                            class="w-full px-4 py-3 bg-[#2563eb] hover:bg-[#1d4ed8] disabled:opacity-50 disabled:cursor-not-allowed rounded-md text-white text-sm font-medium transition-colors"
                        >
                            <span x-text="submitting ? 'Verifying...' : 'View Files'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </template>

        <!-- Error State -->
        <template x-if="!loading && error && !needsPassword">
            <div class="max-w-md mx-auto mt-20">
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-[#f8514926] flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-[#f85149]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-[#c9d1d9] mb-2" x-text="errorTitle"></h2>
                    <p class="text-[#8b949e] text-sm" x-text="error"></p>
                </div>
            </div>
        </template>

        <!-- Share Content -->
        <template x-if="!loading && !error && !needsPassword && share">
            <div>
                <!-- Share Info -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-[#c9d1d9] mb-2" x-text="share.name || 'Shared Files'"></h1>
                    <p class="text-[#8b949e] text-sm">
                        <span x-text="assets.length"></span> file<span x-show="assets.length !== 1">s</span>
                        <template x-if="share.expiresAt">
                            <span class="ml-2">
                                · Expires <span x-text="formatDate(share.expiresAt)"></span>
                            </span>
                        </template>
                    </p>
                </div>

                <!-- Gallery Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <template x-for="asset in assets" :key="asset.id">
                        <div class="group bg-[#161b22] rounded-lg border border-[#30363d] hover:border-[#8b949e]/50 transition-colors overflow-hidden">
                            <div 
                                @click="isImage(asset) && openLightbox(asset)"
                                class="aspect-square bg-[#0d1117] flex items-center justify-center overflow-hidden cursor-pointer relative"
                            >
                                <template x-if="isImage(asset)">
                                    <img 
                                        :src="getThumbnailUrl(asset.id)" 
                                        :alt="asset.filename" 
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    >
                                </template>
                                <template x-if="!isImage(asset)">
                                    <span class="text-5xl">📄</span>
                                </template>
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-[13px] font-medium text-[#c9d1d9] mb-0.5 truncate" x-text="asset.filename"></div>
                                        <div class="text-xs text-[#8b949e]" x-text="formatFileSize(asset.size)"></div>
                                    </div>
                                    <template x-if="share.allowDownload">
                                        <button 
                                            @click="downloadAsset(asset)"
                                            class="p-1.5 rounded hover:bg-[#30363d] text-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                                            title="Download"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Lightbox -->
        <template x-if="lightboxAsset">
            <div
                @click="closeLightbox()"
                @keydown.escape.window="closeLightbox()"
                @keydown.arrow-left.window="goPrev()"
                @keydown.arrow-right.window="goNext()"
                class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center"
            >
                <button
                    @click.stop="closeLightbox()"
                    class="absolute top-5 right-5 text-3xl text-[#c9d1d9] opacity-80 hover:opacity-100 transition-opacity"
                >✕</button>

                <template x-if="imageAssets.length > 1">
                    <button
                        @click.stop="goPrev()"
                        class="absolute left-5 top-1/2 -translate-y-1/2 px-5 py-4 bg-white/10 rounded-lg text-2xl text-[#c9d1d9] hover:bg-white/20 transition-colors"
                    >‹</button>
                </template>

                <template x-if="imageAssets.length > 1">
                    <button
                        @click.stop="goNext()"
                        class="absolute right-5 top-1/2 -translate-y-1/2 px-5 py-4 bg-white/10 rounded-lg text-2xl text-[#c9d1d9] hover:bg-white/20 transition-colors"
                    >›</button>
                </template>

                <img
                    @click.stop
                    :src="getFullImageUrl(lightboxAsset.id)"
                    :alt="lightboxAsset.filename"
                    class="max-w-[90vw] max-h-[85vh] object-contain rounded"
                >

                <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex items-center gap-3">
                    <template x-if="share.allowDownload">
                        <button
                            @click.stop="downloadAsset(lightboxAsset)"
                            class="px-4 py-2 bg-[#2563eb] rounded-md text-white text-[13px] font-medium hover:bg-[#1d4ed8] transition-colors"
                        >Download</button>
                    </template>
                    <span class="text-[#8b949e] text-sm bg-black/60 px-3 py-1.5 rounded" x-text="(lightboxIndex + 1) + ' / ' + imageAssets.length"></span>
                </div>
            </div>
        </template>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function sharePage() {
            return {
                slug: '{{ $slug }}',
                loading: true,
                error: null,
                errorTitle: 'Error',
                needsPassword: false,
                password: '',
                passwordError: '',
                submitting: false,
                share: null,
                assets: [],
                lightboxAsset: null,
                lightboxIndex: 0,
                downloading: false,
                storedPassword: null,

                get imageAssets() {
                    return this.assets.filter(a => this.isImage(a));
                },

                async init() {
                    await this.loadShare();
                },

                async loadShare(password = null) {
                    this.loading = true;
                    this.error = null;
                    this.needsPassword = false;

                    try {
                        const url = '/api/s/' + this.slug + (password ? '?password=' + encodeURIComponent(password) : '');
                        const res = await fetch(url);
                        const data = await res.json();

                        if (res.status === 401 && data.password_required) {
                            this.needsPassword = true;
                            this.loading = false;
                            return;
                        }

                        if (!res.ok) {
                            this.errorTitle = res.status === 410 ? 'Share Expired' : 'Not Found';
                            this.error = data.error || 'This share could not be found.';
                            this.loading = false;
                            return;
                        }

                        this.share = data.share;
                        this.assets = data.assets;
                        this.storedPassword = password;
                    } catch (e) {
                        this.errorTitle = 'Error';
                        this.error = 'Failed to load shared files.';
                    }

                    this.loading = false;
                },

                async submitPassword() {
                    if (!this.password) return;
                    this.submitting = true;
                    this.passwordError = '';
                    
                    await this.loadShare(this.password);
                    
                    if (this.needsPassword) {
                        this.passwordError = 'Invalid password';
                    }
                    this.submitting = false;
                },

                isImage(asset) {
                    return asset.mimeType?.startsWith('image/');
                },

                getThumbnailUrl(assetId) {
                    let url = '/api/s/' + this.slug + '/assets/' + assetId + '/thumb?w=400&h=400';
                    return url;
                },

                getFullImageUrl(assetId) {
                    return '/api/s/' + this.slug + '/assets/' + assetId + '/download';
                },

                formatFileSize(bytes) {
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                },

                formatDate(dateStr) {
                    return new Date(dateStr).toLocaleDateString('en-US', {
                        month: 'short', day: 'numeric', year: 'numeric'
                    });
                },

                openLightbox(asset) {
                    this.lightboxAsset = asset;
                    this.lightboxIndex = this.imageAssets.findIndex(a => a.id === asset.id);
                },

                closeLightbox() {
                    this.lightboxAsset = null;
                },

                goNext() {
                    if (this.imageAssets.length <= 1) return;
                    this.lightboxIndex = (this.lightboxIndex + 1) % this.imageAssets.length;
                    this.lightboxAsset = this.imageAssets[this.lightboxIndex];
                },

                goPrev() {
                    if (this.imageAssets.length <= 1) return;
                    this.lightboxIndex = (this.lightboxIndex - 1 + this.imageAssets.length) % this.imageAssets.length;
                    this.lightboxAsset = this.imageAssets[this.lightboxIndex];
                },

                downloadAsset(asset) {
                    const link = document.createElement('a');
                    link.href = '/api/s/' + this.slug + '/assets/' + asset.id + '/download';
                    link.download = asset.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                },

                async downloadZip() {
                    this.downloading = true;
                    const link = document.createElement('a');
                    link.href = '/api/s/' + this.slug + '/zip';
                    link.download = (this.share.name || 'shared-files') + '.zip';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(() => { this.downloading = false; }, 2000);
                }
            }
        }
    </script>
</body>
</html>
