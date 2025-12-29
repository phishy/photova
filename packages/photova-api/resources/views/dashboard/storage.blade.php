@extends('dashboard.layout')

@section('title', 'Storage')

@section('content')
<div x-data="storagePage()" x-init="init()">
    <h1 class="text-2xl font-semibold tracking-tight mb-8">Storage</h1>

    <div x-show="!rcloneAvailable" x-cloak class="bg-amber-500/15 border border-amber-500 rounded-md p-4 mb-6">
        <div class="text-sm text-amber-400">
            <strong>Storage service unavailable.</strong> The rclone container is not running. You can still use platform storage.
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div>
            <h2 class="text-lg font-medium text-[#c9d1d9] mb-4">Storage Buckets</h2>
            
            <div class="bg-[#161b22] rounded-md border border-[#30363d] overflow-hidden mb-4">
                <div class="flex items-center p-4 border-b border-[#21262d]" :class="system.isDefault ? 'bg-[#388bfd0d]' : ''">
                    <div class="w-10 h-10 rounded-md bg-[#21262d] flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-[#c9d1d9]" x-text="system.name"></div>
                        <div class="text-xs text-[#8b949e]" x-text="system.assetsCount + ' assets'"></div>
                    </div>
                    <template x-if="system.isDefault">
                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-[#388bfd26] text-[#58a6ff] uppercase mr-2">Default</span>
                    </template>
                    <template x-if="!system.isDefault">
                        <button @click="setSystemDefault()" class="text-xs text-[#8b949e] hover:text-[#c9d1d9] transition-colors">
                            Set default
                        </button>
                    </template>
                </div>

                <template x-for="bucket in buckets" :key="bucket.id">
                    <div class="flex items-center p-4 border-b border-[#21262d] last:border-b-0" :class="bucket.isDefault ? 'bg-[#388bfd0d]' : ''">
                        <div class="w-10 h-10 rounded-md bg-[#21262d] flex items-center justify-center mr-3">
                            <span class="text-[11px] font-bold text-[#8b949e] uppercase" x-text="getProviderIcon(bucket.provider)"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-[#c9d1d9]" x-text="bucket.name"></div>
                            <div class="text-xs text-[#8b949e]">
                                <span x-text="getProviderName(bucket.provider)"></span>
                                <span class="mx-1">&middot;</span>
                                <span x-text="bucket.assetsCount + ' assets'"></span>
                            </div>
                        </div>
                        <template x-if="bucket.isDefault">
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-[#388bfd26] text-[#58a6ff] uppercase mr-2">Default</span>
                        </template>
                        <template x-if="!bucket.isDefault && bucket.isActive">
                            <button @click="setDefault(bucket.id)" class="text-xs text-[#8b949e] hover:text-[#c9d1d9] transition-colors mr-2">
                                Set default
                            </button>
                        </template>
                        <template x-if="!bucket.isActive">
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-[#6e768126] text-[#8b949e] uppercase mr-2">Inactive</span>
                        </template>
                        <button @click="testConnection(bucket.id)" class="text-xs text-[#8b949e] hover:text-[#c9d1d9] transition-colors mr-2">
                            Test
                        </button>
                        <button @click="deleteBucket(bucket.id)" class="text-xs text-[#f85149] hover:text-[#ff7b72] transition-colors">
                            Delete
                        </button>
                    </div>
                </template>

                <template x-if="buckets.length === 0">
                    <div class="p-6 text-center text-[#8b949e] text-sm border-t border-[#21262d]">
                        No custom storage buckets. Add one to store assets in your own cloud storage.
                    </div>
                </template>
            </div>

            <div class="flex gap-2">
                <button
                    @click="showAddForm = !showAddForm; showMigrateForm = false"
                    x-show="rcloneAvailable"
                    class="px-4 py-2 bg-[#21262d] border border-[#30363d] rounded-md text-sm text-[#c9d1d9] hover:border-[#8b949e] transition-colors"
                >
                    <span x-text="showAddForm ? 'Cancel' : 'Add Storage'"></span>
                </button>
                <button
                    @click="showMigrateForm = !showMigrateForm; showAddForm = false"
                    x-show="rcloneAvailable && (buckets.length > 0 || system.assetsCount > 0)"
                    class="px-4 py-2 bg-[#21262d] border border-[#30363d] rounded-md text-sm text-[#c9d1d9] hover:border-[#8b949e] transition-colors"
                >
                    <span x-text="showMigrateForm ? 'Cancel' : 'Migrate Assets'"></span>
                </button>
            </div>
        </div>

        <div>
            <template x-if="showAddForm && rcloneAvailable">
                <div class="bg-[#161b22] rounded-md border border-[#30363d] p-5">
                    <h3 class="text-base font-medium text-[#c9d1d9] mb-4">Add Storage Bucket</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs text-[#8b949e] mb-1.5">Name</label>
                            <input
                                type="text"
                                x-model="newBucket.name"
                                placeholder="My S3 Bucket"
                                class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                            >
                        </div>

                        <div>
                            <label class="block text-xs text-[#8b949e] mb-1.5">Provider</label>
                            <select
                                x-model="newBucket.provider"
                                @change="onProviderChange()"
                                class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                            >
                                <option value="">Select provider...</option>
                                <template x-for="p in providers" :key="p.id">
                                    <option :value="p.id" x-text="p.name" :disabled="p.type === 'oauth'"></option>
                                </template>
                            </select>
                        </div>

                        <template x-if="selectedProvider">
                            <div class="space-y-3">
                                <template x-for="field in selectedProvider.fields" :key="field">
                                    <div>
                                        <label class="block text-xs text-[#8b949e] mb-1.5 capitalize" x-text="formatFieldName(field)"></label>
                                        <input
                                            :type="isSecretField(field) ? 'password' : 'text'"
                                            x-model="newBucket.credentials[field]"
                                            :placeholder="getFieldPlaceholder(field)"
                                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                                        >
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" x-model="newBucket.isDefault" id="setDefault" class="rounded">
                            <label for="setDefault" class="text-sm text-[#8b949e]">Set as default storage</label>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button
                                @click="createBucket()"
                                :disabled="!canCreate"
                                class="px-4 py-2 bg-[#2563eb] border border-[#2563eb] rounded-md text-white text-sm font-medium hover:bg-[#1d4ed8] hover:border-[#1d4ed8] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Add Storage
                            </button>
                        </div>

                        <div x-show="createError" x-cloak class="text-sm text-[#f85149]" x-text="createError"></div>
                    </div>
                </div>
            </template>

            <template x-if="showMigrateForm && rcloneAvailable">
                <div class="bg-[#161b22] rounded-md border border-[#30363d] p-5 mb-6">
                    <h3 class="text-base font-medium text-[#c9d1d9] mb-4">Migrate Assets</h3>
                    <p class="text-xs text-[#8b949e] mb-4">Move or copy assets from one storage location to another.</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs text-[#8b949e] mb-1.5">From</label>
                            <select
                                x-model="migrateFrom"
                                class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                            >
                                <option value="">Select source...</option>
                                <option value="system" x-text="system.name + ' (' + system.assetsCount + ' assets)'"></option>
                                <template x-for="bucket in buckets" :key="bucket.id">
                                    <option :value="bucket.id" x-text="bucket.name + ' (' + bucket.assetsCount + ' assets)'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-[#8b949e] mb-1.5">To</label>
                            <select
                                x-model="migrateTo"
                                class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                            >
                                <option value="">Select destination...</option>
                                <option value="system" x-show="migrateFrom !== 'system'" x-text="system.name"></option>
                                <template x-for="bucket in buckets" :key="bucket.id">
                                    <option :value="bucket.id" x-show="migrateFrom !== bucket.id" x-text="bucket.name"></option>
                                </template>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" x-model="deleteSource" id="deleteSource" class="rounded">
                            <label for="deleteSource" class="text-sm text-[#8b949e]">Delete from source after migration (move)</label>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button
                                @click="startMigration()"
                                :disabled="!canMigrate || migrating"
                                class="px-4 py-2 bg-[#2563eb] border border-[#2563eb] rounded-md text-white text-sm font-medium hover:bg-[#1d4ed8] hover:border-[#1d4ed8] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span x-show="!migrating">Start Migration</span>
                                <span x-show="migrating">Starting...</span>
                            </button>
                        </div>

                        <div x-show="migrateError" x-cloak class="text-sm text-[#f85149]" x-text="migrateError"></div>
                    </div>
                </div>
            </template>

            <template x-if="migrations.length > 0">
                <div :class="showAddForm || showMigrateForm ? '' : 'mt-6'">
                    <h3 class="text-base font-medium text-[#c9d1d9] mb-3">Migration History</h3>
                    <div class="bg-[#161b22] rounded-md border border-[#30363d] overflow-hidden">
                        <template x-for="m in migrations" :key="m.id">
                            <div class="p-4 border-b border-[#21262d] last:border-b-0">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-sm text-[#c9d1d9]">
                                        <span x-text="m.fromBucket.name"></span>
                                        <span class="text-[#8b949e] mx-1">&rarr;</span>
                                        <span x-text="m.toBucket.name"></span>
                                    </div>
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10px] font-medium uppercase"
                                        :class="getMigrationStatusClass(m.status)"
                                        x-text="m.status"
                                    ></span>
                                </div>
                                <div class="text-xs text-[#8b949e]">
                                    <span x-text="m.processedAssets"></span>/<span x-text="m.totalAssets"></span> assets
                                    <template x-if="m.status === 'processing'">
                                        <span class="ml-2" x-text="'(' + m.progress + '%)'"></span>
                                    </template>
                                    <span class="mx-1">&middot;</span>
                                    <span x-text="formatDate(m.created)"></span>
                                    <template x-if="m.completedAt && m.status === 'completed'">
                                        <span>
                                            <span class="mx-1">&rarr;</span>
                                            <span x-text="formatDate(m.completedAt)"></span>
                                        </span>
                                    </template>
                                </div>
                                <template x-if="m.status === 'pending' || m.status === 'processing'">
                                    <button @click="cancelMigration(m.id)" class="text-xs text-[#f85149] hover:text-[#ff7b72] mt-2">
                                        Cancel
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function storagePage() {
    return {
        system: { name: 'Platform Storage', isDefault: true, assetsCount: 0 },
        buckets: [],
        providers: [],
        migrations: [],
        rcloneAvailable: true,
        showAddForm: false,
        showMigrateForm: false,
        createError: null,
        migrateError: null,
        migrating: false,
        migrateFrom: '',
        migrateTo: '',
        deleteSource: false,
        newBucket: {
            name: '',
            provider: '',
            credentials: {},
            isDefault: false
        },

        get selectedProvider() {
            return this.providers.find(p => p.id === this.newBucket.provider);
        },

        get canCreate() {
            if (!this.newBucket.name || !this.newBucket.provider) return false;
            const provider = this.selectedProvider;
            if (!provider) return false;
            return provider.fields.every(f => this.newBucket.credentials[f]);
        },

        get canMigrate() {
            if (!this.migrateFrom || !this.migrateTo) return false;
            if (this.migrateFrom === this.migrateTo) return false;
            // Check source has assets
            if (this.migrateFrom === 'system') {
                return this.system.assetsCount > 0;
            }
            const bucket = this.buckets.find(b => b.id === this.migrateFrom);
            return bucket && bucket.assetsCount > 0;
        },

        async init() {
            await Promise.all([
                this.loadStorage(),
                this.loadProviders(),
                this.loadMigrations()
            ]);
        },

        async loadStorage() {
            try {
                const res = await window.apiFetch('/api/storage');
                if (res.ok) {
                    const data = await res.json();
                    this.system = data.system;
                    this.buckets = data.buckets || [];
                    this.rcloneAvailable = data.rcloneAvailable;
                }
            } catch (e) {
                console.error('Failed to load storage:', e);
            }
        },

        async loadProviders() {
            try {
                const res = await window.apiFetch('/api/storage/providers');
                if (res.ok) {
                    const data = await res.json();
                    this.providers = data.providers || [];
                }
            } catch (e) {
                console.error('Failed to load providers:', e);
            }
        },

        async loadMigrations() {
            try {
                const res = await window.apiFetch('/api/storage/migrations');
                if (res.ok) {
                    const data = await res.json();
                    this.migrations = data.migrations || [];
                }
            } catch (e) {
                console.error('Failed to load migrations:', e);
            }
        },

        onProviderChange() {
            this.newBucket.credentials = {};
        },

        async createBucket() {
            this.createError = null;
            try {
                const config = {};
                const credentials = {};
                const provider = this.selectedProvider;
                
                for (const field of provider.fields) {
                    if (this.isSecretField(field)) {
                        credentials[field] = this.newBucket.credentials[field];
                    } else {
                        config[field] = this.newBucket.credentials[field];
                    }
                }

                const res = await window.apiFetch('/api/storage', {
                    method: 'POST',
                    body: JSON.stringify({
                        name: this.newBucket.name,
                        provider: this.newBucket.provider,
                        config,
                        credentials,
                        is_default: this.newBucket.isDefault
                    })
                });
                
                const data = await res.json();
                
                if (!res.ok) {
                    this.createError = data.error || 'Failed to create bucket';
                    return;
                }

                this.showAddForm = false;
                this.newBucket = { name: '', provider: '', credentials: {}, isDefault: false };
                await this.loadStorage();
            } catch (e) {
                this.createError = 'Failed to create bucket';
            }
        },

        async setDefault(bucketId) {
            try {
                await window.apiFetch(`/api/storage/${bucketId}/default`, { method: 'POST' });
                await this.loadStorage();
            } catch (e) {
                console.error('Failed to set default:', e);
            }
        },

        async setSystemDefault() {
            try {
                await window.apiFetch('/api/storage/default', { method: 'DELETE' });
                await this.loadStorage();
            } catch (e) {
                console.error('Failed to set system default:', e);
            }
        },

        async testConnection(bucketId) {
            try {
                const res = await window.apiFetch(`/api/storage/${bucketId}/test`, { method: 'POST' });
                const data = await res.json();
                alert(data.connected ? 'Connection successful!' : 'Connection failed');
            } catch (e) {
                alert('Connection test failed');
            }
        },

        async deleteBucket(bucketId) {
            if (!confirm('Delete this storage bucket? Assets must be migrated first.')) return;
            try {
                const res = await window.apiFetch(`/api/storage/${bucketId}`, { method: 'DELETE' });
                if (!res.ok) {
                    const data = await res.json();
                    alert(data.error || 'Failed to delete');
                    return;
                }
                await this.loadStorage();
            } catch (e) {
                console.error('Failed to delete bucket:', e);
            }
        },

        async cancelMigration(migrationId) {
            try {
                await window.apiFetch(`/api/storage/migrations/${migrationId}/cancel`, { method: 'POST' });
                await this.loadMigrations();
            } catch (e) {
                console.error('Failed to cancel migration:', e);
            }
        },

        async startMigration() {
            this.migrateError = null;
            this.migrating = true;
            try {
                const res = await window.apiFetch('/api/storage/migrate', {
                    method: 'POST',
                    body: JSON.stringify({
                        from_bucket_id: this.migrateFrom === 'system' ? null : this.migrateFrom,
                        to_bucket_id: this.migrateTo === 'system' ? null : this.migrateTo,
                        delete_source: this.deleteSource
                    })
                });
                
                const data = await res.json();
                
                if (!res.ok) {
                    this.migrateError = data.error || 'Failed to start migration';
                    return;
                }

                this.showMigrateForm = false;
                this.migrateFrom = '';
                this.migrateTo = '';
                this.deleteSource = false;
                await Promise.all([this.loadStorage(), this.loadMigrations()]);
            } catch (e) {
                this.migrateError = 'Failed to start migration';
            } finally {
                this.migrating = false;
            }
        },

        getProviderIcon(provider) {
            const icons = {
                'aws': 'S3',
                'digitalocean': 'DO',
                'cloudflare': 'R2',
                'backblaze': 'B2',
                'wasabi': 'WS',
                'minio': 'M',
                'sftp': 'SF',
                'ftp': 'FT',
                'webdav': 'WD',
                'other': 'S3'
            };
            return icons[provider] || '?';
        },

        getProviderName(provider) {
            const names = {
                'aws': 'Amazon S3',
                'digitalocean': 'DigitalOcean Spaces',
                'cloudflare': 'Cloudflare R2',
                'backblaze': 'Backblaze B2',
                'wasabi': 'Wasabi',
                'minio': 'Minio',
                'sftp': 'SFTP',
                'ftp': 'FTP',
                'webdav': 'WebDAV',
                'other': 'S3-Compatible'
            };
            return names[provider] || provider;
        },

        formatFieldName(field) {
            return field.replace(/_/g, ' ').replace(/id$/i, 'ID');
        },

        isSecretField(field) {
            return field.includes('secret') || field.includes('pass') || field.includes('key');
        },

        getFieldPlaceholder(field) {
            const placeholders = {
                'bucket': 'my-bucket',
                'region': 'us-east-1',
                'endpoint': 'https://s3.example.com',
                'access_key_id': 'AKIA...',
                'secret_access_key': '',
                'account_id': 'abc123...',
                'host': 'sftp.example.com',
                'port': '22',
                'user': 'username',
                'pass': '',
                'root': '/path/to/files',
                'url': 'https://webdav.example.com'
            };
            return placeholders[field] || '';
        },

        getMigrationStatusClass(status) {
            const classes = {
                'pending': 'bg-[#6e768126] text-[#8b949e]',
                'processing': 'bg-[#388bfd26] text-[#58a6ff]',
                'completed': 'bg-[#2ea04326] text-[#3fb950]',
                'failed': 'bg-[#f8514926] text-[#f85149]',
                'cancelled': 'bg-[#6e768126] text-[#8b949e]'
            };
            return classes[status] || '';
        },

        formatDate(isoString) {
            if (!isoString) return '';
            const date = new Date(isoString);
            const now = new Date();
            const isToday = date.toDateString() === now.toDateString();
            const yesterday = new Date(now);
            yesterday.setDate(yesterday.getDate() - 1);
            const isYesterday = date.toDateString() === yesterday.toDateString();
            
            const time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            if (isToday) {
                return `Today ${time}`;
            } else if (isYesterday) {
                return `Yesterday ${time}`;
            } else {
                const dateStr = date.toLocaleDateString([], { month: 'short', day: 'numeric' });
                return `${dateStr} ${time}`;
            }
        }
    }
}
</script>
@endsection
