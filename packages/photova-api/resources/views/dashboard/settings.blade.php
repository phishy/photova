@extends('dashboard.layout')

@section('title', 'Settings')

@section('content')
<div x-data="settingsPage()" x-init="loadProfile()">
    <h1 class="text-2xl font-semibold tracking-tight mb-8">Settings</h1>

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
@endsection

@section('scripts')
<script>
    function settingsPage() {
        return {
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
            }
        }
    }
</script>
@endsection
