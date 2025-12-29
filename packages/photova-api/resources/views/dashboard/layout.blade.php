<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Photova API</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.apiFetch = async (url, options = {}) => {
            const defaults = {
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            };
            if (options.headers) {
                options.headers = { ...defaults.headers, ...options.headers };
            }
            return fetch(url, { ...defaults, ...options });
        };
    </script>
</head>
<body class="bg-[#0d1117] text-white min-h-screen" x-data="dashboardApp()" x-init="init()">
    <div class="flex min-h-screen">
        <!-- Mobile Header -->
        <header class="md:hidden fixed top-0 left-0 right-0 z-50 bg-[#0d1117] border-b border-white/[0.08] px-4 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 text-[15px] font-semibold">
                <svg class="w-5 h-5 text-[#58a6ff]" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Photova</span>
            </a>
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-[#8b949e] hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </header>

        <!-- Mobile Sidebar Overlay -->
        <div 
            x-show="sidebarOpen" 
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="md:hidden fixed inset-0 bg-black/50 z-40"
            x-cloak
        ></div>

        <!-- Sidebar -->
        <aside 
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="w-[240px] md:w-[200px] bg-[#0d1117] border-r border-white/[0.08] p-4 flex flex-col fixed top-0 left-0 bottom-0 z-50 md:translate-x-0 transition-transform duration-200"
        >
            <div class="flex items-center justify-between mb-8 px-2">
                <a href="/" class="flex items-center gap-2 text-[15px] font-semibold">
                    <svg class="w-5 h-5 text-[#58a6ff]" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>Photova</span>
                </a>
                <button @click="sidebarOpen = false" class="md:hidden p-1 text-[#8b949e] hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <nav class="flex-1" @click="sidebarOpen = false">
                <a href="/dashboard/assets" class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/assets') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    Files
                </a>
                <a href="/dashboard/keys" class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/keys') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    API Keys
                </a>
                <a href="/dashboard/usage" class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/usage') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Usage
                </a>
                <a href="/dashboard/playground" class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/playground') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Playground
                </a>

                <!-- Admin Section -->
                <template x-if="user && user.role === 'superadmin'">
                    <div class="mt-4 pt-4 border-t border-white/[0.08]">
                        <div class="px-3 py-2 text-xs font-semibold text-[#8b949e] uppercase tracking-wider">Admin</div>
                        <a href="/dashboard/admin/analytics" class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/admin/analytics') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Analytics
                        </a>
                        <a href="/dashboard/admin/pricing" class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/admin/pricing') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pricing
                        </a>
                    </div>
                </template>
            </nav>

            <div class="border-t border-white/[0.08] pt-4">
                <a href="/dashboard/settings" class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/settings') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>
                <a href="/docs" class="flex items-center gap-3 px-3 py-2.5 rounded-md mb-0.5 text-sm text-[#8b949e] hover:text-[#c9d1d9] transition-colors">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Documentation
                </a>
                <button @click="logout" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-md text-sm text-[#8b949e] hover:text-[#c9d1d9] transition-colors text-left">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign Out
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 md:ml-[200px] p-4 md:p-8 pt-20 md:pt-8 min-h-screen bg-[#0d1117]">
            @yield('content')
        </main>

        <!-- Toast Notifications -->
        <div 
            class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"
            x-data="toastSystem()"
            @toast.window="showToast($event.detail.message, $event.detail.type)"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    x-show="toast.visible"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg border min-w-[280px]"
                    :class="{
                        'bg-[#238636]/20 border-[#238636]/40 text-[#3fb950]': toast.type === 'success',
                        'bg-[#f85149]/20 border-[#f85149]/40 text-[#f85149]': toast.type === 'error',
                        'bg-[#58a6ff]/20 border-[#58a6ff]/40 text-[#58a6ff]': toast.type === 'info'
                    }"
                >
                    <span class="text-lg" x-text="toast.type === 'success' ? '✓' : toast.type === 'error' ? '✕' : 'ℹ'"></span>
                    <span class="text-sm font-medium flex-1" x-text="toast.message"></span>
                    <button @click="dismissToast(toast.id)" class="opacity-60 hover:opacity-100 transition-opacity">✕</button>
                </div>
            </template>
        </div>
    </div>

    <script>
        function toastSystem() {
            return {
                toasts: [],
                toastId: 0,

                showToast(message, type = 'success', duration = 4000) {
                    const id = ++this.toastId;
                    this.toasts.push({ id, message, type, visible: true });
                    setTimeout(() => this.dismissToast(id), duration);
                },

                dismissToast(id) {
                    const toast = this.toasts.find(t => t.id === id);
                    if (toast) toast.visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 200);
                }
            }
        }

        function dashboardApp() {
            return {
                user: null,
                authChecked: false,
                sidebarOpen: false,

                async init() {
                    try {
                        const res = await window.apiFetch('/api/auth/me');
                        if (res.status === 401) {
                            window.location.href = '/';
                            return;
                        }
                if (res.ok) {
                    const data = await res.json();
                    this.user = data.user;
                }
                    } catch (e) {
                        console.error('Auth check failed:', e);
                    }
                    this.authChecked = true;
                },

                async logout() {
                    await window.apiFetch('/api/auth/logout', { method: 'POST' });
                    window.location.href = '/';
                }
            }
        }
    </script>
    <script src="/vendor/brighten.umd.js"></script>
    @yield('scripts')
</body>
</html>
