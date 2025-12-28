@extends('dashboard.layout')

@section('title', 'Settings')

@section('content')
<div x-data="settingsPage()" x-init="init()">
    <h1 class="text-2xl font-semibold tracking-tight text-[#c9d1d9] mb-6">Settings</h1>

    <div class="grid gap-6">
        <!-- Profile Section -->
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] overflow-hidden">
            <div class="px-6 py-4 border-b border-[#30363d]">
                <h2 class="text-lg font-medium text-[#c9d1d9]">Profile</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm text-[#8b949e] mb-1.5">Name</label>
                    <input
                        type="text"
                        x-model="profile.name"
                        class="w-full max-w-md px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm focus:outline-none focus:border-[#58a6ff]"
                    >
                </div>
                <div>
                    <label class="block text-sm text-[#8b949e] mb-1.5">Email</label>
                    <input
                        type="email"
                        x-model="profile.email"
                        class="w-full max-w-md px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm focus:outline-none focus:border-[#58a6ff]"
                    >
                </div>
                <div class="pt-2">
                    <button
                        @click="updateProfile()"
                        :disabled="savingProfile"
                        class="px-4 py-2 bg-[#238636] hover:bg-[#2ea043] disabled:opacity-50 text-white text-sm font-medium rounded-md transition-colors"
                    >
                        <span x-show="!savingProfile">Save Changes</span>
                        <span x-show="savingProfile">Saving...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- External Storages Section -->
        <div class="bg-[#161b22] rounded-xl border border-[#30363d] overflow-hidden">
            <div class="px-6 py-4 border-b border-[#30363d] flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-medium text-[#c9d1d9]">External Storages</h2>
                    <p class="text-sm text-[#8b949e] mt-0.5">Connect S3, R2, or other storage backends to import files</p>
                </div>
                <button
                    @click="openAddStorage()"
                    class="flex items-center gap-1.5 px-3 py-2 bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm font-medium transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Storage
                </button>
            </div>
            <div class="divide-y divide-[#30363d]">
                <template x-if="loadingStorages">
                    <div class="p-8 text-center">
                        <svg class="animate-spin h-6 w-6 text-[#8b949e] mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </template>
                <template x-if="!loadingStorages && storages.length === 0">
                    <div class="p-8 text-center">
                        <div class="w-12 h-12 rounded-full bg-[#21262d] flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <p class="text-[#8b949e] text-sm">No external storages connected</p>
                        <p class="text-[#6e7681] text-xs mt-1">Add an S3 bucket or other storage to import files</p>
                    </div>
                </template>
                <template x-for="storage in storages" :key="storage.id">
                    <div class="p-4 flex items-center justify-between hover:bg-[#21262d]/30 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-[#21262d] flex items-center justify-center">
                                <span class="text-lg" x-text="getDriverIcon(storage.driver)"></span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[#c9d1d9] font-medium" x-text="storage.name"></span>
                                    <span x-show="storage.is_default" class="px-1.5 py-0.5 bg-[#238636]/20 text-[#3fb950] text-[10px] font-medium rounded">Default</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-[#8b949e]">
                                    <span x-text="getDriverName(storage.driver)"></span>
                                    <span>&bull;</span>
                                    <span x-text="storage.config.bucket || storage.config.host || '-'"></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="testStorage(storage)"
                                :disabled="testingStorage === storage.id"
                                class="px-3 py-1.5 text-[#8b949e] hover:text-[#c9d1d9] text-sm transition-colors disabled:opacity-50"
                            >
                                <span x-show="testingStorage !== storage.id">Test</span>
                                <span x-show="testingStorage === storage.id">Testing...</span>
                            </button>
                            <button
                                @click="openBrowseStorage(storage)"
                                class="px-3 py-1.5 bg-[#21262d] hover:bg-[#30363d] text-[#c9d1d9] text-sm rounded-md transition-colors"
                            >
                                Browse
                            </button>
                            <div class="relative" x-data="{ open: false }">
                                <button @click.stop="open = !open" class="p-1.5 text-[#8b949e] hover:text-[#c9d1d9] rounded transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                                        <circle cx="8" cy="2.5" r="1.5"/>
                                        <circle cx="8" cy="8" r="1.5"/>
                                        <circle cx="8" cy="13.5" r="1.5"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-1 w-32 bg-[#161b22] border border-[#30363d] rounded-md shadow-lg z-10 py-1">
                                    <button @click="editStorage(storage); open = false" class="w-full px-3 py-1.5 text-left text-sm text-[#c9d1d9] hover:bg-[#30363d]">Edit</button>
                                    <button @click="deleteStorage(storage); open = false" class="w-full px-3 py-1.5 text-left text-sm text-[#f85149] hover:bg-[#30363d]">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Add/Edit Storage Modal -->
    <template x-if="showStorageModal">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showStorageModal = false" @keydown.escape.window="showStorageModal = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-[#30363d] flex items-center justify-between sticky top-0 bg-[#161b22]">
                    <h3 class="text-lg font-medium text-[#c9d1d9]" x-text="editingStorage ? 'Edit Storage' : 'Add Storage'"></h3>
                    <button @click="showStorageModal = false" class="text-[#8b949e] hover:text-[#c9d1d9]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm text-[#8b949e] mb-1.5">Name</label>
                        <input
                            type="text"
                            x-model="storageForm.name"
                            placeholder="My S3 Bucket"
                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm focus:outline-none focus:border-[#58a6ff]"
                        >
                    </div>
                    <div>
                        <label class="block text-sm text-[#8b949e] mb-1.5">Type</label>
                        <select
                            x-model="storageForm.driver"
                            @change="onDriverChange()"
                            :disabled="editingStorage"
                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm focus:outline-none focus:border-[#58a6ff] disabled:opacity-50"
                        >
                            <option value="">Select type...</option>
                            <template x-for="(driver, key) in drivers" :key="key">
                                <option :value="key" x-text="driver.name"></option>
                            </template>
                        </select>
                    </div>

                    <template x-if="storageForm.driver && drivers[storageForm.driver]">
                        <div class="space-y-4 pt-2">
                            <template x-for="field in drivers[storageForm.driver].fields" :key="field.name">
                                <div>
                                    <label class="block text-sm text-[#8b949e] mb-1.5">
                                        <span x-text="field.label"></span>
                                        <span x-show="!field.required" class="text-[#6e7681]">(optional)</span>
                                    </label>
                                    <template x-if="field.type === 'textarea'">
                                        <textarea
                                            x-model="storageForm.config[field.name]"
                                            :placeholder="field.placeholder || ''"
                                            rows="3"
                                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm font-mono focus:outline-none focus:border-[#58a6ff]"
                                        ></textarea>
                                    </template>
                                    <template x-if="field.type !== 'textarea'">
                                        <input
                                            :type="field.type"
                                            x-model="storageForm.config[field.name]"
                                            :placeholder="field.placeholder || field.default || ''"
                                            class="w-full px-3 py-2 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm focus:outline-none focus:border-[#58a6ff]"
                                        >
                                    </template>
                                    <template x-if="editingStorage && (field.type === 'password' || field.name.includes('key') || field.name.includes('secret'))">
                                        <p class="text-xs text-[#8b949e] mt-1">Leave blank to keep existing value</p>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" x-model="storageForm.is_default" id="is_default" class="rounded border-[#30363d] bg-[#0d1117] text-[#2563eb]">
                        <label for="is_default" class="text-sm text-[#c9d1d9]">Set as default storage</label>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-[#30363d] flex justify-end gap-3 sticky bottom-0 bg-[#161b22]">
                    <button @click="showStorageModal = false" class="px-4 py-2 text-[#c9d1d9] hover:bg-[#21262d] rounded-md text-sm transition-colors">Cancel</button>
                    <button
                        @click="saveStorage()"
                        :disabled="savingStorage || !storageForm.name || !storageForm.driver"
                        class="px-4 py-2 bg-[#238636] hover:bg-[#2ea043] disabled:opacity-50 text-white text-sm font-medium rounded-md transition-colors"
                    >
                        <span x-show="!savingStorage" x-text="editingStorage ? 'Save Changes' : 'Add Storage'"></span>
                        <span x-show="savingStorage">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Browse Storage Modal -->
    <template x-if="showBrowseModal && browsingStorage">
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showBrowseModal = false" @keydown.escape.window="showBrowseModal = false">
            <div class="bg-[#161b22] border border-[#30363d] rounded-lg w-full max-w-4xl max-h-[90vh] flex flex-col">
                <div class="px-6 py-4 border-b border-[#30363d] flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-lg font-medium text-[#c9d1d9]" x-text="browsingStorage.name"></h3>
                        <div class="flex items-center gap-2 text-sm text-[#8b949e] mt-0.5">
                            <button @click="browsePath = ''; scanStorage()" class="hover:text-[#58a6ff]">Root</button>
                            <template x-for="(part, idx) in browsePath.split('/').filter(p => p)" :key="idx">
                                <div class="flex items-center gap-2">
                                    <span>/</span>
                                    <button @click="navigateToBreadcrumb(idx)" class="hover:text-[#58a6ff]" x-text="part"></button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <button @click="showBrowseModal = false" class="text-[#8b949e] hover:text-[#c9d1d9]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto min-h-0">
                    <template x-if="scanning">
                        <div class="p-8 text-center">
                            <svg class="animate-spin h-6 w-6 text-[#8b949e] mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-[#8b949e] text-sm">Scanning...</p>
                        </div>
                    </template>
                    <template x-if="!scanning && browseItems.length === 0">
                        <div class="p-8 text-center">
                            <p class="text-[#8b949e] text-sm">No files found</p>
                        </div>
                    </template>
                    <template x-if="!scanning && browseItems.length > 0">
                        <table class="w-full">
                            <thead class="bg-[#21262d] border-b border-[#30363d] sticky top-0">
                                <tr>
                                    <th class="w-10 px-4 py-2">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAll($event.target.checked)"
                                            :checked="selectedFiles.length > 0 && selectedFiles.length === browseItems.filter(i => i.type === 'file' && i.is_image).length"
                                            class="rounded border-[#30363d] bg-[#0d1117] text-[#2563eb]"
                                        >
                                    </th>
                                    <th class="text-left px-4 py-2 text-xs font-medium text-[#8b949e] uppercase">Name</th>
                                    <th class="text-right px-4 py-2 text-xs font-medium text-[#8b949e] uppercase w-24">Size</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#30363d]">
                                <template x-if="browsePath">
                                    <tr @click="navigateUp()" class="hover:bg-[#21262d] cursor-pointer">
                                        <td class="px-4 py-2"></td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center gap-2 text-[#c9d1d9]">
                                                <svg class="w-4 h-4 text-[#8b949e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                </svg>
                                                <span>..</span>
                                            </div>
                                        </td>
                                        <td></td>
                                    </tr>
                                </template>
                                <template x-for="item in browseItems" :key="item.path">
                                    <tr
                                        @click="item.type === 'directory' ? navigateToDir(item.path) : null"
                                        :class="item.type === 'directory' ? 'cursor-pointer' : ''"
                                        class="hover:bg-[#21262d]"
                                    >
                                        <td class="px-4 py-2">
                                            <template x-if="item.type === 'file' && item.is_image">
                                                <input
                                                    type="checkbox"
                                                    @click.stop
                                                    @change="toggleFileSelect(item.path)"
                                                    :checked="selectedFiles.includes(item.path)"
                                                    class="rounded border-[#30363d] bg-[#0d1117] text-[#2563eb]"
                                                >
                                            </template>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center gap-2">
                                                <template x-if="item.type === 'directory'">
                                                    <svg class="w-4 h-4 text-[#8b949e]" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/>
                                                    </svg>
                                                </template>
                                                <template x-if="item.type === 'file' && item.is_image">
                                                    <span class="text-sm">🖼️</span>
                                                </template>
                                                <template x-if="item.type === 'file' && !item.is_image">
                                                    <span class="text-sm">📄</span>
                                                </template>
                                                <span class="text-[#c9d1d9] text-sm" x-text="item.name"></span>
                                                <span x-show="item.type === 'file' && !item.is_image" class="text-xs text-[#6e7681]">(not an image)</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm text-[#8b949e]" x-text="item.type === 'file' ? formatFileSize(item.size) : ''"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                </div>

                <div class="px-6 py-4 border-t border-[#30363d] flex items-center justify-between shrink-0 bg-[#161b22]">
                    <div class="text-sm text-[#8b949e]">
                        <span x-text="selectedFiles.length"></span> file(s) selected
                    </div>
                    <div class="flex gap-3">
                        <button @click="showBrowseModal = false" class="px-4 py-2 text-[#c9d1d9] hover:bg-[#21262d] rounded-md text-sm transition-colors">Cancel</button>
                        <button
                            @click="importSelected()"
                            :disabled="selectedFiles.length === 0 || importing"
                            class="px-4 py-2 bg-[#2563eb] hover:bg-[#1d4ed8] disabled:opacity-50 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            <span x-show="!importing">Import Selected</span>
                            <span x-show="importing">Importing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@section('scripts')
<script>
function settingsPage() {
    return {
        profile: { name: '', email: '' },
        savingProfile: false,
        storages: [],
        loadingStorages: true,
        drivers: {},
        showStorageModal: false,
        editingStorage: null,
        storageForm: { name: '', driver: '', config: {}, is_default: false },
        savingStorage: false,
        testingStorage: null,
        showBrowseModal: false,
        browsingStorage: null,
        browsePath: '',
        browseItems: [],
        selectedFiles: [],
        scanning: false,
        importing: false,

        async init() {
            await Promise.all([
                this.loadProfile(),
                this.loadStorages(),
                this.loadDrivers()
            ]);
        },

        async loadProfile() {
            try {
                const res = await window.apiFetch('/api/auth/me');
                if (res.ok) {
                    const data = await res.json();
                    this.profile = { name: data.user.name, email: data.user.email };
                }
            } catch (e) {
                console.error('Failed to load profile:', e);
            }
        },

        async updateProfile() {
            this.savingProfile = true;
            try {
                const res = await window.apiFetch('/api/auth/me', {
                    method: 'PATCH',
                    body: JSON.stringify(this.profile)
                });
                if (res.ok) {
                    this.$dispatch('toast', { message: 'Profile updated', type: 'success' });
                } else {
                    const error = await res.json();
                    throw new Error(error.error || 'Failed to update profile');
                }
            } catch (e) {
                this.$dispatch('toast', { message: e.message, type: 'error' });
            }
            this.savingProfile = false;
        },

        async loadStorages() {
            this.loadingStorages = true;
            try {
                const res = await window.apiFetch('/api/storages');
                if (res.ok) {
                    const data = await res.json();
                    this.storages = data.storages || [];
                }
            } catch (e) {
                console.error('Failed to load storages:', e);
            }
            this.loadingStorages = false;
        },

        async loadDrivers() {
            try {
                const res = await window.apiFetch('/api/storages/drivers');
                if (res.ok) {
                    const data = await res.json();
                    this.drivers = data.drivers || {};
                }
            } catch (e) {
                console.error('Failed to load drivers:', e);
            }
        },

        getDriverIcon(driver) {
            const icons = { s3: '🪣', r2: '☁️', gcs: '🌐', ftp: '📁', sftp: '🔐', local: '💾' };
            return icons[driver] || '📦';
        },

        getDriverName(driver) {
            return this.drivers[driver]?.name || driver;
        },

        openAddStorage() {
            this.editingStorage = null;
            this.storageForm = { name: '', driver: '', config: {}, is_default: false };
            this.showStorageModal = true;
        },

        editStorage(storage) {
            this.editingStorage = storage;
            this.storageForm = {
                name: storage.name,
                driver: storage.driver,
                config: { ...storage.config },
                is_default: storage.is_default
            };
            this.showStorageModal = true;
        },

        onDriverChange() {
            if (!this.editingStorage) {
                this.storageForm.config = {};
                const driver = this.drivers[this.storageForm.driver];
                if (driver) {
                    driver.fields.forEach(field => {
                        if (field.default) {
                            this.storageForm.config[field.name] = field.default;
                        }
                    });
                }
            }
        },

        async saveStorage() {
            this.savingStorage = true;
            try {
                const url = this.editingStorage
                    ? `/api/storages/${this.editingStorage.id}`
                    : '/api/storages';
                const method = this.editingStorage ? 'PATCH' : 'POST';

                const res = await window.apiFetch(url, {
                    method,
                    body: JSON.stringify(this.storageForm)
                });

                if (res.ok) {
                    this.showStorageModal = false;
                    await this.loadStorages();
                    this.$dispatch('toast', {
                        message: this.editingStorage ? 'Storage updated' : 'Storage added',
                        type: 'success'
                    });
                } else {
                    const error = await res.json();
                    throw new Error(error.error || 'Failed to save storage');
                }
            } catch (e) {
                this.$dispatch('toast', { message: e.message, type: 'error' });
            }
            this.savingStorage = false;
        },

        async deleteStorage(storage) {
            if (!confirm(`Delete "${storage.name}"? This cannot be undone.`)) return;

            try {
                const res = await window.apiFetch(`/api/storages/${storage.id}`, { method: 'DELETE' });
                if (res.ok) {
                    await this.loadStorages();
                    this.$dispatch('toast', { message: 'Storage deleted', type: 'success' });
                }
            } catch (e) {
                this.$dispatch('toast', { message: 'Failed to delete storage', type: 'error' });
            }
        },

        async testStorage(storage) {
            this.testingStorage = storage.id;
            try {
                const res = await window.apiFetch(`/api/storages/${storage.id}/test`, { method: 'POST' });
                const data = await res.json();
                this.$dispatch('toast', {
                    message: data.message,
                    type: data.success ? 'success' : 'error'
                });
            } catch (e) {
                this.$dispatch('toast', { message: 'Connection test failed', type: 'error' });
            }
            this.testingStorage = null;
        },

        openBrowseStorage(storage) {
            this.browsingStorage = storage;
            this.browsePath = '';
            this.browseItems = [];
            this.selectedFiles = [];
            this.showBrowseModal = true;
            this.scanStorage();
        },

        async scanStorage() {
            if (!this.browsingStorage) return;
            this.scanning = true;
            this.selectedFiles = [];

            try {
                const res = await window.apiFetch(`/api/storages/${this.browsingStorage.id}/scan`, {
                    method: 'POST',
                    body: JSON.stringify({ path: this.browsePath })
                });

                if (res.ok) {
                    const data = await res.json();
                    this.browseItems = data.items || [];
                } else {
                    const error = await res.json();
                    throw new Error(error.error || 'Scan failed');
                }
            } catch (e) {
                this.$dispatch('toast', { message: e.message, type: 'error' });
                this.browseItems = [];
            }
            this.scanning = false;
        },

        navigateToDir(path) {
            this.browsePath = path;
            this.scanStorage();
        },

        navigateUp() {
            const parts = this.browsePath.split('/').filter(p => p);
            parts.pop();
            this.browsePath = parts.join('/');
            this.scanStorage();
        },

        navigateToBreadcrumb(idx) {
            const parts = this.browsePath.split('/').filter(p => p);
            this.browsePath = parts.slice(0, idx + 1).join('/');
            this.scanStorage();
        },

        toggleFileSelect(path) {
            const idx = this.selectedFiles.indexOf(path);
            if (idx === -1) {
                this.selectedFiles.push(path);
            } else {
                this.selectedFiles.splice(idx, 1);
            }
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.selectedFiles = this.browseItems
                    .filter(i => i.type === 'file' && i.is_image)
                    .map(i => i.path);
            } else {
                this.selectedFiles = [];
            }
        },

        async importSelected() {
            if (this.selectedFiles.length === 0) return;
            this.importing = true;

            try {
                const res = await window.apiFetch(`/api/storages/${this.browsingStorage.id}/import`, {
                    method: 'POST',
                    body: JSON.stringify({ files: this.selectedFiles })
                });

                if (res.ok) {
                    const data = await res.json();
                    this.showBrowseModal = false;
                    this.$dispatch('toast', {
                        message: `Imported ${data.total_imported} file(s)`,
                        type: 'success'
                    });
                    if (data.total_errors > 0) {
                        console.warn('Import errors:', data.errors);
                    }
                } else {
                    const error = await res.json();
                    throw new Error(error.error || 'Import failed');
                }
            } catch (e) {
                this.$dispatch('toast', { message: e.message, type: 'error' });
            }
            this.importing = false;
        },

        formatFileSize(bytes) {
            if (!bytes) return '-';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    }
}
</script>
@endsection
