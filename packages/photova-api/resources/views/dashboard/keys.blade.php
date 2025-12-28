@extends('dashboard.layout')

@section('title', 'API Keys')

@section('content')
<div x-data="keysPage()" x-init="loadKeys()">
    <h1 class="text-2xl font-semibold tracking-tight mb-8">API Keys</h1>

    <!-- Create Key Form -->
    <div class="flex gap-3 mb-6">
        <input
            type="text"
            x-model="newKeyName"
            placeholder="Key name (optional)"
            class="flex-1 px-3.5 py-2.5 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
        >
        <button
            @click="createKey"
            class="px-5 py-2.5 bg-[#2563eb] border border-[#2563eb] rounded-md text-white text-sm font-medium hover:bg-[#1d4ed8] hover:border-[#1d4ed8] transition-colors"
        >
            Create key
        </button>
    </div>

    <!-- New Key Banner -->
    <div x-show="createdKey" x-cloak class="bg-[#2563eb]/15 border border-[#2563eb] rounded-md p-5 mb-6">
        <div class="text-[13px] text-[#8b949e] mb-3">
            Your new API key (copy it now — you won't see it again)
        </div>
        <code class="block p-3.5 bg-[#0d1117] border border-[#30363d] rounded-md font-mono text-[13px] text-[#c9d1d9] mb-3 break-all" x-text="createdKey"></code>
        <button
            @click="navigator.clipboard.writeText(createdKey); copySuccess = true; setTimeout(() => copySuccess = false, 2000)"
            class="px-4 py-2 bg-[#2563eb] hover:bg-[#1d4ed8] rounded-md text-white text-[13px] font-medium transition-colors"
            x-text="copySuccess ? 'Copied!' : 'Copy'"
        ></button>
    </div>

    <!-- Keys List -->
    <div class="bg-[#161b22] rounded-md border border-[#30363d] overflow-hidden">
        <template x-if="keys.length === 0">
            <div class="p-12 text-center text-[#8b949e] text-sm">
                No API keys yet. Create one to get started.
            </div>
        </template>

        <template x-for="(key, index) in keys" :key="key.id">
            <div class="flex items-center p-4" :class="index < keys.length - 1 ? 'border-b border-[#21262d]' : ''">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-[#c9d1d9] mb-1" x-text="key.name"></div>
                    <code class="text-xs text-[#8b949e] font-mono" x-text="key.keyPrefix || key.key_prefix"></code>
                </div>
                <div
                    class="px-2.5 py-1 rounded-full text-[11px] font-medium uppercase tracking-wide mr-3"
                    :class="key.status === 'active' ? 'bg-[#388bfd26] text-[#58a6ff]' : 'bg-[#6e768126] text-[#8b949e]'"
                    x-text="key.status"
                ></div>
                <template x-if="key.status === 'active'">
                    <button
                        @click="revokeKey(key.id)"
                        class="px-3 py-1.5 bg-transparent border border-[#30363d] rounded-md text-[#8b949e] text-xs mr-2 hover:border-[#8b949e] hover:text-[#c9d1d9] transition-colors"
                    >
                        Revoke
                    </button>
                </template>
                <button
                    @click="deleteKey(key.id)"
                    class="px-3 py-1.5 bg-transparent border border-[#f8514966] rounded-md text-[#f85149] text-xs hover:bg-[#f8514919] transition-colors"
                >
                    Delete
                </button>
            </div>
        </template>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function keysPage() {
        return {
            keys: [],
            newKeyName: '',
            createdKey: null,
            copySuccess: false,

            async loadKeys() {
                try {
                    const res = await window.apiFetch('/api/keys');
                    if (res.ok) {
                        const data = await res.json();
                        this.keys = data.keys || [];
                    }
                } catch (e) {
                    console.error('Failed to load keys:', e);
                }
            },

            async createKey() {
                try {
                    const res = await window.apiFetch('/api/keys', {
                        method: 'POST',
                        body: JSON.stringify({ name: this.newKeyName || 'API Key' }),
                    });
                    const data = await res.json();
                    this.createdKey = data.plainKey;
                    this.newKeyName = '';
                    this.loadKeys();
                } catch (e) {
                    console.error('Failed to create key:', e);
                }
            },

            async revokeKey(id) {
                try {
                    await window.apiFetch(`/api/keys/${id}`, {
                        method: 'PATCH',
                        body: JSON.stringify({ status: 'revoked' }),
                    });
                    this.loadKeys();
                } catch (e) {
                    console.error('Failed to revoke key:', e);
                }
            },

            async deleteKey(id) {
                try {
                    await window.apiFetch(`/api/keys/${id}`, {
                        method: 'DELETE',
                    });
                    this.loadKeys();
                } catch (e) {
                    console.error('Failed to delete key:', e);
                }
            }
        }
    }
</script>
@endsection
