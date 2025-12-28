@extends('dashboard.layout')

@section('title', 'Assets')

@section('content')
<div 
    x-data="assetsPage()" 
    x-init="loadAssets()" 
    @keydown.escape.window="closeLightbox()"
    @drop.prevent="handleDrop($event)"
    @dragover.prevent="dragOver = true"
    @dragleave.prevent="dragOver = false"
    @dragend="dragOver = false"
    class="min-h-[calc(100vh-4rem)] relative"
>
    <!-- Hidden file input -->
    <input
        x-ref="fileInput"
        type="file"
        accept="image/*"
        @change="handleFileSelect($event)"
        class="hidden"
    >

    <!-- Drag overlay (shows when dragging files over the page) -->
    <div
        x-show="dragOver"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-[#58a6ff]/10 border-2 border-dashed border-[#58a6ff] rounded-lg z-40 flex items-center justify-center pointer-events-none"
    >
        <div class="text-center">
            <div class="text-4xl mb-2">📁</div>
            <div class="text-[#58a6ff] font-medium">Drop to upload</div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Assets</h1>
        <button
            @click="$refs.fileInput.click()"
            :disabled="uploading"
            class="flex items-center gap-2 px-4 py-2 bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] hover:border-[#8b949e] disabled:opacity-50 disabled:cursor-not-allowed rounded-md text-[#c9d1d9] text-sm font-medium transition-colors"
        >
            <template x-if="uploading">
                <span class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Uploading...
                </span>
            </template>
            <template x-if="!uploading">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Upload
                </span>
            </template>
        </button>
    </div>

    <!-- Loading State -->
    <template x-if="loading">
        <div class="flex items-center justify-center py-20">
            <svg class="animate-spin h-6 w-6 text-[#8b949e]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </template>

    <!-- Empty State -->
    <template x-if="!loading && assets.length === 0">
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-full bg-[#21262d] flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-[#c9d1d9] font-medium mb-1">No assets yet</h3>
            <p class="text-[#8b949e] text-sm mb-4">Upload images to get started</p>
            <p class="text-[#6e7681] text-xs">Drag & drop anywhere or use the Upload button</p>
        </div>
    </template>

    <!-- Assets Grid -->
    <template x-if="!loading && assets.length > 0">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <template x-for="asset in assets" :key="asset.id">
                <div class="group bg-[#161b22] rounded-lg border border-[#30363d] overflow-hidden hover:border-[#8b949e]/50 transition-colors">
                    <div
                        @click="isImage(asset) && openLightbox(asset.id)"
                        class="aspect-square bg-[#0d1117] flex items-center justify-center overflow-hidden relative"
                        :class="isImage(asset) ? 'cursor-pointer' : ''"
                    >
                        <template x-if="isImage(asset)">
                            <img :src="'/api/assets/' + asset.id + '?download=true'" :alt="asset.filename" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!isImage(asset)">
                            <span class="text-5xl">📄</span>
                        </template>
                        <!-- Hover overlay -->
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-white text-xs font-medium">Click to preview</span>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="text-[13px] font-medium text-[#c9d1d9] mb-1 truncate" x-text="asset.filename"></div>
                        <div class="text-xs text-[#8b949e] mb-2" x-text="formatFileSize(asset.size) + ' • ' + formatDate(asset.created || asset.createdAt || asset.created_at)"></div>
                        <div class="flex gap-2">
                            <template x-if="isImage(asset)">
                                <button
                                    @click.stop="editAsset(asset.id)"
                                    class="flex-1 py-1.5 bg-[#58a6ff]/10 border border-[#58a6ff]/40 rounded-md text-[#58a6ff] text-xs hover:bg-[#58a6ff]/20 transition-colors"
                                >
                                    Edit
                                </button>
                            </template>
                            <button
                                @click.stop="shareAsset(asset.id)"
                                class="flex-1 py-1.5 bg-transparent border border-[#30363d] rounded-md text-[#8b949e] text-xs hover:border-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                            >
                                Share
                            </button>
                            <button
                                @click.stop="deleteAsset(asset.id)"
                                class="px-2.5 py-1.5 bg-transparent border border-[#f8514966] rounded-md text-[#f85149] text-xs hover:bg-[#f8514919] transition-colors"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- Lightbox -->
    <template x-if="lightboxIndex !== null && imageAssets[lightboxIndex]">
        <div
            @click="closeLightbox()"
            class="fixed inset-0 bg-black/90 z-50 flex items-center justify-center"
        >
            <button
                @click.stop="closeLightbox()"
                class="absolute top-5 right-5 text-3xl text-[#c9d1d9] opacity-80 hover:opacity-100 transition-opacity"
            >
                ✕
            </button>

            <template x-if="imageAssets.length > 1">
                <button
                    @click.stop="goPrev()"
                    class="absolute left-5 top-1/2 -translate-y-1/2 px-5 py-4 bg-white/10 rounded-lg text-2xl text-[#c9d1d9] hover:bg-white/20 transition-colors"
                >
                    ‹
                </button>
            </template>

            <template x-if="imageAssets.length > 1">
                <button
                    @click.stop="goNext()"
                    class="absolute right-5 top-1/2 -translate-y-1/2 px-5 py-4 bg-white/10 rounded-lg text-2xl text-[#c9d1d9] hover:bg-white/20 transition-colors"
                >
                    ›
                </button>
            </template>

            <img
                @click.stop
                :src="'/api/assets/' + imageAssets[lightboxIndex].id + '?download=true'"
                :alt="imageAssets[lightboxIndex].filename"
                class="max-w-[90vw] max-h-[85vh] object-contain rounded"
            >

            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex items-center gap-3">
                <button
                    @click.stop="editAsset(imageAssets[lightboxIndex].id)"
                    class="px-4 py-2 bg-[#238636] rounded-md text-white text-[13px] font-medium hover:bg-[#2ea043] transition-colors"
                >
                    Edit in Photova
                </button>
                <span class="text-[#8b949e] text-sm bg-black/60 px-3 py-1.5 rounded" x-text="(lightboxIndex + 1) + ' / ' + imageAssets.length"></span>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    function assetsPage() {
        return {
            assets: [],
            loading: true,
            uploading: false,
            dragOver: false,
            lightboxIndex: null,

            get imageAssets() {
                return this.assets.filter(a => this.isImage(a));
            },

            isImage(asset) {
                const mimeType = asset.mimeType || asset.mime_type || '';
                return mimeType.startsWith('image/');
            },

            formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            },

            formatDate(dateStr) {
                return new Date(dateStr).toLocaleDateString('en-US', {
                    month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
                });
            },

            async loadAssets() {
                try {
                    const res = await window.apiFetch('/api/assets');
                    if (res.ok) {
                        const data = await res.json();
                        this.assets = data.assets || [];
                    }
                } catch (e) {
                    console.error('Failed to load assets:', e);
                }
                this.loading = false;
            },

            async uploadFile(file) {
                this.uploading = true;
                try {
                    const formData = new FormData();
                    formData.append('file', file);

                    const res = await fetch('/api/assets', {
                        method: 'POST',
                        body: formData,
                        credentials: 'include',
                        headers: {
                            'X-CSRF-TOKEN': window.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (!res.ok) {
                        const error = await res.json();
                        throw new Error(error.error || 'Upload failed');
                    }

                    await this.loadAssets();
                    this.$dispatch('toast', { message: 'Image uploaded successfully', type: 'success' });
                } catch (e) {
                    console.error('Upload failed:', e);
                    this.$dispatch('toast', { message: e.message || 'Upload failed', type: 'error' });
                }
                this.uploading = false;
            },

            handleFileSelect(e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    this.uploadFile(files[0]);
                }
                e.target.value = '';
            },

            handleDrop(e) {
                this.dragOver = false;
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    this.uploadFile(files[0]);
                }
            },

            async deleteAsset(id) {
                if (!confirm('Delete this asset?')) return;
                try {
                    await window.apiFetch(`/api/assets/${id}`, { method: 'DELETE' });
                    await this.loadAssets();
                    this.$dispatch('toast', { message: 'Asset deleted', type: 'success' });
                } catch (e) {
                    console.error('Delete failed:', e);
                    this.$dispatch('toast', { message: 'Delete failed', type: 'error' });
                }
            },

            async shareAsset(id) {
                try {
                    const res = await window.apiFetch(`/api/assets/${id}/share`, { method: 'POST' });
                    if (!res.ok) {
                        const error = await res.json();
                        throw new Error(error.error || 'Failed to generate share link');
                    }
                    const data = await res.json();
                    await navigator.clipboard.writeText(data.url);
                    this.$dispatch('toast', { message: 'Share link copied! Expires in 24 hours.', type: 'success' });
                } catch (e) {
                    console.error('Share failed:', e);
                    this.$dispatch('toast', { message: e.message || 'Failed to generate share link', type: 'error' });
                }
            },

            editAsset(id) {
                window.location.href = '/dashboard/playground?asset=' + id;
            },

            openLightbox(id) {
                const idx = this.imageAssets.findIndex(a => a.id === id);
                if (idx !== -1) this.lightboxIndex = idx;
            },

            closeLightbox() {
                this.lightboxIndex = null;
            },

            goNext() {
                if (this.lightboxIndex !== null && this.imageAssets.length > 0) {
                    this.lightboxIndex = (this.lightboxIndex + 1) % this.imageAssets.length;
                }
            },

            goPrev() {
                if (this.lightboxIndex !== null && this.imageAssets.length > 0) {
                    this.lightboxIndex = (this.lightboxIndex - 1 + this.imageAssets.length) % this.imageAssets.length;
                }
            }
        }
    }
</script>
@endsection
