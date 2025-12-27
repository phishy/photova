@extends('dashboard.layout')

@section('title', 'Playground')

@section('content')
<div x-data="playgroundPage()" x-init="loadKeys()" class="flex flex-col h-[calc(100vh-64px)]">
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
                class="px-3.5 py-2 bg-[#238636] rounded-md text-white text-[13px] font-medium"
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
                    class="inline-block px-5 py-2.5 bg-[#238636] text-white rounded-md text-sm font-medium"
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
                    class="inline-block px-5 py-2.5 bg-[#238636] text-white rounded-md text-sm font-medium"
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

            get selectedKey() {
                return this.keys.find(k => k.id === this.selectedKeyId);
            },

            async loadKeys() {
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

                // Initialize editor after keys are loaded
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
                        onSave: async (blob) => {
                            const reader = new FileReader();
                            const base64 = await new Promise((resolve) => {
                                reader.onloadend = () => resolve(reader.result);
                                reader.readAsDataURL(blob);
                            });

                            const filename = assetId ? `edited-${assetId}.png` : `edited-${Date.now()}.png`;

                            const res = await fetch('/api/assets', {
                                method: 'POST',
                                body: JSON.stringify({ image: base64, filename }),
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
                    });

                    this.editorInstance = editor;
                    this.editorLoaded = true;
                } catch (e) {
                    console.error('Failed to load editor:', e);
                }
            }
        }
    }
</script>
@endsection
