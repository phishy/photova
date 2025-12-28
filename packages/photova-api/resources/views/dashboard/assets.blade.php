@extends('dashboard.layout')

@section('title', 'Files')

@section('content')
<div 
    x-data="assetsPage()" 
    x-init="init()" 
    @keydown.escape.window="closeLightbox(); showCreateFolder = false; showMoveModal = false; showTagManager = false; showTagAssetModal = false; showDetailsModal = false; confirmModal.show = false"
    @keydown.arrow-left.window="lightboxIndex !== null && goPrev()"
    @keydown.arrow-right.window="lightboxIndex !== null && goNext()"
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

    <!-- Drag overlay -->
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
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Files</h1>
        <div class="flex items-center gap-2">
            <!-- View Toggle -->
            <div class="flex items-center bg-[#21262d] border border-[#30363d] rounded-md overflow-hidden">
                <button
                    @click="setViewMode('grid')"
                    :class="viewMode === 'grid' ? 'bg-[#30363d] text-[#c9d1d9]' : 'text-[#8b949e] hover:text-[#c9d1d9]'"
                    class="p-2 transition-colors"
                    title="Grid view"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button
                    @click="setViewMode('list')"
                    :class="viewMode === 'list' ? 'bg-[#30363d] text-[#c9d1d9]' : 'text-[#8b949e] hover:text-[#c9d1d9]'"
                    class="p-2 transition-colors"
                    title="List view"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
            <!-- New Folder Button -->
            <button
                @click="showCreateFolder = true"
                class="flex items-center gap-1.5 px-3 py-2 bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] hover:border-[#8b949e] rounded-md text-[#c9d1d9] text-sm font-medium transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                <span class="hidden sm:inline">New Folder</span>
            </button>
            <!-- Upload Button -->
            <button
                @click="$refs.fileInput.click()"
                :disabled="uploading"
                class="flex items-center gap-1.5 px-3 py-2 bg-[#2563eb] hover:bg-[#1d4ed8] disabled:opacity-50 disabled:cursor-not-allowed rounded-md text-white text-sm font-medium transition-colors"
            >
                <template x-if="uploading">
                    <span class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="hidden sm:inline">Uploading...</span>
                    </span>
                </template>
                <template x-if="!uploading">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="hidden sm:inline">Upload</span>
                    </span>
                </template>
            </button>
        </div>
    </div>

    <!-- Breadcrumb (hidden when tag filtering - global search) -->
    <template x-if="selectedTags.length === 0">
        <div class="flex items-center gap-2 mb-4 text-sm">
            <button 
                @click="navigateToFolder(null)" 
                class="text-[#58a6ff] hover:underline"
                :class="currentFolderId === null ? 'font-medium' : ''"
            >Files</button>
            <template x-for="(crumb, index) in breadcrumbs" :key="crumb.id">
                <div class="flex items-center gap-2">
                    <span class="text-[#8b949e]">/</span>
                    <button 
                        @click="navigateToFolder(crumb.id)" 
                        class="text-[#58a6ff] hover:underline"
                        :class="index === breadcrumbs.length - 1 ? 'font-medium' : ''"
                        x-text="crumb.name"
                    ></button>
                </div>
            </template>
        </div>
    </template>

    <!-- Search and Tag Filters -->
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <!-- Search Input -->
        <div class="relative flex-1 max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                x-model="searchQuery"
                @input.debounce.300ms="searchAssets()"
                placeholder="Search files..."
                class="w-full pl-10 pr-8 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm placeholder-[#8b949e] focus:outline-none focus:border-[#58a6ff]"
            >
            <button
                x-show="searchQuery"
                @click="searchQuery = ''; searchAssets()"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8b949e] hover:text-[#c9d1d9]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Tag Filters -->
        <div class="flex items-center gap-2 flex-wrap">
            <template x-for="tag in tags" :key="tag.id">
                <button
                    @click="toggleTagFilter(tag.id)"
                    :class="selectedTags.includes(tag.id) ? 'ring-2 ring-[#58a6ff]' : 'opacity-70 hover:opacity-100'"
                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-all"
                    :style="'background-color: ' + tag.color + '20; color: ' + tag.color"
                >
                    <span class="w-2 h-2 rounded-full" :style="'background-color: ' + tag.color"></span>
                    <span x-text="tag.name"></span>
                    <span class="text-[10px] opacity-70" x-text="'(' + (tag.assets_count || 0) + ')'"></span>
                </button>
            </template>
            <!-- Manage Tags Button -->
            <button
                @click="showTagManager = true"
                class="flex items-center gap-1 px-2.5 py-1 text-xs text-[#8b949e] hover:text-[#c9d1d9] border border-dashed border-[#30363d] hover:border-[#8b949e] rounded-full transition-colors"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tags</span>
            </button>
        </div>
    </div>

    <!-- Active filters indicator -->
    <template x-if="searchQuery || selectedTags.length > 0">
        <div class="flex items-center gap-2 mb-4 text-sm flex-wrap">
            <span class="text-[#8b949e]">
                <template x-if="selectedTags.length > 0">
                    <span>Searching all files:</span>
                </template>
                <template x-if="selectedTags.length === 0">
                    <span>Filtering:</span>
                </template>
            </span>
            <template x-if="searchQuery">
                <span class="px-2 py-0.5 bg-[#21262d] rounded text-[#c9d1d9]">
                    "<span x-text="searchQuery"></span>"
                </span>
            </template>
            <template x-for="tagId in selectedTags" :key="tagId">
                <span 
                    class="px-2 py-0.5 rounded text-xs"
                    :style="'background-color: ' + getTagById(tagId)?.color + '20; color: ' + getTagById(tagId)?.color"
                    x-text="getTagById(tagId)?.name"
                ></span>
            </template>
            <button 
                @click="clearFilters()" 
                class="text-[#58a6ff] hover:underline text-xs"
            >Clear all</button>
        </div>
    </template>

    <!-- Bulk Actions Bar -->
    <template x-if="selectedAssets.length > 0">
        <div class="flex items-center justify-between gap-4 mb-4 px-4 py-3 bg-[#161b22] border border-[#30363d] rounded-lg">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <button
                        @click="allSelected ? deselectAll() : selectAll()"
                        class="w-5 h-5 rounded flex items-center justify-center transition-colors"
                        :class="allSelected 
                            ? 'bg-[#2563eb] border border-[#2563eb]' 
                            : someSelected 
                                ? 'bg-[#2563eb]/50 border border-[#2563eb]' 
                                : 'bg-transparent border-2 border-[#484f58] hover:border-[#8b949e]'"
                    >
                        <svg x-show="allSelected || someSelected" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                    <span class="text-[#c9d1d9] text-sm font-medium">
                        <span x-text="selectedAssets.length"></span> selected
                    </span>
                </div>
                <button 
                    @click="deselectAll()" 
                    class="text-[#58a6ff] text-sm hover:underline"
                >Clear selection</button>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="openBulkMoveModal()"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    Move
                </button>
                <button
                    @click="bulkDelete()"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-[#f8514926] hover:bg-[#f8514940] border border-[#f8514966] rounded-md text-[#f85149] text-sm transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </template>

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
    <template x-if="!loading && folders.length === 0 && assets.length === 0">
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-full bg-[#21262d] flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-[#c9d1d9] font-medium mb-1">No files yet</h3>
            <p class="text-[#8b949e] text-sm mb-4">Upload images or create folders to get started</p>
            <p class="text-[#6e7681] text-xs">Drag & drop anywhere or use the Upload button</p>
        </div>
    </template>

    <!-- Content -->
    <template x-if="!loading && (folders.length > 0 || assets.length > 0)">
        <div>
            <!-- Grid View -->
            <template x-if="viewMode === 'grid'">
                <div>
                    <!-- Folders Grid (hidden during search or tag filtering) -->
                    <template x-if="(folders.length > 0 || currentFolderId) && !searchQuery.trim() && selectedTags.length === 0">
                        <div class="mb-6">
                            <div class="text-xs text-[#8b949e] uppercase tracking-wide mb-3">Folders</div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                                <!-- Parent Folder (..) -->
                                <template x-if="currentFolderId">
                                    <div 
                                        @click="navigateToFolder(parentFolderId)"
                                        @dragover.prevent="dropTargetFolderId = 'parent'"
                                        @dragenter.prevent="dropTargetFolderId = 'parent'"
                                        @dragleave.prevent="dropTargetFolderId = null"
                                        @drop.prevent="handleDropOnFolder(parentFolderId)"
                                        class="group bg-[#161b22] rounded-lg border p-3 cursor-pointer transition-all"
                                        :class="dropTargetFolderId === 'parent' ? 'border-[#58a6ff] bg-[#58a6ff]/10 scale-105' : 'border-[#30363d] hover:border-[#8b949e]/50'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg class="w-8 h-8 transition-colors" :class="dropTargetFolderId === 'parent' ? 'text-[#58a6ff]' : 'text-[#8b949e] group-hover:text-[#58a6ff]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                            </svg>
                                            <span class="text-[#c9d1d9] text-sm font-medium">..</span>
                                        </div>
                                    </div>
                                </template>
                                <template x-for="folder in folders" :key="folder.id">
                                    <div 
                                        @click="navigateToFolder(folder.id)"
                                        @contextmenu.prevent="openFolderMenu($event, folder)"
                                        @dragover.prevent="dropTargetFolderId = folder.id"
                                        @dragenter.prevent="dropTargetFolderId = folder.id"
                                        @dragleave.prevent="dropTargetFolderId = null"
                                        @drop.prevent="handleDropOnFolder(folder.id)"
                                        class="group bg-[#161b22] rounded-lg border p-3 cursor-pointer transition-all"
                                        :class="dropTargetFolderId === folder.id ? 'border-[#58a6ff] bg-[#58a6ff]/10 scale-105' : 'border-[#30363d] hover:border-[#8b949e]/50'"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg class="w-8 h-8 transition-colors" :class="dropTargetFolderId === folder.id ? 'text-[#58a6ff]' : 'text-[#8b949e] group-hover:text-[#58a6ff]'" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                                            </svg>
                                            <span class="text-[#c9d1d9] text-sm font-medium truncate" x-text="folder.name"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Files Grid -->
                    <template x-if="assets.length > 0">
                        <div>
                            <template x-if="folders.length > 0 && !searchQuery.trim() && selectedTags.length === 0">
                                <div class="text-xs text-[#8b949e] uppercase tracking-wide mb-3">Files</div>
                            </template>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                <template x-for="asset in assets" :key="asset.id">
                                    <div 
                                        class="group bg-[#161b22] rounded-lg border border-[#30363d] hover:border-[#8b949e]/50 transition-colors"
                                        :class="draggingAssetId === asset.id ? 'opacity-50' : ''"
                                        draggable="true"
                                        @dragstart="startDragAsset($event, asset.id)"
                                        @dragend="endDragAsset()"
                                    >
                                        <div
                                            @click="isImage(asset) && openLightbox(asset.id)"
                                            class="aspect-square bg-[#0d1117] flex items-center justify-center overflow-hidden relative rounded-t-lg"
                                            :class="isImage(asset) ? 'cursor-pointer' : ''"
                                        >
                                            <template x-if="isImage(asset)">
                                                <img :src="'/api/assets/' + asset.id + '?download=true'" :alt="asset.filename" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!isImage(asset)">
                                                <span class="text-5xl">📄</span>
                                            </template>
                                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                <span class="text-white text-xs font-medium">Click to preview</span>
                                            </div>
                                        </div>
                                        <div class="p-3">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-[13px] font-medium text-[#c9d1d9] mb-0.5 truncate" x-text="asset.filename"></div>
                                                    <div class="text-xs text-[#8b949e]" x-text="formatFileSize(asset.size)"></div>
                                                    <!-- Asset Tags -->
                                                    <div x-show="asset.tags && asset.tags.length > 0" class="flex flex-wrap gap-1 mt-1.5">
                                                        <template x-for="tag in (asset.tags || []).slice(0, 3)" :key="tag.id">
                                                            <span 
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                                                                :style="'background-color: ' + tag.color + '20; color: ' + tag.color"
                                                                x-text="tag.name"
                                                            ></span>
                                                        </template>
                                                        <span 
                                                            x-show="asset.tags && asset.tags.length > 3" 
                                                            class="text-[10px] text-[#8b949e]"
                                                            x-text="'+' + (asset.tags.length - 3)"
                                                        ></span>
                                                    </div>
                                                </div>
                                                <div class="relative" x-data="{ open: false }">
                                                    <button 
                                                        @click.stop="open = !open"
                                                        class="p-1 rounded hover:bg-[#30363d] text-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                                                    >
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                                                            <circle cx="8" cy="2.5" r="1.5"/>
                                                            <circle cx="8" cy="8" r="1.5"/>
                                                            <circle cx="8" cy="13.5" r="1.5"/>
                                                        </svg>
                                                    </button>
                                                    <div 
                                                        x-show="open" 
                                                        @click.outside="open = false"
                                                        x-transition
                                                        class="absolute right-0 mt-1 w-36 bg-[#161b22] border border-[#30363d] rounded-md shadow-lg z-10 py-1"
                                                    >
                                                        <template x-if="isImage(asset)">
                                                            <button 
                                                                @click.stop="editAsset(asset.id); open = false"
                                                                class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                            >Edit</button>
                                                        </template>
                                                        <button 
                                                            @click.stop="openTagAssetModal(asset); open = false"
                                                            class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                        >Tags...</button>
                                                        <button 
                                                            @click.stop="openDetailsModal(asset); open = false"
                                                            class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                        >Details</button>
                                                        <button 
                                                            @click.stop="openMoveModal(asset.id); open = false"
                                                            class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                        >Move to...</button>
                                                        <button 
                                                            @click.stop="shareAsset(asset.id); open = false"
                                                            class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                        >Share</button>
                                                        <button 
                                                            @click.stop="downloadAsset(asset.id); open = false"
                                                            class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                        >Download</button>
                                                        <div class="border-t border-[#30363d] my-1"></div>
                                                        <button 
                                                            @click.stop="deleteAsset(asset.id); open = false"
                                                            class="w-full px-3 py-1.5 text-left text-sm text-[#f85149] hover:bg-[#30363d] transition-colors"
                                                        >Delete</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- List View -->
            <template x-if="viewMode === 'list'">
                <div class="bg-[#161b22] border border-[#30363d] rounded-lg">
                    <table class="w-full">
                        <thead class="bg-[#21262d] border-b border-[#30363d]">
                            <tr>
                                <th class="text-left px-4 py-3 text-xs font-medium text-[#8b949e] uppercase tracking-wide">Name</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-[#8b949e] uppercase tracking-wide hidden sm:table-cell">Size</th>
                                <th class="text-left px-4 py-3 text-xs font-medium text-[#8b949e] uppercase tracking-wide hidden md:table-cell">Modified</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#30363d]">
                            <!-- Parent Folder (..) -->
                            <template x-if="currentFolderId && !searchQuery.trim() && selectedTags.length === 0">
                                <tr 
                                    @click="navigateToFolder(parentFolderId)"
                                    @dragover.prevent="dropTargetFolderId = 'parent'"
                                    @dragenter.prevent="dropTargetFolderId = 'parent'"
                                    @dragleave.prevent="dropTargetFolderId = null"
                                    @drop.prevent="handleDropOnFolder(parentFolderId)"
                                    class="cursor-pointer transition-all"
                                    :class="dropTargetFolderId === 'parent' ? 'bg-[#58a6ff]/10' : 'hover:bg-[#21262d]'"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 transition-colors" :class="dropTargetFolderId === 'parent' ? 'text-[#58a6ff]' : 'text-[#8b949e]'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                            </svg>
                                            <span class="text-[#c9d1d9] text-sm font-medium">..</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[#8b949e] hidden sm:table-cell">--</td>
                                    <td class="px-4 py-3 text-sm text-[#8b949e] hidden md:table-cell">--</td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </template>
                            <!-- Folders (hidden during search or tag filtering) -->
                            <template x-for="folder in ((!searchQuery.trim() && selectedTags.length === 0) ? folders : [])" :key="'folder-' + folder.id">
                                <tr 
                                    @click="navigateToFolder(folder.id)"
                                    @dragover.prevent="dropTargetFolderId = folder.id"
                                    @dragenter.prevent="dropTargetFolderId = folder.id"
                                    @dragleave.prevent="dropTargetFolderId = null"
                                    @drop.prevent="handleDropOnFolder(folder.id)"
                                    class="cursor-pointer transition-all"
                                    :class="dropTargetFolderId === folder.id ? 'bg-[#58a6ff]/10' : 'hover:bg-[#21262d]'"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 transition-colors" :class="dropTargetFolderId === folder.id ? 'text-[#58a6ff]' : 'text-[#8b949e]'" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                                            </svg>
                                            <span class="text-[#c9d1d9] text-sm font-medium" x-text="folder.name"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[#8b949e] hidden sm:table-cell">--</td>
                                    <td class="px-4 py-3 text-sm text-[#8b949e] hidden md:table-cell" x-text="formatDate(folder.created)"></td>
                                    <td class="px-4 py-3">
                                        <div class="relative" x-data="{ open: false }">
                                            <button 
                                                @click.stop="open = !open"
                                                class="p-1 rounded hover:bg-[#30363d] text-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                                                    <circle cx="8" cy="2.5" r="1.5"/>
                                                    <circle cx="8" cy="8" r="1.5"/>
                                                    <circle cx="8" cy="13.5" r="1.5"/>
                                                </svg>
                                            </button>
                                            <div 
                                                x-show="open" 
                                                @click.outside="open = false"
                                                x-transition
                                                class="absolute right-0 mt-1 w-32 bg-[#161b22] border border-[#30363d] rounded-md shadow-lg z-10 py-1"
                                            >
                                                <button 
                                                    @click.stop="deleteFolder(folder.id); open = false"
                                                    class="w-full px-3 py-1.5 text-left text-sm text-[#f85149] hover:bg-[#30363d] transition-colors"
                                                >Delete</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <!-- Files -->
                            <template x-for="asset in assets" :key="'asset-' + asset.id">
                                <tr 
                                    class="hover:bg-[#21262d] transition-colors"
                                    :class="draggingAssetId === asset.id ? 'opacity-50' : ''"
                                    draggable="true"
                                    @dragstart="startDragAsset($event, asset.id)"
                                    @dragend="endDragAsset()"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <template x-if="isImage(asset)">
                                                <img 
                                                    :src="'/api/assets/' + asset.id + '?download=true'" 
                                                    :alt="asset.filename" 
                                                    class="w-8 h-8 rounded object-cover cursor-pointer"
                                                    @click="openLightbox(asset.id)"
                                                >
                                            </template>
                                            <template x-if="!isImage(asset)">
                                                <span class="text-2xl">📄</span>
                                            </template>
                                            <div>
                                                <span class="text-[#c9d1d9] text-sm" x-text="asset.filename"></span>
                                                <!-- Asset Tags in List View -->
                                                <div x-show="asset.tags && asset.tags.length > 0" class="flex flex-wrap gap-1 mt-1">
                                                    <template x-for="tag in (asset.tags || []).slice(0, 3)" :key="tag.id">
                                                        <span 
                                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                                                            :style="'background-color: ' + tag.color + '20; color: ' + tag.color"
                                                            x-text="tag.name"
                                                        ></span>
                                                    </template>
                                                    <span 
                                                        x-show="asset.tags && asset.tags.length > 3" 
                                                        class="text-[10px] text-[#8b949e]"
                                                        x-text="'+' + (asset.tags.length - 3)"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-[#8b949e] hidden sm:table-cell" x-text="formatFileSize(asset.size)"></td>
                                    <td class="px-4 py-3 text-sm text-[#8b949e] hidden md:table-cell" x-text="formatDate(asset.created)"></td>
                                    <td class="px-4 py-3">
                                        <div class="relative" x-data="{ open: false }">
                                            <button 
                                                @click.stop="open = !open"
                                                class="p-1 rounded hover:bg-[#30363d] text-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                                            >
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                                                    <circle cx="8" cy="2.5" r="1.5"/>
                                                    <circle cx="8" cy="8" r="1.5"/>
                                                    <circle cx="8" cy="13.5" r="1.5"/>
                                                </svg>
                                            </button>
                                            <div 
                                                x-show="open" 
                                                @click.outside="open = false"
                                                x-transition
                                                class="absolute right-0 mt-1 w-36 bg-[#161b22] border border-[#30363d] rounded-md shadow-lg z-10 py-1"
                                            >
                                                <template x-if="isImage(asset)">
                                                    <button 
                                                        @click.stop="editAsset(asset.id); open = false"
                                                        class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                    >Edit</button>
                                                </template>
                                                            <button 
                                                                @click.stop="openTagAssetModal(asset); open = false"
                                                                class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                            >Tags...</button>
                                                            <button 
                                                                @click.stop="openDetailsModal(asset); open = false"
                                                                class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                            >Details</button>
                                                            <button 
                                                                @click.stop="openMoveModal(asset.id); open = false"
                                                                class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                            >Move to...</button>
                                                            <button 
                                                                @click.stop="shareAsset(asset.id); open = false"
                                                                class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                            >Share</button>
                                                            <button 
                                                                @click.stop="downloadAsset(asset.id); open = false"
                                                                class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d] transition-colors"
                                                            >Download</button>
                                                            <div class="border-t border-[#30363d] my-1"></div>
                                                            <button 
                                                                @click.stop="deleteAsset(asset.id); open = false"
                                                                class="w-full px-3 py-1.5 text-left text-sm text-[#f85149] hover:bg-[#30363d] transition-colors"
                                                            >Delete</button>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
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
                :src="'/api/assets/' + imageAssets[lightboxIndex].id + '?download=true'"
                :alt="imageAssets[lightboxIndex].filename"
                class="max-w-[90vw] max-h-[85vh] object-contain rounded"
            >

            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex items-center gap-3">
                <button
                    @click.stop="editAsset(imageAssets[lightboxIndex].id)"
                    class="px-4 py-2 bg-[#2563eb] rounded-md text-white text-[13px] font-medium hover:bg-[#1d4ed8] transition-colors"
                >Edit in Photova</button>
                <span class="text-[#8b949e] text-sm bg-black/60 px-3 py-1.5 rounded" x-text="(lightboxIndex + 1) + ' / ' + imageAssets.length"></span>
            </div>
        </div>
    </template>

    <!-- Create Folder Modal -->
    <template x-if="showCreateFolder">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showCreateFolder = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-md p-6" x-init="$nextTick(() => $refs.folderNameInput.focus())">
                <h3 class="text-lg font-medium text-[#c9d1d9] mb-4">Create New Folder</h3>
                <input
                    x-model="newFolderName"
                    @keydown.enter="createFolder()"
                    type="text"
                    placeholder="Folder name"
                    class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm focus:outline-none focus:border-[#58a6ff] mb-4"
                    x-ref="folderNameInput"
                >
                <div class="flex justify-end gap-3">
                    <button
                        @click="showCreateFolder = false; newFolderName = ''"
                        class="px-4 py-2 text-sm text-[#c9d1d9] hover:bg-[#21262d] rounded-md transition-colors"
                    >Cancel</button>
                    <button
                        @click="createFolder()"
                        :disabled="!newFolderName.trim()"
                        class="px-4 py-2 bg-[#2563eb] text-white text-sm font-medium rounded-md hover:bg-[#1d4ed8] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >Create</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Move to Folder Modal -->
    <template x-if="showMoveModal">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showMoveModal = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-md p-6">
                <h3 class="text-lg font-medium text-[#c9d1d9] mb-4">Move to Folder</h3>
                <div class="max-h-64 overflow-y-auto mb-4 space-y-1">
                    <button
                        @click="moveAssetToFolder(null)"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-left hover:bg-[#21262d] transition-colors"
                        :class="moveTargetFolder === null ? 'bg-[#21262d]' : ''"
                    >
                        <svg class="w-5 h-5 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-[#c9d1d9] text-sm">Root (Files)</span>
                    </button>
                    <template x-for="folder in allFolders" :key="folder.id">
                        <button
                            @click="moveAssetToFolder(folder.id)"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-left hover:bg-[#21262d] transition-colors"
                            :class="moveTargetFolder === folder.id ? 'bg-[#21262d]' : ''"
                        >
                            <svg class="w-5 h-5 text-[#8b949e]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                            </svg>
                            <span class="text-[#c9d1d9] text-sm" x-text="folder.name"></span>
                        </button>
                    </template>
                </div>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showMoveModal = false; assetToMove = null"
                        class="px-4 py-2 text-sm text-[#c9d1d9] hover:bg-[#21262d] rounded-md transition-colors"
                    >Cancel</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Tag Manager Modal -->
    <template x-if="showTagManager">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showTagManager = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-[#c9d1d9]">Manage Tags</h3>
                    <button @click="showTagManager = false" class="text-[#8b949e] hover:text-[#c9d1d9]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Create new tag -->
                <div class="flex gap-2 mb-4">
                    <input
                        type="text"
                        x-model="newTagName"
                        @keydown.enter="createTag()"
                        placeholder="New tag name..."
                        class="flex-1 px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm focus:outline-none focus:border-[#58a6ff]"
                    >
                    <div class="relative">
                        <input 
                            type="color" 
                            x-model="newTagColor" 
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        >
                        <div 
                            class="w-10 h-10 rounded-md border border-[#30363d] cursor-pointer"
                            :style="'background-color: ' + newTagColor"
                        ></div>
                    </div>
                    <button
                        @click="createTag()"
                        :disabled="!newTagName.trim()"
                        class="px-4 py-2 bg-[#2563eb] text-white text-sm font-medium rounded-md hover:bg-[#1d4ed8] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >Add</button>
                </div>

                <!-- Existing tags -->
                <div class="max-h-64 overflow-y-auto space-y-2">
                    <template x-if="tags.length === 0">
                        <p class="text-center text-[#8b949e] text-sm py-4">No tags yet. Create one above!</p>
                    </template>
                    <template x-for="tag in tags" :key="tag.id">
                        <div class="flex items-center justify-between p-2 rounded-md hover:bg-[#21262d] group">
                            <div class="flex items-center gap-2">
                                <span 
                                    class="w-4 h-4 rounded-full" 
                                    :style="'background-color: ' + tag.color"
                                ></span>
                                <span class="text-[#c9d1d9] text-sm" x-text="tag.name"></span>
                                <span class="text-[#8b949e] text-xs" x-text="'(' + (tag.assets_count || 0) + ' files)'"></span>
                            </div>
                            <button
                                @click="deleteTag(tag.id)"
                                class="p-1 text-[#f85149] opacity-0 group-hover:opacity-100 hover:bg-[#30363d] rounded transition-all"
                                title="Delete tag"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <!-- Tag Asset Modal -->
    <template x-if="showTagAssetModal && assetToTag">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showTagAssetModal = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-[#c9d1d9]">Edit Tags</h3>
                    <button @click="showTagAssetModal = false" class="text-[#8b949e] hover:text-[#c9d1d9]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <p class="text-[#8b949e] text-sm mb-4 truncate">
                    File: <span class="text-[#c9d1d9]" x-text="assetToTag.filename"></span>
                </p>

                <!-- Tags list -->
                <div class="max-h-64 overflow-y-auto space-y-2 mb-4">
                    <template x-if="tags.length === 0">
                        <div class="text-center py-4">
                            <p class="text-[#8b949e] text-sm mb-2">No tags available.</p>
                            <button 
                                @click="showTagAssetModal = false; showTagManager = true"
                                class="text-[#58a6ff] text-sm hover:underline"
                            >Create tags first</button>
                        </div>
                    </template>
                    <template x-for="tag in tags" :key="tag.id">
                        <div 
                            class="flex items-center gap-3 p-2 rounded-md hover:bg-[#21262d] cursor-pointer"
                            @click="toggleAssetTag(tag.id)"
                        >
                            <div 
                                class="w-4 h-4 rounded flex items-center justify-center shrink-0"
                                :style="assetTagIds.includes(tag.id) 
                                    ? 'background-color: #2563eb; border: 1px solid #2563eb;' 
                                    : 'background-color: transparent; border: 2px solid #484f58;'"
                            >
                                <svg 
                                    x-show="assetTagIds.includes(tag.id)" 
                                    class="w-3 h-3 text-white" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span 
                                class="w-3 h-3 rounded-full shrink-0" 
                                :style="'background-color: ' + tag.color"
                            ></span>
                            <span class="text-[#c9d1d9] text-sm" x-text="tag.name"></span>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        @click="showTagAssetModal = false"
                        class="px-4 py-2 text-sm text-[#c9d1d9] hover:bg-[#21262d] rounded-md transition-colors"
                    >Cancel</button>
                    <button
                        @click="saveAssetTags()"
                        class="px-4 py-2 bg-[#2563eb] text-white text-sm font-medium rounded-md hover:bg-[#1d4ed8] transition-colors"
                    >Save</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Asset Details Modal -->
    <template x-if="showDetailsModal && assetDetails">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showDetailsModal = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-[#c9d1d9]">Asset Details</h3>
                    <button @click="showDetailsModal = false" class="text-[#8b949e] hover:text-[#c9d1d9]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Thumbnail -->
                <div class="mb-4">
                    <template x-if="isImage(assetDetails)">
                        <img 
                            :src="'/api/assets/' + assetDetails.id + '?download=true'" 
                            :alt="assetDetails.filename"
                            class="w-full h-48 object-contain bg-[#0d1117] rounded-lg"
                        >
                    </template>
                    <template x-if="!isImage(assetDetails)">
                        <div class="w-full h-48 bg-[#0d1117] rounded-lg flex items-center justify-center">
                            <span class="text-6xl">📄</span>
                        </div>
                    </template>
                </div>

                <!-- File Info -->
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between items-center py-2 border-b border-[#30363d]">
                        <span class="text-[#8b949e] text-sm">Filename</span>
                        <span class="text-[#c9d1d9] text-sm font-medium truncate ml-4 max-w-[60%]" x-text="assetDetails.filename"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-[#30363d]">
                        <span class="text-[#8b949e] text-sm">Size</span>
                        <span class="text-[#c9d1d9] text-sm" x-text="formatFileSize(assetDetails.size)"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-[#30363d]">
                        <span class="text-[#8b949e] text-sm">Type</span>
                        <span class="text-[#c9d1d9] text-sm" x-text="assetDetails.mimeType || assetDetails.mime_type"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-[#30363d]">
                        <span class="text-[#8b949e] text-sm">Created</span>
                        <span class="text-[#c9d1d9] text-sm" x-text="formatDate(assetDetails.created || assetDetails.created_at)"></span>
                    </div>
                    <template x-if="assetDetails.updated || assetDetails.updated_at">
                        <div class="flex justify-between items-center py-2 border-b border-[#30363d]">
                            <span class="text-[#8b949e] text-sm">Updated</span>
                            <span class="text-[#c9d1d9] text-sm" x-text="formatDate(assetDetails.updated || assetDetails.updated_at)"></span>
                        </div>
                    </template>
                </div>

                <!-- Metadata Section -->
                <template x-if="assetDetails.metadata && Object.keys(assetDetails.metadata).length > 0">
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-[#c9d1d9] mb-2">Metadata</h4>
                        <div class="bg-[#0d1117] rounded-lg p-3 space-y-2">
                            <template x-if="assetDetails.metadata.caption">
                                <div>
                                    <span class="text-[#8b949e] text-xs uppercase tracking-wide">AI Caption</span>
                                    <p class="text-[#c9d1d9] text-sm mt-1" x-text="assetDetails.metadata.caption"></p>
                                </div>
                            </template>
                            <template x-for="(value, key) in assetDetails.metadata" :key="key">
                                <template x-if="key !== 'caption'">
                                    <div>
                                        <span class="text-[#8b949e] text-xs uppercase tracking-wide" x-text="key"></span>
                                        <p class="text-[#c9d1d9] text-sm mt-1" x-text="typeof value === 'object' ? JSON.stringify(value) : value"></p>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- No Metadata -->
                <template x-if="!assetDetails.metadata || Object.keys(assetDetails.metadata).length === 0">
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-[#c9d1d9] mb-2">Metadata</h4>
                        <div class="bg-[#0d1117] rounded-lg p-3">
                            <p class="text-[#8b949e] text-sm text-center">No metadata available</p>
                        </div>
                    </div>
                </template>

                <!-- Tags -->
                <template x-if="assetDetails.tags && assetDetails.tags.length > 0">
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-[#c9d1d9] mb-2">Tags</h4>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="tag in assetDetails.tags" :key="tag.id">
                                <span 
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                    :style="'background-color: ' + tag.color + '20; color: ' + tag.color"
                                    x-text="tag.name"
                                ></span>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="flex justify-end">
                    <button
                        @click="showDetailsModal = false"
                        class="px-4 py-2 text-sm text-[#c9d1d9] hover:bg-[#21262d] rounded-md transition-colors"
                    >Close</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Confirm Modal -->
    <template x-if="confirmModal.show">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="confirmModal.show = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-[#f8514926] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#f85149]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-[#c9d1d9] mb-1" x-text="confirmModal.title"></h3>
                        <p class="text-sm text-[#8b949e]" x-text="confirmModal.message"></p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="confirmModal.show = false"
                        class="px-4 py-2 text-sm text-[#c9d1d9] hover:bg-[#21262d] rounded-md transition-colors"
                    >Cancel</button>
                    <button
                        @click="confirmModal.onConfirm && confirmModal.onConfirm(); confirmModal.show = false"
                        class="px-4 py-2 text-white text-sm font-medium rounded-md transition-colors"
                        :class="confirmModal.confirmClass"
                        x-text="confirmModal.confirmText"
                    ></button>
                </div>
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
            folders: [],
            allFolders: [],
            breadcrumbs: [],
            loading: true,
            uploading: false,
            dragOver: false,
            lightboxIndex: null,
            viewMode: 'grid',
            currentFolderId: null,
            showCreateFolder: false,
            newFolderName: '',
            showMoveModal: false,
            assetToMove: null,
            moveTargetFolder: null,
            // Search & Tags
            searchQuery: '',
            tags: [],
            selectedTags: [],
            showTagManager: false,
            newTagName: '',
            newTagColor: '#58a6ff',
            showTagAssetModal: false,
            assetToTag: null,
            assetTagIds: [],
            // Details modal
            showDetailsModal: false,
            assetDetails: null,
            // Drag and drop
            draggingAssetId: null,
            dropTargetFolderId: null,
            // Confirm modal
            confirmModal: {
                show: false,
                title: '',
                message: '',
                confirmText: 'Delete',
                confirmClass: 'bg-[#f85149] hover:bg-[#da3633]',
                onConfirm: null
            },
            // Multi-select
            selectedAssets: [],
            showBulkMoveModal: false,

            get imageAssets() {
                return this.assets.filter(a => this.isImage(a));
            },

            get parentFolderId() {
                // Returns the parent folder ID when inside a subfolder
                // null means root, undefined means we're already at root
                if (!this.currentFolderId) return undefined;
                if (this.breadcrumbs.length <= 1) return null;
                return this.breadcrumbs[this.breadcrumbs.length - 2].id;
            },

            showConfirm({ title, message, confirmText = 'Delete', confirmClass = 'bg-[#f85149] hover:bg-[#da3633]', onConfirm }) {
                this.confirmModal = {
                    show: true,
                    title,
                    message,
                    confirmText,
                    confirmClass,
                    onConfirm
                };
            },

            async init() {
                this.viewMode = localStorage.getItem('assetsViewMode') || 'grid';
                
                // Restore state from URL
                this.readStateFromUrl();
                
                // Listen for back/forward navigation
                window.addEventListener('popstate', async () => {
                    this.readStateFromUrl();
                    await this.updateBreadcrumbs();
                    await this.loadContent();
                });
                
                await this.loadTags();
                await this.updateBreadcrumbs();
                await this.loadContent();
            },

            readStateFromUrl() {
                const params = new URLSearchParams(window.location.search);
                this.currentFolderId = params.get('folder') || null;
                this.searchQuery = params.get('search') || '';
                const tagsParam = params.get('tags');
                this.selectedTags = tagsParam ? tagsParam.split(',') : [];
            },

            updateUrl(replace = false) {
                const params = new URLSearchParams();
                if (this.currentFolderId) {
                    params.set('folder', this.currentFolderId);
                }
                if (this.searchQuery.trim()) {
                    params.set('search', this.searchQuery.trim());
                }
                if (this.selectedTags.length > 0) {
                    params.set('tags', this.selectedTags.join(','));
                }
                const newUrl = params.toString() 
                    ? `${window.location.pathname}?${params.toString()}`
                    : window.location.pathname;
                
                if (replace) {
                    history.replaceState({}, '', newUrl);
                } else {
                    history.pushState({}, '', newUrl);
                }
            },

            setViewMode(mode) {
                this.viewMode = mode;
                localStorage.setItem('assetsViewMode', mode);
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
                    month: 'short', day: 'numeric', year: 'numeric'
                });
            },

            async loadContent() {
                this.loading = true;
                await Promise.all([this.loadFolders(), this.loadAssets(), this.loadAllFolders()]);
                this.loading = false;
            },

            async loadFolders() {
                try {
                    const parentParam = this.currentFolderId ? `parent_id=${this.currentFolderId}` : 'parent_id=';
                    const res = await window.apiFetch(`/api/folders?${parentParam}`);
                    if (res.ok) {
                        const data = await res.json();
                        this.folders = data.folders || [];
                    }
                } catch (e) {
                    console.error('Failed to load folders:', e);
                }
            },

            async loadAssets() {
                try {
                    const params = new URLSearchParams();
                    // Search and tag filtering are global (ignore folder context)
                    const isGlobalSearch = this.searchQuery.trim() || this.selectedTags.length > 0;
                    if (!isGlobalSearch) {
                        if (this.currentFolderId) {
                            params.set('folder_id', this.currentFolderId);
                        } else {
                            params.set('folder_id', 'root');
                        }
                    }
                    if (this.searchQuery.trim()) {
                        params.set('search', this.searchQuery.trim());
                    }
                    if (this.selectedTags.length > 0) {
                        params.set('tags', this.selectedTags.join(','));
                    }
                    const res = await window.apiFetch(`/api/assets?${params.toString()}`);
                    if (res.ok) {
                        const data = await res.json();
                        this.assets = data.assets || [];
                    }
                } catch (e) {
                    console.error('Failed to load assets:', e);
                }
            },

            async loadAllFolders() {
                try {
                    const res = await window.apiFetch('/api/folders');
                    if (res.ok) {
                        const data = await res.json();
                        this.allFolders = data.folders || [];
                    }
                } catch (e) {
                    console.error('Failed to load all folders:', e);
                }
            },

            async navigateToFolder(folderId) {
                this.currentFolderId = folderId;
                this.updateUrl();
                await this.updateBreadcrumbs();
                await this.loadContent();
            },

            async updateBreadcrumbs() {
                this.breadcrumbs = [];
                if (!this.currentFolderId) return;

                let currentId = this.currentFolderId;
                const crumbs = [];

                while (currentId) {
                    try {
                        const res = await window.apiFetch(`/api/folders/${currentId}`);
                        if (res.ok) {
                            const data = await res.json();
                            crumbs.unshift({ id: data.folder.id, name: data.folder.name });
                            currentId = data.folder.parentId;
                        } else {
                            break;
                        }
                    } catch (e) {
                        break;
                    }
                }

                this.breadcrumbs = crumbs;
            },

            async createFolder() {
                if (!this.newFolderName.trim()) return;

                try {
                    const res = await window.apiFetch('/api/folders', {
                        method: 'POST',
                        body: JSON.stringify({
                            name: this.newFolderName.trim(),
                            parent_id: this.currentFolderId
                        })
                    });

                    if (res.ok) {
                        this.showCreateFolder = false;
                        this.newFolderName = '';
                        await this.loadContent();
                        this.$dispatch('toast', { message: 'Folder created', type: 'success' });
                    } else {
                        const error = await res.json();
                        throw new Error(error.error || 'Failed to create folder');
                    }
                } catch (e) {
                    console.error('Create folder failed:', e);
                    this.$dispatch('toast', { message: e.message || 'Failed to create folder', type: 'error' });
                }
            },

            deleteFolder(id) {
                this.showConfirm({
                    title: 'Delete folder?',
                    message: 'All contents will be moved to the parent folder.',
                    confirmText: 'Delete',
                    onConfirm: async () => {
                        try {
                            await window.apiFetch(`/api/folders/${id}`, { method: 'DELETE' });
                            await this.loadContent();
                            this.$dispatch('toast', { message: 'Folder deleted', type: 'success' });
                        } catch (e) {
                            console.error('Delete folder failed:', e);
                            this.$dispatch('toast', { message: 'Failed to delete folder', type: 'error' });
                        }
                    }
                });
            },

            openMoveModal(assetId) {
                this.assetToMove = assetId;
                this.moveTargetFolder = null;
                this.showMoveModal = true;
            },

            async moveAssetToFolder(folderId) {
                if (!this.assetToMove) return;

                try {
                    const res = await window.apiFetch('/api/assets/move', {
                        method: 'POST',
                        body: JSON.stringify({
                            asset_ids: [this.assetToMove],
                            folder_id: folderId
                        })
                    });

                    if (res.ok) {
                        this.showMoveModal = false;
                        this.assetToMove = null;
                        await this.loadContent();
                        this.$dispatch('toast', { message: 'File moved', type: 'success' });
                    } else {
                        const error = await res.json();
                        throw new Error(error.error || 'Failed to move file');
                    }
                } catch (e) {
                    console.error('Move failed:', e);
                    this.$dispatch('toast', { message: e.message || 'Failed to move file', type: 'error' });
                }
            },

            async uploadFile(file) {
                this.uploading = true;
                try {
                    const formData = new FormData();
                    formData.append('file', file);
                    if (this.currentFolderId) {
                        formData.append('folder_id', this.currentFolderId);
                    }

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
                    this.$dispatch('toast', { message: 'File uploaded', type: 'success' });
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

            deleteAsset(id) {
                const asset = this.assets.find(a => a.id === id);
                this.showConfirm({
                    title: 'Delete file?',
                    message: asset ? `"${asset.filename}" will be permanently deleted.` : 'This file will be permanently deleted.',
                    confirmText: 'Delete',
                    onConfirm: async () => {
                        try {
                            await window.apiFetch(`/api/assets/${id}`, { method: 'DELETE' });
                            await this.loadAssets();
                            this.$dispatch('toast', { message: 'File deleted', type: 'success' });
                        } catch (e) {
                            console.error('Delete failed:', e);
                            this.$dispatch('toast', { message: 'Delete failed', type: 'error' });
                        }
                    }
                });
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

            downloadAsset(id) {
                const asset = this.assets.find(a => a.id === id);
                const link = document.createElement('a');
                link.href = `/api/assets/${id}?download=true`;
                link.download = asset?.filename || 'download';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
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
            },

            // Search
            async searchAssets() {
                this.updateUrl(true); // replace to avoid flooding history while typing
                await this.loadAssets();
            },

            // Tags
            async loadTags() {
                try {
                    const res = await window.apiFetch('/api/tags');
                    if (res.ok) {
                        const data = await res.json();
                        this.tags = data.tags || [];
                    }
                } catch (e) {
                    console.error('Failed to load tags:', e);
                }
            },

            getTagById(id) {
                return this.tags.find(t => t.id === id);
            },

            toggleTagFilter(tagId) {
                const idx = this.selectedTags.indexOf(tagId);
                if (idx === -1) {
                    this.selectedTags.push(tagId);
                } else {
                    this.selectedTags.splice(idx, 1);
                }
                this.updateUrl();
                this.loadAssets();
            },

            clearFilters() {
                this.searchQuery = '';
                this.selectedTags = [];
                this.updateUrl();
                this.loadAssets();
            },

            async createTag() {
                if (!this.newTagName.trim()) return;
                try {
                    const res = await window.apiFetch('/api/tags', {
                        method: 'POST',
                        body: JSON.stringify({
                            name: this.newTagName.trim(),
                            color: this.newTagColor
                        })
                    });
                    if (res.ok) {
                        this.newTagName = '';
                        this.newTagColor = '#58a6ff';
                        await this.loadTags();
                        this.$dispatch('toast', { message: 'Tag created', type: 'success' });
                    } else {
                        const error = await res.json();
                        throw new Error(error.error || 'Failed to create tag');
                    }
                } catch (e) {
                    console.error('Create tag failed:', e);
                    this.$dispatch('toast', { message: e.message || 'Failed to create tag', type: 'error' });
                }
            },

            deleteTag(id) {
                const tag = this.tags.find(t => t.id === id);
                this.showConfirm({
                    title: 'Delete tag?',
                    message: tag ? `"${tag.name}" will be removed from all files.` : 'This tag will be removed from all files.',
                    confirmText: 'Delete',
                    onConfirm: async () => {
                        try {
                            const res = await window.apiFetch(`/api/tags/${id}`, { method: 'DELETE' });
                            if (res.ok) {
                                await this.loadTags();
                                this.selectedTags = this.selectedTags.filter(t => t !== id);
                                await this.loadAssets();
                                this.$dispatch('toast', { message: 'Tag deleted', type: 'success' });
                            }
                        } catch (e) {
                            console.error('Delete tag failed:', e);
                            this.$dispatch('toast', { message: 'Failed to delete tag', type: 'error' });
                        }
                    }
                });
            },

            openTagAssetModal(asset) {
                this.assetToTag = asset;
                this.assetTagIds = (asset.tags || []).map(t => t.id);
                this.showTagAssetModal = true;
            },

            openDetailsModal(asset) {
                this.assetDetails = asset;
                this.showDetailsModal = true;
            },

            toggleAssetTag(tagId) {
                const idx = this.assetTagIds.indexOf(tagId);
                if (idx === -1) {
                    this.assetTagIds.push(tagId);
                } else {
                    this.assetTagIds.splice(idx, 1);
                }
            },

            async saveAssetTags() {
                if (!this.assetToTag) return;
                try {
                    const res = await window.apiFetch(`/api/assets/${this.assetToTag.id}/tags`, {
                        method: 'POST',
                        body: JSON.stringify({ tag_ids: this.assetTagIds })
                    });
                    if (res.ok) {
                        this.showTagAssetModal = false;
                        this.assetToTag = null;
                        await this.loadTags();
                        await this.loadAssets();
                        this.$dispatch('toast', { message: 'Tags updated', type: 'success' });
                    } else {
                        const error = await res.json();
                        throw new Error(error.error || 'Failed to update tags');
                    }
                } catch (e) {
                    console.error('Save tags failed:', e);
                    this.$dispatch('toast', { message: e.message || 'Failed to update tags', type: 'error' });
                }
            },

            // Drag and drop for moving files to folders
            startDragAsset(event, assetId) {
                this.draggingAssetId = assetId;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', assetId);
            },

            endDragAsset() {
                this.draggingAssetId = null;
                this.dropTargetFolderId = null;
            },

            async handleDropOnFolder(folderId) {
                const assetId = this.draggingAssetId;
                this.endDragAsset();
                
                if (!assetId) return;
                
                try {
                    const res = await window.apiFetch('/api/assets/move', {
                        method: 'POST',
                        body: JSON.stringify({
                            asset_ids: [assetId],
                            folder_id: folderId
                        })
                    });

                    if (res.ok) {
                        await this.loadContent();
                        this.$dispatch('toast', { message: 'File moved', type: 'success' });
                    } else {
                        const error = await res.json();
                        throw new Error(error.error || 'Failed to move file');
                    }
                } catch (e) {
                    console.error('Move failed:', e);
                    this.$dispatch('toast', { message: e.message || 'Failed to move file', type: 'error' });
                }
            },

            // Multi-select methods
            isSelected(assetId) {
                return this.selectedAssets.includes(assetId);
            },

            toggleSelect(assetId, event) {
                if (event) event.stopPropagation();
                const idx = this.selectedAssets.indexOf(assetId);
                if (idx === -1) {
                    this.selectedAssets.push(assetId);
                } else {
                    this.selectedAssets.splice(idx, 1);
                }
            },

            selectAll() {
                this.selectedAssets = this.assets.map(a => a.id);
            },

            deselectAll() {
                this.selectedAssets = [];
            },

            get allSelected() {
                return this.assets.length > 0 && this.selectedAssets.length === this.assets.length;
            },

            get someSelected() {
                return this.selectedAssets.length > 0 && this.selectedAssets.length < this.assets.length;
            },

            openBulkMoveModal() {
                this.moveTargetFolder = null;
                this.showBulkMoveModal = true;
            },

            async bulkMoveToFolder(folderId) {
                if (this.selectedAssets.length === 0) return;

                try {
                    const res = await window.apiFetch('/api/assets/move', {
                        method: 'POST',
                        body: JSON.stringify({
                            asset_ids: this.selectedAssets,
                            folder_id: folderId
                        })
                    });

                    if (res.ok) {
                        const count = this.selectedAssets.length;
                        this.showBulkMoveModal = false;
                        this.selectedAssets = [];
                        await this.loadContent();
                        this.$dispatch('toast', { message: `${count} file${count > 1 ? 's' : ''} moved`, type: 'success' });
                    } else {
                        const error = await res.json();
                        throw new Error(error.error || 'Failed to move files');
                    }
                } catch (e) {
                    console.error('Bulk move failed:', e);
                    this.$dispatch('toast', { message: e.message || 'Failed to move files', type: 'error' });
                }
            },

            bulkDelete() {
                const count = this.selectedAssets.length;
                this.showConfirm({
                    title: `Delete ${count} file${count > 1 ? 's' : ''}?`,
                    message: 'These files will be permanently deleted.',
                    confirmText: 'Delete',
                    onConfirm: async () => {
                        try {
                            // Delete all selected assets
                            await Promise.all(
                                this.selectedAssets.map(id => 
                                    window.apiFetch(`/api/assets/${id}`, { method: 'DELETE' })
                                )
                            );
                            this.selectedAssets = [];
                            await this.loadAssets();
                            this.$dispatch('toast', { message: `${count} file${count > 1 ? 's' : ''} deleted`, type: 'success' });
                        } catch (e) {
                            console.error('Bulk delete failed:', e);
                            this.$dispatch('toast', { message: 'Failed to delete files', type: 'error' });
                        }
                    }
                });
            }
        }
    }
</script>
@endsection
