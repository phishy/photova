<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation - Photova</title>
    <meta name="description" content="Complete API documentation for Photova - AI image editing APIs for developers.">
    
    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/docs') }}">
    <meta property="og:title" content="API Documentation - Photova">
    <meta property="og:description" content="Complete API documentation for Photova - AI image editing APIs for developers.">
    <meta property="og:image" content="{{ url('/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Photova">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="API Documentation - Photova">
    <meta name="twitter:description" content="Complete API documentation for Photova - AI image editing APIs for developers.">
    <meta name="twitter:image" content="{{ url('/og-image.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        html { scroll-behavior: smooth; }
        .prose code { background: rgba(255,255,255,0.1); padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.875em; }
        .prose pre code { background: transparent; padding: 0; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #30363d; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #484f58; }
    </style>
</head>
<body class="bg-[#0a0a0a] text-gray-300 min-h-screen" x-data="docs()">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 h-14 bg-[#0a0a0a]/80 backdrop-blur-xl border-b border-white/5">
        <div class="h-full px-6 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="/" class="flex items-center gap-2 font-semibold text-white text-sm">
                    <span class="text-lg">☀️</span>
                    <span>Photova</span>
                </a>
                <span class="text-gray-600">/</span>
                <span class="text-gray-400 text-sm">Documentation</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="https://github.com/phishy/photova" target="_blank" class="text-gray-400 hover:text-white transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                </a>
                <a href="/dashboard" class="px-4 py-1.5 bg-white text-black rounded-md text-sm font-medium hover:bg-gray-100 transition">Dashboard</a>
            </div>
        </div>
    </header>

    <div class="flex pt-14">
        <!-- Sidebar -->
        <aside class="hidden lg:block fixed left-0 top-14 bottom-0 w-64 border-r border-white/5 overflow-y-auto scrollbar-thin p-6">
            <nav class="space-y-8">
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Getting Started</h4>
                    <ul class="space-y-2">
                        <li><a href="#introduction" class="text-sm text-gray-400 hover:text-white transition block py-1">Introduction</a></li>
                        <li><a href="#installation" class="text-sm text-gray-400 hover:text-white transition block py-1">Installation</a></li>
                        <li><a href="#storage" class="text-sm text-gray-400 hover:text-white transition block py-1">Storage</a></li>
                        <li><a href="#authentication" class="text-sm text-gray-400 hover:text-white transition block py-1">Authentication</a></li>
                        <li><a href="#base-url" class="text-sm text-gray-400 hover:text-white transition block py-1">Base URL</a></li>
                        <li><a href="#errors" class="text-sm text-gray-400 hover:text-white transition block py-1">Errors</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Operations</h4>
                    <ul class="space-y-2">
                        <li><a href="#background-remove" class="text-sm text-gray-400 hover:text-white transition block py-1">Background Removal</a></li>
                        <li><a href="#upscale" class="text-sm text-gray-400 hover:text-white transition block py-1">Upscale</a></li>
                        <li><a href="#restore" class="text-sm text-gray-400 hover:text-white transition block py-1">Restore</a></li>
                        <li><a href="#colorize" class="text-sm text-gray-400 hover:text-white transition block py-1">Colorize</a></li>
                        <li><a href="#unblur" class="text-sm text-gray-400 hover:text-white transition block py-1">Deblur</a></li>
                        <li><a href="#inpaint" class="text-sm text-gray-400 hover:text-white transition block py-1">Object Removal</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Resources</h4>
                    <ul class="space-y-2">
                        <li><a href="#assets" class="text-sm text-gray-400 hover:text-white transition block py-1">Assets</a></li>
                        <li><a href="#usage" class="text-sm text-gray-400 hover:text-white transition block py-1">Usage</a></li>
                    </ul>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-64 min-h-screen">
            <div class="max-w-4xl mx-auto px-6 py-16">
                
                <!-- Introduction -->
                <section id="introduction" class="mb-20">
                    <h1 class="text-4xl font-bold text-white mb-4">API Documentation</h1>
                    <p class="text-lg text-gray-400 mb-8">
                        The Photova API provides powerful AI image editing capabilities through a simple REST interface.
                        Background removal, upscaling, restoration, colorization, and more.
                    </p>
                    <div class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                        <div class="flex items-start gap-3">
                            <span class="text-blue-400 text-lg">💡</span>
                            <div>
                                <p class="text-sm text-blue-300 font-medium mb-1">Quick Start</p>
                                <p class="text-sm text-blue-200/70">Create an account to get your API key, then make your first request in seconds.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Installation -->
                <section id="installation" class="mb-20">
                    <h2 class="text-2xl font-bold text-white mb-4">Installation</h2>
                    <p class="text-gray-400 mb-6">
                        Self-host the Photova API on your own infrastructure. Requires PHP 8.2+ and PostgreSQL.
                    </p>

                    <h3 class="text-lg font-semibold text-white mb-3">Requirements</h3>
                    <ul class="list-disc list-inside text-gray-400 mb-8 space-y-1">
                        <li>PHP 8.2 or higher</li>
                        <li>Composer</li>
                        <li>PostgreSQL 14+ (or Docker)</li>
                        <li>At least one AI provider API key (Replicate, fal.ai, or remove.bg)</li>
                    </ul>

                    <h3 class="text-lg font-semibold text-white mb-3">Quick Start with Docker</h3>
                    <p class="text-gray-400 mb-4">The fastest way to get started using Docker for PostgreSQL:</p>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden mb-8">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-white/10 bg-white/[0.02]">
                            <span class="text-xs text-gray-500">Terminal</span>
                        </div>
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-gray-500"># Clone the repository</span>
<span class="text-purple-400">git</span> clone https://github.com/phishy/photova.git
<span class="text-purple-400">cd</span> brighten

<span class="text-gray-500"># Start PostgreSQL via Docker</span>
<span class="text-purple-400">docker</span> compose up postgres -d

<span class="text-gray-500"># Setup Laravel</span>
<span class="text-purple-400">cd</span> packages/photova-api
<span class="text-purple-400">composer</span> install
<span class="text-purple-400">cp</span> .env.example .env
<span class="text-purple-400">php</span> artisan key:generate
<span class="text-purple-400">php</span> artisan migrate

<span class="text-gray-500"># Start the server</span>
<span class="text-purple-400">php</span> artisan serve</code></pre>
                    </div>

                    <h3 class="text-lg font-semibold text-white mb-3">Environment Variables</h3>
                    <p class="text-gray-400 mb-4">Configure your <code>.env</code> file with the following:</p>
                    <div class="overflow-hidden rounded-lg border border-white/10 mb-8">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Variable</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">DB_CONNECTION</code></td>
                                    <td class="px-4 py-3 text-gray-400">Database driver (<code>pgsql</code>)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">DB_HOST</code></td>
                                    <td class="px-4 py-3 text-gray-400">Database host (default: <code>127.0.0.1</code>)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">DB_DATABASE</code></td>
                                    <td class="px-4 py-3 text-gray-400">Database name</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">AUTH_ENABLED</code></td>
                                    <td class="px-4 py-3 text-gray-400">Enable API key auth (<code>true</code>/<code>false</code>)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">REPLICATE_API_KEY</code></td>
                                    <td class="px-4 py-3 text-gray-400">Replicate API key (recommended)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">FAL_API_KEY</code></td>
                                    <td class="px-4 py-3 text-gray-400">fal.ai API key (optional)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">REMOVEBG_API_KEY</code></td>
                                    <td class="px-4 py-3 text-gray-400">remove.bg API key (optional fallback)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="text-lg font-semibold text-white mb-3">Provider Configuration</h3>
                    <p class="text-gray-400 mb-4">Configure which provider handles each operation in <code>config/photova.php</code>:</p>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden mb-8">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-white/10 bg-white/[0.02]">
                            <span class="text-xs text-gray-500">config/photova.php</span>
                        </div>
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">'operations'</span> => [
    <span class="text-green-400">'background-remove'</span> => [
        <span class="text-green-400">'provider'</span> => <span class="text-green-400">'replicate'</span>,
        <span class="text-green-400">'fallback'</span> => <span class="text-green-400">'removebg'</span>,  <span class="text-gray-500">// Optional fallback</span>
    ],
    <span class="text-green-400">'upscale'</span>  => [<span class="text-green-400">'provider'</span> => <span class="text-green-400">'replicate'</span>],
    <span class="text-green-400">'unblur'</span>   => [<span class="text-green-400">'provider'</span> => <span class="text-green-400">'replicate'</span>],
    <span class="text-green-400">'colorize'</span> => [<span class="text-green-400">'provider'</span> => <span class="text-green-400">'replicate'</span>],
    <span class="text-green-400">'inpaint'</span>  => [<span class="text-green-400">'provider'</span> => <span class="text-green-400">'replicate'</span>],
    <span class="text-green-400">'restore'</span>  => [<span class="text-green-400">'provider'</span> => <span class="text-green-400">'replicate'</span>],
],</code></pre>
                    </div>

                    <h3 id="storage" class="text-lg font-semibold text-white mb-3">Storage Configuration</h3>
                    <p class="text-gray-400 mb-4">Configure asset storage buckets in <code>config/photova.php</code>. Each bucket can use a different storage backend.</p>
                    
                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Available Disks</h4>
                    <div class="overflow-hidden rounded-lg border border-white/10 mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Disk</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Path</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">local</code></td>
                                    <td class="px-4 py-3 text-gray-500">storage/app/private</td>
                                    <td class="px-4 py-3 text-gray-400">Private storage, served through API only</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">public</code></td>
                                    <td class="px-4 py-3 text-gray-500">storage/app/public</td>
                                    <td class="px-4 py-3 text-gray-400">Public storage, directly accessible via URL</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">s3</code></td>
                                    <td class="px-4 py-3 text-gray-500">S3 bucket</td>
                                    <td class="px-4 py-3 text-gray-400">S3-compatible storage (AWS, MinIO, R2)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Multiple Buckets Example</h4>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden mb-6">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-white/10 bg-white/[0.02]">
                            <span class="text-xs text-gray-500">config/photova.php</span>
                        </div>
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">'storage'</span> => [
    <span class="text-green-400">'default'</span> => <span class="text-green-400">'assets'</span>,
    <span class="text-green-400">'buckets'</span> => [
        <span class="text-green-400">'assets'</span> => [
            <span class="text-green-400">'disk'</span> => <span class="text-green-400">'local'</span>,   <span class="text-gray-500">// Private, API-only access</span>
            <span class="text-green-400">'path'</span> => <span class="text-green-400">'assets'</span>,
        ],
        <span class="text-green-400">'thumbnails'</span> => [
            <span class="text-green-400">'disk'</span> => <span class="text-green-400">'public'</span>,  <span class="text-gray-500">// Direct URL access</span>
            <span class="text-green-400">'path'</span> => <span class="text-green-400">'thumbs'</span>,
        ],
        <span class="text-green-400">'cloud'</span> => [
            <span class="text-green-400">'disk'</span> => <span class="text-green-400">'s3'</span>,      <span class="text-gray-500">// S3-compatible storage</span>
            <span class="text-green-400">'path'</span> => <span class="text-green-400">'uploads'</span>,
        ],
    ],
],</code></pre>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">S3 Environment Variables</h4>
                    <p class="text-gray-400 mb-4">To use the <code>s3</code> disk, configure these in your <code>.env</code>:</p>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden mb-6">
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-gray-500"># AWS S3</span>
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=my-bucket

<span class="text-gray-500"># For MinIO or other S3-compatible services</span>
AWS_ENDPOINT=http://localhost:9000
AWS_USE_PATH_STYLE_ENDPOINT=true</code></pre>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Using Buckets</h4>
                    <p class="text-gray-400 mb-4">Specify which bucket to use with the <code>bucket</code> query parameter:</p>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden mb-8">
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-gray-500"># Upload to default bucket</span>
<span class="text-purple-400">curl</span> -X POST /api/assets -F 'file=@image.png'

<span class="text-gray-500"># Upload to specific bucket</span>
<span class="text-purple-400">curl</span> -X POST /api/assets<span class="text-yellow-400">?bucket=cloud</span> -F 'file=@image.png'

<span class="text-gray-500"># List assets in bucket</span>
<span class="text-purple-400">curl</span> /api/assets<span class="text-yellow-400">?bucket=cloud</span></code></pre>
                    </div>

                    <div class="p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                        <div class="flex items-start gap-3">
                            <span class="text-green-400 text-lg">✓</span>
                            <div>
                                <p class="text-sm text-green-300 font-medium mb-1">You're all set!</p>
                                <p class="text-sm text-green-200/70">Your API is now running at <code>http://localhost:8000</code>. Create an account and API key to start making requests.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Authentication -->
                <section id="authentication" class="mb-20">
                    <h2 class="text-2xl font-bold text-white mb-4">Authentication</h2>
                    <p class="text-gray-400 mb-6">
                        All API requests require authentication using an API key. You can create and manage your API keys from the 
                        <a href="/dashboard/keys" class="text-blue-400 hover:text-blue-300">Dashboard</a>.
                    </p>
                    <p class="text-gray-400 mb-6">
                        Include your API key in the <code class="text-white">Authorization</code> header using the Bearer scheme:
                    </p>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden mb-6">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-white/10 bg-white/[0.02]">
                            <span class="text-xs text-gray-500">Header</span>
                            <button @click="copy('auth')" class="text-xs text-gray-500 hover:text-white transition">
                                <span x-text="copied === 'auth' ? 'Copied!' : 'Copy'"></span>
                            </button>
                        </div>
                        <pre class="p-4 text-sm overflow-x-auto"><code class="text-gray-300">Authorization: Bearer br_live_xxxxx</code></pre>
                    </div>
                    <p class="text-gray-400 text-sm">
                        Alternatively, you can use the <code class="text-white">X-API-Key</code> header:
                    </p>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden mt-4">
                        <pre class="p-4 text-sm overflow-x-auto"><code class="text-gray-300">X-API-Key: br_live_xxxxx</code></pre>
                    </div>
                </section>

                <!-- Base URL -->
                <section id="base-url" class="mb-20">
                    <h2 class="text-2xl font-bold text-white mb-4">Base URL</h2>
                    <p class="text-gray-400 mb-6">All API requests should be made to:</p>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                        <pre class="p-4 text-sm overflow-x-auto"><code class="text-green-400">https://api.photova.app</code></pre>
                    </div>
                </section>

                <!-- Errors -->
                <section id="errors" class="mb-20">
                    <h2 class="text-2xl font-bold text-white mb-4">Errors</h2>
                    <p class="text-gray-400 mb-6">The API uses conventional HTTP response codes to indicate success or failure.</p>
                    <div class="overflow-hidden rounded-lg border border-white/10">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Code</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-green-400">200</code></td>
                                    <td class="px-4 py-3 text-gray-400">Success</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-yellow-400">400</code></td>
                                    <td class="px-4 py-3 text-gray-400">Bad Request - Invalid parameters</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-yellow-400">401</code></td>
                                    <td class="px-4 py-3 text-gray-400">Unauthorized - Invalid or missing API key</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-yellow-400">429</code></td>
                                    <td class="px-4 py-3 text-gray-400">Rate limited - Too many requests</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-red-400">500</code></td>
                                    <td class="px-4 py-3 text-gray-400">Server error</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-gray-400 mt-6 mb-4">Error responses include a JSON body with details:</p>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                        <pre class="p-4 text-sm overflow-x-auto"><code class="text-gray-300">{
  "<span class="text-red-400">error</span>": "<span class="text-green-400">Invalid API key</span>",
  "<span class="text-red-400">requestId</span>": "<span class="text-green-400">req_abc123</span>"
}</code></pre>
                    </div>
                </section>

                <!-- Operations Section Header -->
                <div class="mb-12 pb-6 border-b border-white/10">
                    <h2 class="text-3xl font-bold text-white mb-2">Operations</h2>
                    <p class="text-gray-400">AI-powered image processing endpoints</p>
                </div>

                <!-- Background Remove -->
                <section id="background-remove" class="mb-20">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded">POST</span>
                        <code class="text-white font-mono">/v1/background-remove</code>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Background Removal</h3>
                    <p class="text-gray-400 mb-6">Remove the background from an image, returning a transparent PNG.</p>
                    
                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Request Body</h4>
                    <div class="overflow-hidden rounded-lg border border-white/10 mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Parameter</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Type</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">image</code> <span class="text-red-400">*</span></td>
                                    <td class="px-4 py-3 text-gray-500">string</td>
                                    <td class="px-4 py-3 text-gray-400">Base64 encoded image (data URI)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Example Request</h4>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden mb-6">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-white/10 bg-white/[0.02]">
                            <span class="text-xs text-gray-500">cURL</span>
                            <button @click="copy('bg-remove')" class="text-xs text-gray-500 hover:text-white transition">
                                <span x-text="copied === 'bg-remove' ? 'Copied!' : 'Copy'"></span>
                            </button>
                        </div>
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">curl</span> -X POST https://api.photova.app/v1/background-remove \
  -H <span class="text-green-400">'Authorization: Bearer br_live_xxxxx'</span> \
  -H <span class="text-green-400">'Content-Type: application/json'</span> \
  -d <span class="text-green-400">'{"image": "data:image/png;base64,..."}'</span></code></pre>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Response</h4>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                        <pre class="p-4 text-sm overflow-x-auto"><code class="text-gray-300">{
  "<span class="text-red-400">image</span>": "<span class="text-green-400">data:image/png;base64,...</span>",
  "<span class="text-red-400">metadata</span>": {
    "<span class="text-red-400">provider</span>": "<span class="text-green-400">replicate</span>",
    "<span class="text-red-400">processingTime</span>": <span class="text-blue-400">1234</span>,
    "<span class="text-red-400">requestId</span>": "<span class="text-green-400">req_abc123</span>"
  }
}</code></pre>
                    </div>
                </section>

                <!-- Upscale -->
                <section id="upscale" class="mb-20">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded">POST</span>
                        <code class="text-white font-mono">/v1/upscale</code>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Upscale</h3>
                    <p class="text-gray-400 mb-6">Increase image resolution up to 4x using AI super-resolution.</p>
                    
                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Request Body</h4>
                    <div class="overflow-hidden rounded-lg border border-white/10 mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Parameter</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Type</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">image</code> <span class="text-red-400">*</span></td>
                                    <td class="px-4 py-3 text-gray-500">string</td>
                                    <td class="px-4 py-3 text-gray-400">Base64 encoded image (data URI)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">options.scale</code></td>
                                    <td class="px-4 py-3 text-gray-500">number</td>
                                    <td class="px-4 py-3 text-gray-400">Scale factor: 2 or 4 (default: 2)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Example Request</h4>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">curl</span> -X POST https://api.photova.app/v1/upscale \
  -H <span class="text-green-400">'Authorization: Bearer br_live_xxxxx'</span> \
  -H <span class="text-green-400">'Content-Type: application/json'</span> \
  -d <span class="text-green-400">'{"image": "data:image/png;base64,...", "options": {"scale": 4}}'</span></code></pre>
                    </div>
                </section>

                <!-- Restore -->
                <section id="restore" class="mb-20">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded">POST</span>
                        <code class="text-white font-mono">/v1/restore</code>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Restore</h3>
                    <p class="text-gray-400 mb-6">Restore old or damaged photos by fixing scratches, tears, and imperfections.</p>
                    
                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Request Body</h4>
                    <div class="overflow-hidden rounded-lg border border-white/10 mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Parameter</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Type</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">image</code> <span class="text-red-400">*</span></td>
                                    <td class="px-4 py-3 text-gray-500">string</td>
                                    <td class="px-4 py-3 text-gray-400">Base64 encoded image (data URI)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Example Request</h4>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">curl</span> -X POST https://api.photova.app/v1/restore \
  -H <span class="text-green-400">'Authorization: Bearer br_live_xxxxx'</span> \
  -H <span class="text-green-400">'Content-Type: application/json'</span> \
  -d <span class="text-green-400">'{"image": "data:image/png;base64,..."}'</span></code></pre>
                    </div>
                </section>

                <!-- Colorize -->
                <section id="colorize" class="mb-20">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded">POST</span>
                        <code class="text-white font-mono">/v1/colorize</code>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Colorize</h3>
                    <p class="text-gray-400 mb-6">Add realistic color to black and white images using AI.</p>
                    
                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Request Body</h4>
                    <div class="overflow-hidden rounded-lg border border-white/10 mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Parameter</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Type</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">image</code> <span class="text-red-400">*</span></td>
                                    <td class="px-4 py-3 text-gray-500">string</td>
                                    <td class="px-4 py-3 text-gray-400">Base64 encoded image (data URI)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Example Request</h4>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">curl</span> -X POST https://api.photova.app/v1/colorize \
  -H <span class="text-green-400">'Authorization: Bearer br_live_xxxxx'</span> \
  -H <span class="text-green-400">'Content-Type: application/json'</span> \
  -d <span class="text-green-400">'{"image": "data:image/png;base64,..."}'</span></code></pre>
                    </div>
                </section>

                <!-- Unblur -->
                <section id="unblur" class="mb-20">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded">POST</span>
                        <code class="text-white font-mono">/v1/unblur</code>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Deblur</h3>
                    <p class="text-gray-400 mb-6">Sharpen blurry images and restore clarity using AI deblurring.</p>
                    
                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Request Body</h4>
                    <div class="overflow-hidden rounded-lg border border-white/10 mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Parameter</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Type</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">image</code> <span class="text-red-400">*</span></td>
                                    <td class="px-4 py-3 text-gray-500">string</td>
                                    <td class="px-4 py-3 text-gray-400">Base64 encoded image (data URI)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Example Request</h4>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">curl</span> -X POST https://api.photova.app/v1/unblur \
  -H <span class="text-green-400">'Authorization: Bearer br_live_xxxxx'</span> \
  -H <span class="text-green-400">'Content-Type: application/json'</span> \
  -d <span class="text-green-400">'{"image": "data:image/png;base64,..."}'</span></code></pre>
                    </div>
                </section>

                <!-- Inpaint -->
                <section id="inpaint" class="mb-20">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded">POST</span>
                        <code class="text-white font-mono">/v1/inpaint</code>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Object Removal (Inpaint)</h3>
                    <p class="text-gray-400 mb-6">Remove unwanted objects from images by providing a mask.</p>
                    
                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Request Body</h4>
                    <div class="overflow-hidden rounded-lg border border-white/10 mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-white/[0.02]">
                                <tr>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Parameter</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Type</th>
                                    <th class="text-left px-4 py-3 text-gray-400 font-medium">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">image</code> <span class="text-red-400">*</span></td>
                                    <td class="px-4 py-3 text-gray-500">string</td>
                                    <td class="px-4 py-3 text-gray-400">Base64 encoded image (data URI)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">options.mask</code></td>
                                    <td class="px-4 py-3 text-gray-500">string</td>
                                    <td class="px-4 py-3 text-gray-400">Base64 mask image (white = remove)</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3"><code class="text-white">options.prompt</code></td>
                                    <td class="px-4 py-3 text-gray-500">string</td>
                                    <td class="px-4 py-3 text-gray-400">Optional prompt for what to fill</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Example Request</h4>
                    <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                        <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">curl</span> -X POST https://api.photova.app/v1/inpaint \
  -H <span class="text-green-400">'Authorization: Bearer br_live_xxxxx'</span> \
  -H <span class="text-green-400">'Content-Type: application/json'</span> \
  -d <span class="text-green-400">'{"image": "data:image/png;base64,...", "options": {"mask": "data:image/png;base64,..."}}'</span></code></pre>
                    </div>
                </section>

                <!-- Resources Section Header -->
                <div class="mb-12 pb-6 border-b border-white/10">
                    <h2 class="text-3xl font-bold text-white mb-2">Resources</h2>
                    <p class="text-gray-400">Manage assets and track usage</p>
                </div>

                <!-- Assets -->
                <section id="assets" class="mb-20">
                    <h3 class="text-xl font-bold text-white mb-6">Assets</h3>
                    <p class="text-gray-400 mb-8">Upload and manage images in your account storage.</p>
                    
                    <!-- Upload Asset -->
                    <div class="mb-12">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded">POST</span>
                            <code class="text-white font-mono">/api/assets</code>
                        </div>
                        <h4 class="text-lg font-semibold text-white mb-3">Upload Asset</h4>
                        <p class="text-gray-400 mb-4">Upload an image to storage. Supports both file upload and base64.</p>
                        <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                            <pre class="p-4 text-sm overflow-x-auto"><code><span class="text-purple-400">curl</span> -X POST https://api.photova.app/api/assets \
  -H <span class="text-green-400">'Authorization: Bearer br_live_xxxxx'</span> \
  -F <span class="text-green-400">'file=@image.png'</span></code></pre>
                        </div>
                    </div>

                    <!-- List Assets -->
                    <div class="mb-12">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs font-mono rounded">GET</span>
                            <code class="text-white font-mono">/api/assets</code>
                        </div>
                        <h4 class="text-lg font-semibold text-white mb-3">List Assets</h4>
                        <p class="text-gray-400 mb-4">Retrieve a list of all uploaded assets.</p>
                    </div>

                    <!-- Get Asset -->
                    <div class="mb-12">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs font-mono rounded">GET</span>
                            <code class="text-white font-mono">/api/assets/{`{id}`}</code>
                        </div>
                        <h4 class="text-lg font-semibold text-white mb-3">Get Asset</h4>
                        <p class="text-gray-400 mb-4">Retrieve asset metadata. Add <code>?download=true</code> to download the file.</p>
                    </div>

                    <!-- Delete Asset -->
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-mono rounded">DELETE</span>
                            <code class="text-white font-mono">/api/assets/{`{id}`}</code>
                        </div>
                        <h4 class="text-lg font-semibold text-white mb-3">Delete Asset</h4>
                        <p class="text-gray-400 mb-4">Permanently delete an asset.</p>
                    </div>
                </section>

                <!-- Usage -->
                <section id="usage" class="mb-20">
                    <h3 class="text-xl font-bold text-white mb-6">Usage</h3>
                    <p class="text-gray-400 mb-8">Track your API usage and billing.</p>
                    
                    <!-- Usage Summary -->
                    <div class="mb-12">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs font-mono rounded">GET</span>
                            <code class="text-white font-mono">/api/usage/summary</code>
                        </div>
                        <h4 class="text-lg font-semibold text-white mb-3">Usage Summary</h4>
                        <p class="text-gray-400 mb-4">Get aggregated usage statistics for the current billing period.</p>
                        <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                            <pre class="p-4 text-sm overflow-x-auto"><code class="text-gray-300">{
  "<span class="text-red-400">summary</span>": {
    "<span class="text-red-400">totalRequests</span>": <span class="text-blue-400">1234</span>,
    "<span class="text-red-400">totalErrors</span>": <span class="text-blue-400">12</span>,
    "<span class="text-red-400">averageLatencyMs</span>": <span class="text-blue-400">856</span>,
    "<span class="text-red-400">byOperation</span>": {
      "<span class="text-red-400">background-remove</span>": { "<span class="text-red-400">requests</span>": <span class="text-blue-400">500</span>, "<span class="text-red-400">errors</span>": <span class="text-blue-400">5</span> },
      "<span class="text-red-400">upscale</span>": { "<span class="text-red-400">requests</span>": <span class="text-blue-400">734</span>, "<span class="text-red-400">errors</span>": <span class="text-blue-400">7</span> }
    }
  }
}</code></pre>
                        </div>
                    </div>

                    <!-- Usage Current -->
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs font-mono rounded">GET</span>
                            <code class="text-white font-mono">/api/usage/current</code>
                        </div>
                        <h4 class="text-lg font-semibold text-white mb-3">Current Usage</h4>
                        <p class="text-gray-400 mb-4">Get current month usage against your plan limits.</p>
                        <div class="relative bg-[#111] rounded-lg border border-white/10 overflow-hidden">
                            <pre class="p-4 text-sm overflow-x-auto"><code class="text-gray-300">{
  "<span class="text-red-400">current</span>": {
    "<span class="text-red-400">used</span>": <span class="text-blue-400">1234</span>,
    "<span class="text-red-400">limit</span>": <span class="text-blue-400">10000</span>,
    "<span class="text-red-400">remaining</span>": <span class="text-blue-400">8766</span>
  }
}</code></pre>
                        </div>
                    </div>
                </section>

                <!-- Footer -->
                <div class="pt-12 border-t border-white/10 text-center">
                    <p class="text-gray-500 text-sm">
                        Need help? <a href="mailto:support@photova.app" class="text-blue-400 hover:text-blue-300">Contact support</a>
                    </p>
                </div>

            </div>
        </main>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('docs', () => ({
                copied: null,
                copy(id) {
                    const snippets = {
                        'auth': 'Authorization: Bearer br_live_xxxxx',
                        'bg-remove': `curl -X POST https://api.photova.app/v1/background-remove \\
  -H 'Authorization: Bearer br_live_xxxxx' \\
  -H 'Content-Type: application/json' \\
  -d '{"image": "data:image/png;base64,..."}'`
                    };
                    navigator.clipboard.writeText(snippets[id] || '');
                    this.copied = id;
                    setTimeout(() => this.copied = null, 2000);
                }
            }));
        });
    </script>
</body>
</html>
