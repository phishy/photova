<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Photova - The Ultimate Photo Platform</title>
    <meta name="description" content="Edit, organize, and enhance your entire photo library with AI-powered tools. Open source. Self-hosted. Yours forever.">
    
    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="Photova - The Ultimate Photo Platform">
    <meta property="og:description" content="Edit, organize, and enhance your entire photo library with AI-powered tools. Open source. Self-hosted. Yours forever.">
    <meta property="og:image" content="{{ url('/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Photova">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Photova - The Ultimate Photo Platform">
    <meta name="twitter:description" content="Edit, organize, and enhance your entire photo library with AI-powered tools. Open source. Self-hosted. Yours forever.">
    <meta name="twitter:image" content="{{ url('/og-image.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>window.csrfToken = '{{ csrf_token() }}';</script>
    <style>
        @keyframes gradient-x {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @keyframes wave-drift {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(-25%); }
        }
        .animate-gradient { animation: gradient-x 8s linear infinite; background-size: 200% auto; }
        .animate-pulse-slow { animation: pulse-slow 2s ease-in-out infinite; }
        .animate-wave { animation: wave-drift 20s ease-in-out infinite; }
        .animate-wave-reverse { animation: wave-drift 25s ease-in-out infinite reverse; }
    </style>
</head>
<body class="bg-black text-white min-h-screen" x-data="app()">
    <header class="fixed top-0 left-0 right-0 z-50 px-6 h-16 flex items-center bg-black/50 backdrop-blur-xl border-b border-white/5">
        <div class="max-w-6xl mx-auto w-full flex justify-between items-center">
            <a href="/" class="flex items-center gap-2 font-semibold text-sm">
                <svg class="w-6 h-6 text-[#58a6ff]" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Photova</span>
            </a>
            <nav class="flex items-center gap-8">
                <a href="#features" class="hidden md:block text-sm text-gray-400 hover:text-white transition">Features</a>
                <a href="#pricing" class="hidden md:block text-sm text-gray-400 hover:text-white transition">Pricing</a>
                <a href="/docs" class="hidden md:block text-sm text-gray-400 hover:text-white transition">Docs</a>
                <a href="https://github.com/phishy/photova" target="_blank" class="hidden md:flex items-center gap-1.5 text-sm text-gray-400 hover:text-white transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                    GitHub
                </a>
                <template x-if="isLoggedIn">
                    <a href="/dashboard" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-md text-sm font-medium hover:opacity-90 transition">Dashboard</a>
                </template>
                <template x-if="!isLoggedIn">
                    <button @click="scrollToAuth" class="px-4 py-2 bg-white text-black rounded-md text-sm font-medium hover:bg-gray-100 transition">Get Started</button>
                </template>
            </nav>
        </div>
    </header>

    <section class="min-h-screen flex flex-col justify-center items-center px-6 pt-32 pb-20 relative overflow-hidden">
        <svg class="absolute bottom-0 left-0 w-[200%] h-[60%] opacity-40 pointer-events-none animate-wave" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <defs>
                <linearGradient id="wave1" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.3"/>
                    <stop offset="50%" stop-color="#8b5cf6" stop-opacity="0.2"/>
                    <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.3"/>
                </linearGradient>
            </defs>
            <path fill="url(#wave1)" d="M0,160L48,176C96,192,192,224,288,213.3C384,203,480,149,576,138.7C672,128,768,160,864,181.3C960,203,1056,213,1152,197.3C1248,181,1344,139,1392,117.3L1440,96L1440,320L0,320Z"/>
        </svg>
        <svg class="absolute bottom-0 left-[-50%] w-[200%] h-[50%] opacity-30 pointer-events-none animate-wave-reverse" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <defs>
                <linearGradient id="wave2" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.3"/>
                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.2"/>
                </linearGradient>
            </defs>
            <path fill="url(#wave2)" d="M0,64L48,80C96,96,192,128,288,128C384,128,480,96,576,106.7C672,117,768,171,864,181.3C960,192,1056,160,1152,133.3C1248,107,1344,85,1392,74.7L1440,64L1440,320L0,320Z"/>
        </svg>

        <div class="absolute top-[20%] left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-[30%] left-[30%] w-[600px] h-[600px] bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 text-center max-w-4xl">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/5 border border-white/10 rounded-full text-sm text-gray-400 mb-8">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse-slow"></span>
                Open source &middot; Self-hosted &middot; Yours forever
            </div>

            <h1 class="text-5xl md:text-7xl font-bold tracking-tight leading-tight mb-6">
                The Ultimate
                <br>
                <span class="bg-gradient-to-r from-blue-500 via-cyan-400 to-purple-500 bg-clip-text text-transparent animate-gradient">Photo Platform</span>
            </h1>

            <p class="text-lg md:text-xl text-gray-400 max-w-xl mx-auto mb-10 leading-relaxed">Edit, organize, and enhance your entire photo library with AI-powered tools. Professional-grade. No subscriptions. No limits.</p>

            <div class="flex gap-4 justify-center flex-wrap">
                <template x-if="isLoggedIn">
                    <a href="/dashboard" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg text-sm font-medium hover:shadow-lg hover:shadow-blue-500/30 hover:-translate-y-0.5 transition-all">Go to Dashboard →</a>
                </template>
                <template x-if="!isLoggedIn">
                    <button @click="scrollToAuth" class="px-6 py-3 bg-white text-black rounded-lg text-sm font-medium hover:shadow-lg hover:shadow-white/15 hover:-translate-y-0.5 transition-all">Start for free →</button>
                </template>
                <a href="/docs" class="px-6 py-3 bg-transparent text-white rounded-lg text-sm font-medium border border-white/20 hover:border-white/40 transition">Read the docs</a>
            </div>
        </div>

        <div class="mt-20 flex gap-3 flex-wrap justify-center max-w-3xl">
            <template x-for="op in operations" :key="op.id">
                <div class="px-4 py-2.5 bg-white/[0.03] border border-white/[0.08] rounded-lg text-sm text-gray-400 hover:border-blue-500/50 hover:text-white transition cursor-default" x-text="op.name"></div>
            </template>
        </div>
    </section>

    <section id="features" class="py-32 px-6 bg-black border-t border-white/5">
        <div class="max-w-6xl mx-auto">
            <!-- Three Pillars -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-32">
                <div class="p-8 bg-[#0a0a0a] border border-white/[0.08] rounded-2xl hover:border-blue-500/30 transition-all">
                    <div class="w-14 h-14 rounded-xl mb-6 flex items-center justify-center text-2xl bg-gradient-to-br from-blue-500 to-cyan-400">🎨</div>
                    <h3 class="text-xl font-semibold mb-3">Edit</h3>
                    <p class="text-gray-400 leading-relaxed">Professional-grade editing right in your browser. Layers, filters, crop, brush, text — everything you need, nothing you don't.</p>
                </div>
                <div class="p-8 bg-[#0a0a0a] border border-white/[0.08] rounded-2xl hover:border-purple-500/30 transition-all">
                    <div class="w-14 h-14 rounded-xl mb-6 flex items-center justify-center text-2xl bg-gradient-to-br from-purple-500 to-pink-500">📁</div>
                    <h3 class="text-xl font-semibold mb-3">Organize</h3>
                    <p class="text-gray-400 leading-relaxed">Your entire library, beautifully managed. Upload, tag, search, and access your images from anywhere. Connect your own storage.</p>
                </div>
                <div class="p-8 bg-[#0a0a0a] border border-white/[0.08] rounded-2xl hover:border-cyan-500/30 transition-all">
                    <div class="w-14 h-14 rounded-xl mb-6 flex items-center justify-center text-2xl bg-gradient-to-br from-cyan-400 to-purple-500">✨</div>
                    <h3 class="text-xl font-semibold mb-3">Enhance</h3>
                    <p class="text-gray-400 leading-relaxed">AI superpowers at your fingertips. Remove backgrounds, upscale, restore old photos, colorize, and more — in seconds.</p>
                </div>
            </div>

            <!-- API Section -->
            <div class="text-center mb-16">
                <p class="text-sm text-blue-500 font-medium mb-3 uppercase tracking-wider">For Developers</p>
                <h2 class="text-4xl md:text-5xl font-bold tracking-tight mb-4">Simple API, powerful results</h2>
                <p class="text-lg text-gray-500 max-w-lg mx-auto">One endpoint per operation. JSON in, JSON out. Ship in minutes.</p>
            </div>

            <div class="bg-[#0a0a0a] rounded-2xl border border-white/[0.08] overflow-hidden mb-12">
                <div class="flex justify-between items-center border-b border-white/[0.08] px-5">
                    <div class="flex">
                        <button @click="codeTab = 'js'" :class="codeTab === 'js' ? 'text-white border-blue-500' : 'text-gray-500 border-transparent'" class="px-5 py-4 text-sm font-medium border-b-2 -mb-px transition">JavaScript</button>
                        <button @click="codeTab = 'curl'" :class="codeTab === 'curl' ? 'text-white border-blue-500' : 'text-gray-500 border-transparent'" class="px-5 py-4 text-sm font-medium border-b-2 -mb-px transition">cURL</button>
                    </div>
                    <button @click="copyCode" class="px-3 py-1.5 bg-white/5 border border-white/10 rounded-md text-xs text-gray-400 hover:bg-white/10 hover:text-white transition flex items-center gap-2">
                        <span x-text="copied ? '✓ Copied!' : 'Copy'"></span>
                    </button>
                </div>
                <div class="p-6">
                    <pre class="text-sm leading-relaxed overflow-auto font-mono text-gray-300"><code x-html="codeTab === 'js' ? jsCode : curlCode"></code></pre>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-6 bg-white/[0.02] border border-white/5 rounded-xl text-center">
                    <div class="text-2xl mb-2">🎨</div>
                    <div class="text-3xl font-bold mb-1">15+</div>
                    <div class="text-sm text-gray-500">Professional filters</div>
                </div>
                <div class="p-6 bg-white/[0.02] border border-white/5 rounded-xl text-center">
                    <div class="text-2xl mb-2">🧠</div>
                    <div class="text-3xl font-bold mb-1">7</div>
                    <div class="text-sm text-gray-500">AI operations</div>
                </div>
                <div class="p-6 bg-white/[0.02] border border-white/5 rounded-xl text-center">
                    <div class="text-2xl mb-2">📦</div>
                    <div class="text-3xl font-bold mb-1">5 min</div>
                    <div class="text-sm text-gray-500">Deploy time</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-32 px-6 bg-black border-t border-white/5">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <p class="text-sm text-purple-500 font-medium mb-3 uppercase tracking-wider">AI-Powered</p>
                <h2 class="text-4xl md:text-5xl font-bold tracking-tight mb-4">One-click enhancements</h2>
                <p class="text-lg text-gray-500 max-w-lg mx-auto">Powerful AI that used to take hours, now runs in seconds.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="(op, i) in operations" :key="op.id">
                    <div class="p-8 bg-[#0a0a0a] border border-white/[0.08] rounded-2xl hover:border-blue-500/30 hover:-translate-y-1 transition-all cursor-default">
                        <div class="w-12 h-12 rounded-xl mb-5 flex items-center justify-center text-xl" :class="opGradients[i]" x-text="opIcons[i]"></div>
                        <h3 class="text-lg font-semibold mb-2" x-text="op.name"></h3>
                        <p class="text-sm text-gray-500 mb-4" x-text="op.desc"></p>
                        <code class="inline-block px-3 py-1.5 bg-white/5 rounded-md text-xs text-gray-400 font-mono">/v1/<span x-text="op.id"></span></code>
                    </div>
                </template>
            </div>
        </div>
    </section>

    <section class="py-32 px-6 bg-black border-t border-white/5">
        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <p class="text-sm text-green-500 font-medium mb-3 uppercase tracking-wider">Open Source</p>
                    <h2 class="text-4xl md:text-5xl font-bold tracking-tight mb-6">Your photos stay yours</h2>
                    <p class="text-lg text-gray-400 leading-relaxed mb-6">No vendor lock-in. No one mining your images. Photova runs on your servers — a laptop, a VPS, or your own cloud. You control the data, the costs, and the future.</p>
                    <div class="flex gap-4">
                        <a href="https://github.com/phishy/photova" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/5 border border-white/10 rounded-lg text-sm font-medium hover:bg-white/10 transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                            Star on GitHub
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-6 bg-white/[0.02] border border-white/5 rounded-xl text-center">
                        <div class="text-3xl mb-2">🔒</div>
                        <div class="text-sm text-gray-400">Self-hosted</div>
                    </div>
                    <div class="p-6 bg-white/[0.02] border border-white/5 rounded-xl text-center">
                        <div class="text-3xl mb-2">💾</div>
                        <div class="text-sm text-gray-400">Your storage</div>
                    </div>
                    <div class="p-6 bg-white/[0.02] border border-white/5 rounded-xl text-center">
                        <div class="text-3xl mb-2">🔓</div>
                        <div class="text-sm text-gray-400">MIT licensed</div>
                    </div>
                    <div class="p-6 bg-white/[0.02] border border-white/5 rounded-xl text-center">
                        <div class="text-3xl mb-2">♾️</div>
                        <div class="text-sm text-gray-400">No limits</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="py-32 px-6 bg-black border-t border-white/5">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-16">
                <p class="text-sm text-cyan-500 font-medium mb-3 uppercase tracking-wider">Pricing</p>
                <h2 class="text-4xl md:text-5xl font-bold tracking-tight mb-4">Start free, scale as you grow</h2>
                <p class="text-lg text-gray-500 max-w-lg mx-auto">No per-image fees. No surprise bills. Just powerful tools.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <template x-for="plan in plans" :key="plan.name">
                    <div class="p-8 rounded-2xl relative" :class="plan.popular ? 'bg-gradient-to-b from-blue-500/10 to-transparent border border-blue-500/30' : 'bg-[#0a0a0a] border border-white/[0.08]'">
                        <div x-show="plan.popular" class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-blue-500 rounded-full text-xs font-semibold uppercase tracking-wide">Popular</div>
                        <div class="text-base font-semibold mb-2" x-text="plan.name"></div>
                        <div class="mb-2">
                            <span class="text-5xl font-bold" x-text="plan.price"></span>
                            <span class="text-gray-500 text-sm" x-text="plan.period"></span>
                        </div>
                        <div class="text-sm text-gray-500 mb-6 pb-6 border-b border-white/[0.08]" x-text="plan.requests"></div>
                        <ul class="mb-8 space-y-3">
                            <template x-for="feature in plan.features" :key="feature">
                                <li class="text-sm text-gray-400 flex items-center gap-2">
                                    <span class="text-blue-500">✓</span>
                                    <span x-text="feature"></span>
                                </li>
                            </template>
                        </ul>
                        <button @click="scrollToAuth" class="w-full py-3 px-6 rounded-lg text-sm font-medium transition" :class="plan.popular ? 'bg-white text-black hover:opacity-90' : 'bg-transparent text-white border border-white/20 hover:border-white/40'" x-text="plan.cta"></button>
                    </div>
                </template>
            </div>
        </div>
    </section>

    <section id="auth" class="py-32 px-6 bg-black border-t border-white/5">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold tracking-tight mb-2" x-text="isLogin ? 'Welcome back' : 'Ready to take control?'"></h2>
                <p class="text-gray-500" x-text="isLogin ? 'Sign in to your account' : 'Create your free account and start editing'"></p>
            </div>

            <div class="p-8 bg-[#0a0a0a] border border-white/[0.08] rounded-2xl">
                <div x-show="error" class="mb-5 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500 text-sm" x-text="error"></div>

                <form @submit.prevent="handleSubmit">
                    <div x-show="!isLogin" class="mb-4">
                        <label class="block text-sm text-gray-400 mb-2">Name</label>
                        <input type="text" x-model="name" placeholder="Your name" class="w-full px-4 py-3 bg-[#111] border border-white/10 rounded-lg text-white text-sm outline-none focus:border-blue-500/50 transition">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm text-gray-400 mb-2">Email</label>
                        <input type="email" x-model="email" placeholder="you@example.com" required @keydown.enter="handleSubmit" class="w-full px-4 py-3 bg-[#111] border border-white/10 rounded-lg text-white text-sm outline-none focus:border-blue-500/50 transition">
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm text-gray-400 mb-2">Password</label>
                        <input type="password" x-model="password" :placeholder="isLogin ? 'Enter password' : 'Create password'" required @keydown.enter="handleSubmit" class="w-full px-4 py-3 bg-[#111] border border-white/10 rounded-lg text-white text-sm outline-none focus:border-blue-500/50 transition">
                    </div>
                    <button type="submit" :disabled="loading" class="w-full py-3 px-6 bg-white text-black rounded-lg text-sm font-medium hover:opacity-90 transition disabled:opacity-50" x-text="loading ? 'Loading...' : (isLogin ? 'Sign in' : 'Create account')"></button>
                </form>

                <div class="text-center mt-5 text-sm text-gray-500">
                    <span x-text="isLogin ? 'No account yet?' : 'Already have an account?'"></span>
                    <button @click="isLogin = !isLogin" class="text-blue-500 font-medium ml-1" x-text="isLogin ? 'Sign up' : 'Sign in'"></button>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-12 px-6 border-t border-white/5 bg-black">
        <div class="max-w-6xl mx-auto flex justify-between items-center flex-wrap gap-6">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <svg class="w-4 h-4 text-[#58a6ff]" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Photova</span>
                <span class="mx-2">·</span>
                <span>© 2025</span>
            </div>
            <div class="flex gap-6">
                <a href="/docs" class="text-sm text-gray-500 hover:text-white transition">Docs</a>
                <a href="https://github.com/phishy/photova" target="_blank" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-white transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                    GitHub
                </a>
            </div>
        </div>
    </footer>

    <script>
        function app() {
            return {
                isLoggedIn: false,
                isLogin: false,
                email: '',
                password: '',
                name: '',
                error: '',
                loading: false,
                copied: false,
                codeTab: 'js',
                operations: [
                    { id: 'background-remove', name: 'Background Removal', desc: 'Remove backgrounds instantly' },
                    { id: 'upscale', name: 'Upscale', desc: 'Enhance resolution up to 4x' },
                    { id: 'restore', name: 'Restore', desc: 'Fix old or damaged photos' },
                    { id: 'colorize', name: 'Colorize', desc: 'Add color to B&W images' },
                    { id: 'unblur', name: 'Deblur', desc: 'Sharpen blurry images' },
                    { id: 'inpaint', name: 'Object Removal', desc: 'Remove unwanted objects' },
                    { id: 'analyze', name: 'Analyze', desc: 'AI-powered image captioning' },
                ],
                opGradients: [
                    'bg-gradient-to-br from-blue-500 to-cyan-400',
                    'bg-gradient-to-br from-purple-500 to-pink-500',
                    'bg-gradient-to-br from-pink-500 to-orange-400',
                    'bg-gradient-to-br from-cyan-400 to-purple-500',
                    'bg-gradient-to-br from-orange-400 to-pink-500',
                    'bg-gradient-to-br from-green-400 to-blue-500',
                    'bg-gradient-to-br from-yellow-400 to-orange-500',
                ],
                opIcons: ['✂️', '🔍', '🖼️', '🎨', '✨', '🧹', '🧠'],
                plans: [
                    { name: 'Free', price: '$0', period: '/month', requests: '100 requests/mo', features: ['All operations', 'API access', 'Community support'], cta: 'Get started' },
                    { name: 'Pro', price: '$29', period: '/month', requests: '10,000 requests/mo', features: ['All operations', 'Priority processing', 'Email support', 'Webhooks'], popular: true, cta: 'Get started' },
                    { name: 'Enterprise', price: 'Custom', period: '', requests: 'Unlimited', features: ['All operations', 'Dedicated support', 'SLA guarantee', 'Custom models'], cta: 'Contact us' },
                ],
                jsCode: `<span class="text-purple-400">const</span> response = <span class="text-purple-400">await</span> <span class="text-blue-400">fetch</span>(<span class="text-green-400">'https://api.photova.app/v1/background-remove'</span>, {
  <span class="text-red-400">method</span>: <span class="text-green-400">'POST'</span>,
  <span class="text-red-400">headers</span>: {
    <span class="text-green-400">'Authorization'</span>: <span class="text-orange-400">'Bearer br_live_xxxxx'</span>,
    <span class="text-green-400">'Content-Type'</span>: <span class="text-green-400">'application/json'</span>,
  },
  <span class="text-red-400">body</span>: JSON.<span class="text-blue-400">stringify</span>({ <span class="text-red-400">image</span>: <span class="text-orange-400">'data:image/png;base64,...'</span> }),
});

<span class="text-purple-400">const</span> { <span class="text-yellow-400">image</span> } = <span class="text-purple-400">await</span> response.<span class="text-blue-400">json</span>();`,
                curlCode: `<span class="text-purple-400">curl</span> <span class="text-blue-400">-X</span> <span class="text-red-400">POST</span> <span class="text-green-400">https://api.photova.app/v1/background-remove</span> \\
  <span class="text-blue-400">-H</span> <span class="text-orange-400">"Authorization: Bearer br_live_xxxxx"</span> \\
  <span class="text-blue-400">-H</span> <span class="text-green-400">"Content-Type: application/json"</span> \\
  <span class="text-blue-400">-d</span> <span class="text-green-400">'{"image": "data:image/png;base64,..."}'</span>`,

                init() {
                    fetch('/api/auth/me', { credentials: 'include' })
                        .then(r => r.ok ? r.json() : Promise.reject())
                        .then(() => this.isLoggedIn = true)
                        .catch(() => {});
                },

                scrollToAuth() {
                    document.getElementById('auth').scrollIntoView({ behavior: 'smooth' });
                },

                copyCode() {
                    const code = this.codeTab === 'js' 
                        ? `const response = await fetch('https://api.photova.app/v1/background-remove', {\n  method: 'POST',\n  headers: {\n    'Authorization': 'Bearer br_live_xxxxx',\n    'Content-Type': 'application/json',\n  },\n  body: JSON.stringify({ image: 'data:image/png;base64,...' }),\n});\n\nconst { image } = await response.json();`
                        : `curl -X POST https://api.photova.app/v1/background-remove \\\n  -H "Authorization: Bearer br_live_xxxxx" \\\n  -H "Content-Type: application/json" \\\n  -d '{"image": "data:image/png;base64,..."}'`;
                    navigator.clipboard.writeText(code);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                },

                async handleSubmit() {
                    this.error = '';
                    this.loading = true;
                    const headers = {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    };

                    try {
                        if (this.isLogin) {
                            const res = await fetch('/api/auth/login', {
                                method: 'POST',
                                headers,
                                body: JSON.stringify({ email: this.email, password: this.password }),
                                credentials: 'include',
                            });
                            if (!res.ok) throw new Error((await res.json()).error || 'Login failed');
                            window.location.href = '/dashboard';
                        } else {
                            const signupRes = await fetch('/api/auth/signup', {
                                method: 'POST',
                                headers,
                                body: JSON.stringify({ email: this.email, password: this.password, password_confirmation: this.password, name: this.name || this.email.split('@')[0] }),
                                credentials: 'include',
                            });
                            if (!signupRes.ok) throw new Error((await signupRes.json()).error || 'Signup failed');
                            const loginRes = await fetch('/api/auth/login', {
                                method: 'POST',
                                headers,
                                body: JSON.stringify({ email: this.email, password: this.password }),
                                credentials: 'include',
                            });
                            if (!loginRes.ok) throw new Error('Account created! Please sign in.');
                            window.location.href = '/dashboard';
                        }
                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
