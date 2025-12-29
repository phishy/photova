@extends('dashboard.layout')

@section('title', 'Playground')

@section('content')
<div x-data="playgroundPage()" x-init="loadKeys()" @keydown.escape.window="showSaveModal = false" class="flex flex-col h-[calc(100vh-64px)]">
    <div class="flex justify-between items-center mb-4 flex-shrink-0 gap-4 flex-wrap">
        <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9]">Playground</h1>
        <div class="flex items-center gap-3">
            <label class="text-[13px] text-[#8b949e]">API Key:</label>
            <template x-if="keys.length > 0">
                <select
                    x-model="selectedKeyId"
                    @change="handleKeyChange()"
                    class="px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-[13px] min-w-[200px] outline-none"
                >
                    <template x-for="key in keys" :key="key.id">
                        <option :value="key.id" x-text="key.name + ' (' + (key.keyPrefix || key.key_prefix) + ')'"></option>
                    </template>
                </select>
            </template>
            <template x-if="keys.length === 0 && !loading">
                <span class="text-[13px] text-[#8b949e]">No API keys</span>
            </template>
            <a
                href="/dashboard/keys"
                class="px-3.5 py-2 bg-[#2563eb] hover:bg-[#1d4ed8] rounded-md text-white text-[13px] font-medium transition-colors"
            >
                Manage Keys
            </a>
        </div>
    </div>

    <!-- Loading State -->
    <template x-if="loading">
        <div class="flex-1 flex items-center justify-center bg-[#161b22] rounded-md border border-[#30363d]">
            <div class="text-[#8b949e] text-sm">Loading...</div>
        </div>
    </template>

    <!-- No Keys State -->
    <template x-if="!loading && !selectedKey">
        <div class="flex-1 flex items-center justify-center bg-[#161b22] rounded-md border border-[#30363d]">
            <div class="text-center p-8 max-w-md">
                <h2 class="text-lg font-semibold text-[#c9d1d9] mb-3">No API Keys Found</h2>
                <p class="text-[#8b949e] text-sm mb-6 leading-relaxed">
                    Create an API key to use the editor with AI features.
                </p>
                <a
                    href="/dashboard/keys"
                    class="inline-block px-5 py-2.5 bg-[#2563eb] hover:bg-[#1d4ed8] text-white rounded-md text-sm font-medium transition-colors"
                >
                    Create API Key
                </a>
            </div>
        </div>
    </template>

    <!-- Key Needs Refresh State -->
    <template x-if="!loading && selectedKey && !selectedKey.key">
        <div class="flex-1 flex items-center justify-center bg-[#161b22] rounded-md border border-[#30363d]">
            <div class="text-center p-8 max-w-md">
                <h2 class="text-lg font-semibold text-[#c9d1d9] mb-3">Key Needs Refresh</h2>
                <p class="text-[#8b949e] text-sm mb-6 leading-relaxed">
                    This API key was created before we started storing full keys.
                    Please delete it and create a new one to use in the Playground.
                </p>
                <a
                    href="/dashboard/keys"
                    class="inline-block px-5 py-2.5 bg-[#2563eb] hover:bg-[#1d4ed8] text-white rounded-md text-sm font-medium transition-colors"
                >
                    Manage API Keys
                </a>
            </div>
        </div>
    </template>

    <!-- Editor Container -->
    <template x-if="!loading && selectedKey && selectedKey.key">
        <div
            x-ref="editorContainer"
            class="flex-1 min-h-0 bg-[#161b22] rounded-md border border-[#30363d] overflow-hidden"
        ></div>
    </template>

    <!-- Save Modal -->
    <template x-if="showSaveModal">
        <div class="fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center p-4" @click.self="showSaveModal = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-sm p-6">
                <h3 class="text-lg font-medium text-[#c9d1d9] mb-2">Save Image</h3>
                <p class="text-sm text-[#8b949e] mb-6">You're editing an existing asset. How would you like to save?</p>
                
                <div class="flex flex-col gap-3">
                    <button
                        @click="confirmSave('update')"
                        :disabled="saving"
                        class="w-full px-4 py-3 bg-[#2563eb] hover:bg-[#1d4ed8] disabled:opacity-50 text-white text-sm font-medium rounded-md transition-colors text-left"
                    >
                        <div class="font-medium">Update existing</div>
                        <div class="text-xs text-white/70 mt-0.5">Replace the original image</div>
                    </button>
                    <button
                        @click="confirmSave('new')"
                        :disabled="saving"
                        class="w-full px-4 py-3 bg-[#21262d] hover:bg-[#30363d] disabled:opacity-50 border border-[#30363d] text-[#c9d1d9] text-sm font-medium rounded-md transition-colors text-left"
                    >
                        <div class="font-medium">Save as new</div>
                        <div class="text-xs text-[#8b949e] mt-0.5">Create a new copy</div>
                    </button>
                </div>

                <button
                    @click="showSaveModal = false"
                    class="w-full mt-4 px-4 py-2 text-sm text-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                >Cancel</button>
            </div>
        </div>
    </template>

    <!-- Save Success Modal -->
    <template x-if="showSaveSuccessModal">
        <div class="fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center p-4" @click.self="showSaveSuccessModal = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-sm p-6 text-center">
                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-[#238636]/20 flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#3fb950]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-[#c9d1d9] mb-2">Saved!</h3>
                <p class="text-sm text-[#8b949e] mb-6">Your image has been saved successfully.</p>
                
                <div class="flex flex-col gap-3">
                    <button
                        @click="goBackToFiles()"
                        class="w-full px-4 py-2.5 bg-[#2563eb] hover:bg-[#1d4ed8] text-white text-sm font-medium rounded-md transition-colors"
                    >Back to Files</button>
                    <button
                        @click="showSaveSuccessModal = false"
                        class="w-full px-4 py-2.5 text-sm text-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                    >Continue Editing</button>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
    function playgroundPage() {
        return {
            keys: [],
            selectedKeyId: '',
            loading: true,
            editorLoaded: false,
            editorInstance: null,
            showSaveModal: false,
            showSaveSuccessModal: false,
            saving: false,
            pendingSaveData: null,
            currentAssetId: null,
            referrerUrl: null,

            get selectedKey() {
                return this.keys.find(k => k.id === this.selectedKeyId);
            },

            async loadKeys() {
                const ref = document.referrer;
                if (ref && ref.includes('/dashboard')) {
                    this.referrerUrl = ref;
                }

                try {
                    const res = await window.apiFetch('/api/keys');
                    if (res.ok) {
                        const data = await res.json();
                        const activeKeys = (data.keys || []).filter(k => k.status === 'active');
                        this.keys = activeKeys;

                        const lastUsed = localStorage.getItem('playground-last-key-id');
                        if (lastUsed && activeKeys.some(k => k.id === lastUsed)) {
                            this.selectedKeyId = lastUsed;
                        } else if (activeKeys.length > 0) {
                            this.selectedKeyId = activeKeys[0].id;
                        }
                    }
                } catch (e) {
                    console.error('Failed to load keys:', e);
                }
                this.loading = false;

                this.$nextTick(() => {
                    if (this.selectedKey?.key) {
                        this.initEditor();
                    }
                });
            },

            handleKeyChange() {
                this.editorLoaded = false;
                this.editorInstance = null;
                if (this.$refs.editorContainer) {
                    this.$refs.editorContainer.innerHTML = '';
                }
                this.$nextTick(() => {
                    if (this.selectedKey?.key) {
                        localStorage.setItem('playground-last-key-id', this.selectedKey.id);
                        this.initEditor();
                    }
                });
            },

            async initEditor() {
                if (!this.selectedKey?.key || !this.$refs.editorContainer || this.editorLoaded) return;

                try {
                    const { EditorUI } = window.Brighten;

                    this.$refs.editorContainer.innerHTML = '';

                    const urlParams = new URLSearchParams(window.location.search);
                    const assetId = urlParams.get('asset');
                    this.currentAssetId = assetId;

                    const self = this;

                    const editor = new EditorUI({
                        container: this.$refs.editorContainer,
                        theme: 'dark',
                        apiEndpoint: window.location.origin,
                        apiKey: this.selectedKey.key,
                        showHeader: true,
                        showSidebar: true,
                        showPanel: true,
                        image: assetId
                            ? `/api/assets/${assetId}?download=true`
                            : 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1600&q=80',
                        onSave: async (blob, metadata) => {
                            const reader = new FileReader();
                            const base64 = await new Promise((resolve) => {
                                reader.onloadend = () => resolve(reader.result);
                                reader.readAsDataURL(blob);
                            });

                            self.pendingSaveData = { base64, metadata };

                            if (self.currentAssetId) {
                                self.showSaveModal = true;
                                return new Promise((resolve, reject) => {
                                    self.pendingSaveResolve = resolve;
                                    self.pendingSaveReject = reject;
                                });
                            } else {
                                return self.saveAsNew(base64, metadata);
                            }
                        }
                    });

                    this.editorInstance = editor;
                    this.editorLoaded = true;
                } catch (e) {
                    console.error('Failed to load editor:', e);
                }
            },

            async confirmSave(mode) {
                if (!this.pendingSaveData) return;

                this.saving = true;
                try {
                    const { base64, metadata } = this.pendingSaveData;
                    let result;

                    if (mode === 'update') {
                        result = await this.updateExisting(base64, metadata);
                    } else {
                        result = await this.saveAsNew(base64, metadata);
                    }

                    this.showSaveModal = false;
                    
                    if (this.referrerUrl) {
                        this.showSaveSuccessModal = true;
                    }

                    if (this.pendingSaveResolve) {
                        this.pendingSaveResolve(result);
                    }
                } catch (e) {
                    if (this.pendingSaveReject) {
                        this.pendingSaveReject(e);
                    }
                } finally {
                    this.saving = false;
                    this.pendingSaveData = null;
                    this.pendingSaveResolve = null;
                    this.pendingSaveReject = null;
                }
            },

            goBackToFiles() {
                window.location.href = this.referrerUrl || '/dashboard';
            },

            async updateExisting(base64, metadata) {
                const payload = { image: base64 };
                if (metadata?.caption) {
                    payload.metadata = { caption: metadata.caption };
                }

                const res = await fetch(`/api/assets/${this.currentAssetId}`, {
                    method: 'PATCH',
                    body: JSON.stringify(payload),
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (!res.ok) {
                    const error = await res.json();
                    throw new Error(error.error || 'Failed to update');
                }

                const data = await res.json();
                return { id: data.asset?.id, url: data.asset?.url };
            },

            async saveAsNew(base64, metadata) {
                const filename = this.currentAssetId 
                    ? `edited-${this.currentAssetId}.png` 
                    : `edited-${Date.now()}.png`;

                const payload = { image: base64, filename };
                if (metadata?.caption) {
                    payload.metadata = { caption: metadata.caption };
                }

                const res = await fetch('/api/assets', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (!res.ok) {
                    const error = await res.json();
                    throw new Error(error.error || 'Failed to save');
                }

                const data = await res.json();
                return { id: data.asset?.id, url: data.asset?.url };
            }
        }
    }
</script>
@endsection
