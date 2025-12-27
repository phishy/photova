<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Photova API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
        <!-- Sidebar -->
        <aside class="w-[200px] bg-[#0d1117] border-r border-white/[0.08] p-4 flex flex-col fixed top-0 left-0 bottom-0">
            <a href="/" class="flex items-center gap-2 text-[15px] font-semibold mb-8 px-2">
                <span class="text-lg">☀️</span>
                <span>Photova</span>
            </a>
            
            <nav class="flex-1">
                <a href="/dashboard" class="flex items-center gap-2.5 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard') && !request()->is('dashboard/*') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <span class="text-sm">◉</span>
                    Overview
                </a>
                <a href="/dashboard/keys" class="flex items-center gap-2.5 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/keys') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <span class="text-sm">⚿</span>
                    API Keys
                </a>
                <a href="/dashboard/assets" class="flex items-center gap-2.5 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/assets') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <span class="text-sm">⬡</span>
                    Assets
                </a>
                <a href="/dashboard/usage" class="flex items-center gap-2.5 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/usage') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <span class="text-sm">◔</span>
                    Usage
                </a>
                <a href="/dashboard/playground" class="flex items-center gap-2.5 px-3 py-2.5 rounded-md mb-0.5 text-sm font-medium transition-colors {{ request()->is('dashboard/playground') ? 'bg-[#388bfd26] text-[#58a6ff]' : 'text-[#8b949e] hover:text-[#c9d1d9]' }}">
                    <span class="text-sm">▷</span>
                    Playground
                </a>
            </nav>

            <div class="border-t border-white/[0.08] pt-4">
                <a href="/docs" class="flex items-center gap-2.5 px-3 py-2.5 rounded-md mb-0.5 text-sm text-[#8b949e] hover:text-[#c9d1d9] transition-colors">
                    <span class="text-sm">◧</span>
                    Documentation
                </a>
                <button @click="logout" class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-md text-sm text-[#8b949e] hover:text-[#c9d1d9] transition-colors text-left">
                    <span class="text-sm">⎋</span>
                    Sign Out
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-[200px] p-8 min-h-screen bg-[#0d1117]">
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

                async init() {
                    try {
                        const res = await window.apiFetch('/api/auth/me');
                        if (res.status === 401) {
                            window.location.href = '/';
                            return;
                        }
                        if (res.ok) {
                            this.user = await res.json();
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
