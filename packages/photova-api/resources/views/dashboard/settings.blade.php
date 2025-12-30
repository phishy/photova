@extends('dashboard.layout')

@section('title', 'Settings')

@section('content')
<div x-data="settingsPage()" x-init="init()">
    <h1 class="text-2xl font-semibold tracking-tight mb-6">Settings</h1>

    <!-- Tab Navigation -->
    <div class="border-b border-[#30363d] mb-6">
        <nav class="flex gap-6">
            <button
                @click="activeTab = 'profile'"
                :class="activeTab === 'profile' ? 'text-[#58a6ff] border-b-2 border-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9] border-b-2 border-transparent'"
                class="pb-3 text-sm font-medium transition-colors"
            >
                Profile
            </button>
            <button
                @click="activeTab = 'keys'"
                :class="activeTab === 'keys' ? 'text-[#58a6ff] border-b-2 border-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9] border-b-2 border-transparent'"
                class="pb-3 text-sm font-medium transition-colors"
            >
                API Keys
            </button>
        </nav>
    </div>

    <!-- Profile Tab Content -->
    <div x-show="activeTab === 'profile'" x-cloak>
        <div class="grid gap-6 max-w-2xl">
            <!-- Profile Section -->
            <div class="bg-[#161b22] rounded-md border border-[#30363d] overflow-hidden">
                <div class="px-5 py-4 border-b border-[#21262d]">
                    <h2 class="text-base font-medium text-[#c9d1d9]">Profile</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm text-[#8b949e] mb-2">Name</label>
                        <input
                            type="text"
                            x-model="profile.name"
                            class="w-full px-3.5 py-2.5 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                        >
                    </div>
                    <div>
                        <label class="block text-sm text-[#8b949e] mb-2">Email</label>
                        <input
                            type="email"
                            x-model="profile.email"
                            class="w-full px-3.5 py-2.5 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                        >
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <div x-show="profileSuccess" x-cloak class="text-sm text-[#3fb950]">Profile updated</div>
                        <div x-show="profileError" x-cloak class="text-sm text-[#f85149]" x-text="profileError"></div>
                        <button
                            @click="updateProfile"
                            :disabled="profileLoading"
                            class="px-5 py-2.5 bg-[#2563eb] border border-[#2563eb] rounded-md text-white text-sm font-medium hover:bg-[#1d4ed8] hover:border-[#1d4ed8] transition-colors disabled:opacity-50 ml-auto"
                            x-text="profileLoading ? 'Saving...' : 'Save changes'"
                        ></button>
                    </div>
                </div>
            </div>

            <!-- Password Section -->
            <div class="bg-[#161b22] rounded-md border border-[#30363d] overflow-hidden">
                <div class="px-5 py-4 border-b border-[#21262d]">
                    <h2 class="text-base font-medium text-[#c9d1d9]">Change Password</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm text-[#8b949e] mb-2">Current Password</label>
                        <input
                            type="password"
                            x-model="passwords.current"
                            class="w-full px-3.5 py-2.5 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                        >
                    </div>
                    <div>
                        <label class="block text-sm text-[#8b949e] mb-2">New Password</label>
                        <input
                            type="password"
                            x-model="passwords.new"
                            class="w-full px-3.5 py-2.5 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                        >
                    </div>
                    <div>
                        <label class="block text-sm text-[#8b949e] mb-2">Confirm New Password</label>
                        <input
                            type="password"
                            x-model="passwords.confirm"
                            class="w-full px-3.5 py-2.5 bg-[#0d1117] border border-[#30363d] rounded-md text-[#c9d1d9] text-sm outline-none focus:border-[#58a6ff] transition-colors"
                        >
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <div x-show="passwordSuccess" x-cloak class="text-sm text-[#3fb950]">Password updated</div>
                        <div x-show="passwordError" x-cloak class="text-sm text-[#f85149]" x-text="passwordError"></div>
                        <button
                            @click="updatePassword"
                            :disabled="passwordLoading"
                            class="px-5 py-2.5 bg-[#2563eb] border border-[#2563eb] rounded-md text-white text-sm font-medium hover:bg-[#1d4ed8] hover:border-[#1d4ed8] transition-colors disabled:opacity-50 ml-auto"
                            x-text="passwordLoading ? 'Updating...' : 'Update password'"
                        ></button>
                    </div>
                </div>
            </div>

            <!-- Account Info Section -->
            <div class="bg-[#161b22] rounded-md border border-[#30363d] overflow-hidden">
                <div class="px-5 py-4 border-b border-[#21262d]">
                    <h2 class="text-base font-medium text-[#c9d1d9]">Account</h2>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-[#8b949e]">Plan</span>
                            <div class="text-[#c9d1d9] capitalize" x-text="profile.plan || 'Free'"></div>
                        </div>
                        <div>
                            <span class="text-[#8b949e]">Monthly Limit</span>
                            <div class="text-[#c9d1d9]" x-text="profile.monthlyLimit ? profile.monthlyLimit.toLocaleString() + ' requests' : 'Unlimited'"></div>
                        </div>
                        <div>
                            <span class="text-[#8b949e]">Member Since</span>
                            <div class="text-[#c9d1d9]" x-text="profile.created ? new Date(profile.created).toLocaleDateString() : '-'"></div>
                        </div>
                        <div>
                            <span class="text-[#8b949e]">Role</span>
                            <div class="text-[#c9d1d9] capitalize" x-text="profile.role || 'User'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Keys Tab Content -->
    <div x-show="activeTab === 'keys'" x-cloak>
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
</div>
@endsection

@section('scripts')
<script>
    function settingsPage() {
        return {
            activeTab: new URLSearchParams(window.location.search).get('tab') || 'profile',
            // Profile state
            profile: {
                name: '',
                email: '',
                plan: '',
                monthlyLimit: null,
                created: null,
                role: ''
            },
            passwords: {
                current: '',
                new: '',
                confirm: ''
            },
            profileLoading: false,
            profileSuccess: false,
            profileError: '',
            passwordLoading: false,
            passwordSuccess: false,
            passwordError: '',
            // API Keys state
            keys: [],
            newKeyName: '',
            createdKey: null,
            copySuccess: false,

            async init() {
                await this.loadProfile();
                await this.loadKeys();
            },

            async loadProfile() {
                try {
                    const res = await window.apiFetch('/api/auth/me');
                    if (res.ok) {
                        const data = await res.json();
                        this.profile = data.user;
                    }
                } catch (e) {
                    console.error('Failed to load profile:', e);
                }
            },

            async updateProfile() {
                this.profileLoading = true;
                this.profileSuccess = false;
                this.profileError = '';

                try {
                    const res = await window.apiFetch('/api/auth/me', {
                        method: 'PATCH',
                        body: JSON.stringify({
                            name: this.profile.name,
                            email: this.profile.email
                        }),
                    });

                    if (res.ok) {
                        const data = await res.json();
                        this.profile = data.user;
                        this.profileSuccess = true;
                        setTimeout(() => this.profileSuccess = false, 3000);
                    } else {
                        const data = await res.json();
                        this.profileError = data.message || data.error || 'Failed to update profile';
                    }
                } catch (e) {
                    this.profileError = 'Failed to update profile';
                } finally {
                    this.profileLoading = false;
                }
            },

            async updatePassword() {
                this.passwordLoading = true;
                this.passwordSuccess = false;
                this.passwordError = '';

                if (this.passwords.new !== this.passwords.confirm) {
                    this.passwordError = 'Passwords do not match';
                    this.passwordLoading = false;
                    return;
                }

                if (this.passwords.new.length < 8) {
                    this.passwordError = 'Password must be at least 8 characters';
                    this.passwordLoading = false;
                    return;
                }

                try {
                    const res = await window.apiFetch('/api/auth/me/password', {
                        method: 'PATCH',
                        body: JSON.stringify({
                            current_password: this.passwords.current,
                            password: this.passwords.new,
                            password_confirmation: this.passwords.confirm
                        }),
                    });

                    if (res.ok) {
                        this.passwordSuccess = true;
                        this.passwords = { current: '', new: '', confirm: '' };
                        setTimeout(() => this.passwordSuccess = false, 3000);
                    } else {
                        const data = await res.json();
                        this.passwordError = data.message || data.errors?.current_password?.[0] || data.errors?.password?.[0] || 'Failed to update password';
                    }
                } catch (e) {
                    this.passwordError = 'Failed to update password';
                } finally {
                    this.passwordLoading = false;
                }
            },

            // API Keys methods
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
