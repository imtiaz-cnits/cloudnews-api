<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#040912]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud News (云讯) Meet | Next-Gen Real-Time Video Conferencing</title>
    <meta name="description" content="Decoupled real-time WebRTC video conferencing powered by LiveKit SFU and Laravel backend.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            primary: '#0284C7',
                            accent: '#38BDF8',
                            dark: '#040912',
                            card: '#0B1728',
                            cardLight: '#11223B',
                            border: 'rgba(56, 189, 248, 0.22)',
                            glow: 'rgba(2, 132, 199, 0.35)'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .hero-glow {
            background: radial-gradient(circle at 50% 20%, rgba(2, 132, 199, 0.25) 0%, rgba(4, 9, 18, 0) 70%);
        }
    </style>
</head>

<body class="text-slate-200 antialiased min-h-screen flex flex-col bg-[#040912]">

    <!-- Navigation Bar -->
    <header class="sticky top-0 z-50 backdrop-blur-xl bg-[#040912]/80 border-b border-brand-border">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <!-- Brand -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-600 to-sky-400 flex items-center justify-center shadow-lg shadow-sky-500/25 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <span class="text-xl font-extrabold text-white tracking-tight flex items-center gap-2">
                        Cloud News <span class="text-xs px-2 py-0.5 rounded-md bg-sky-500/15 text-sky-400 font-mono border border-sky-500/30">云讯</span>
                    </span>
                    <span class="text-[11px] text-slate-400 tracking-wider uppercase block font-medium">cloudnewsmeet.com</span>
                </div>
            </a>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="#features" class="hover:text-sky-400 transition">Features</a>
                <a href="#architecture" class="hover:text-sky-400 transition">Architecture</a>
                <a href="#mobile" class="hover:text-sky-400 transition">Mobile App</a>
                <a href="#metrics" class="hover:text-sky-400 transition">Network Stats</a>
            </nav>

            <!-- Action Button -->
            <div class="flex items-center gap-3">
                @auth
                @if(Auth::user()->role === 'admin')
                <a href="{{ route('dashboard.index') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-sky-500 text-white font-semibold text-sm shadow-lg shadow-sky-500/20 hover:from-sky-500 hover:to-sky-400 transition">
                    Admin Dashboard &rarr;
                </a>
                @else
                <span class="text-xs text-slate-400">Logged in as {{ Auth::user()->name }}</span>
                @endif
                @else
                <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold text-sm transition">
                    Host Login
                </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 hero-glow">

        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-6 pt-16 pb-24 md:pt-24 md:pb-32 text-center">

            <!-- Live Status Pill -->
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-sky-500/10 border border-sky-500/30 text-sky-300 text-xs font-semibold mb-8 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Decoupled SFU Real-Time Video Conferencing</span>
            </div>

            <!-- Headline -->
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white tracking-tight max-w-5xl mx-auto leading-tight md:leading-[1.15]">
                Ultra-Low Latency Meetings <br class="hidden sm:inline">
                Powered by <span class="bg-gradient-to-r from-sky-400 via-sky-300 to-cyan-400 bg-clip-text text-transparent">LiveKit WebRTC</span>
            </h1>

            <!-- Subtitle -->
            <p class="mt-6 text-lg md:text-xl text-slate-400 max-w-2xl mx-auto font-normal leading-relaxed">
                Connect your teams with crystal-clear 1080p video, real-time audio sync, and state-of-the-art token security engineered for mobile & desktop.
            </p>

            <!-- CTA Buttons -->
            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('login') }}" class="px-8 py-4 rounded-xl bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-500 hover:to-sky-400 text-white font-bold text-base shadow-xl shadow-sky-500/25 transition transform hover:-translate-y-0.5">
                    Admin & Host Dashboard
                </a>
                <a href="#mobile" class="px-8 py-4 rounded-xl bg-[#0B1728] hover:bg-[#132238] text-slate-200 border border-brand-border font-semibold text-base transition">
                    Explore Mobile App
                </a>
            </div>

            <!-- Hero Video Interface Mockup -->
            <div class="mt-16 max-w-4xl mx-auto rounded-2xl bg-[#0B1728]/90 border border-brand-border p-4 sm:p-6 shadow-2xl shadow-sky-950/50 backdrop-blur-xl">
                <!-- App Window Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-800 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                        <span class="ml-2 text-slate-400 font-mono font-medium">Room: live-conference-global</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-0.5 rounded bg-rose-500/20 text-rose-400 font-bold tracking-wider text-[10px]">LIVE</span>
                        <span class="text-slate-400 font-mono text-xs">00:18:42</span>
                    </div>
                </div>

                <!-- Video Grid Preview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-4">
                    <!-- Participant 1 (Host) -->
                    <div class="relative aspect-video rounded-xl bg-gradient-to-br from-slate-900 to-slate-950 border border-sky-500/40 overflow-hidden flex items-center justify-center group shadow-md">
                        <div class="w-20 h-20 rounded-full bg-sky-500/20 border-2 border-sky-400 flex items-center justify-center text-sky-400 font-bold text-xl">
                            Host
                        </div>
                        <div class="absolute bottom-3 left-3 px-3 py-1 rounded-md bg-black/60 backdrop-blur-md text-xs font-semibold text-white flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Sarah Connor (Host)
                        </div>
                        <div class="absolute bottom-3 right-3 p-1.5 rounded-md bg-black/60 text-slate-300">
                            <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    <!-- Participant 2 -->
                    <div class="relative aspect-video rounded-xl bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 overflow-hidden flex items-center justify-center shadow-md">
                        <div class="w-20 h-20 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-slate-300 font-bold text-xl">
                            Alex
                        </div>
                        <div class="absolute bottom-3 left-3 px-3 py-1 rounded-md bg-black/60 backdrop-blur-md text-xs font-semibold text-white flex items-center gap-2">
                            Alex Johnson (Mobile App)
                        </div>
                        <div class="absolute bottom-3 right-3 p-1.5 rounded-md bg-black/60 text-slate-300">
                            <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Control Bar -->
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button class="w-10 h-10 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-200 flex items-center justify-center transition">
                        <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button class="w-10 h-10 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-200 flex items-center justify-center transition">
                        <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <button class="px-5 h-10 rounded-full bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-rose-600/30">
                        Leave Room
                    </button>
                </div>
            </div>
        </section>

        <!-- Stats Counter Section -->
        <section id="metrics" class="border-y border-brand-border bg-[#0B1728]/50 py-12">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-white font-mono">{{ $activeMeetingsCount ?? 0 }}</div>
                    <div class="text-xs md:text-sm text-slate-400 mt-1 font-medium">Active Meeting Rooms</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-white font-mono">{{ $totalHostsCount ?? 0 }}+</div>
                    <div class="text-xs md:text-sm text-slate-400 mt-1 font-medium">Registered Hosts</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-sky-400 font-mono">&lt; 100ms</div>
                    <div class="text-xs md:text-sm text-slate-400 mt-1 font-medium">Live SFU Latency</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-emerald-400 font-mono">100%</div>
                    <div class="text-xs md:text-sm text-slate-400 mt-1 font-medium">JWT Secure Encrypted</div>
                </div>
            </div>
        </section>

        <!-- Core Features Section -->
        <section id="features" class="max-w-7xl mx-auto px-6 py-24">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-bold text-sky-400 tracking-wider uppercase">Enterprise Capabilities</h2>
                <p class="text-3xl md:text-4xl font-extrabold text-white mt-2">Engineered for High-Concurrency Live Audio & Video</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="p-8 rounded-2xl bg-[#0B1728] border border-brand-border hover:border-sky-500/50 transition duration-300">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">LiveKit SFU Architecture</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Selective Forwarding Unit ensures peer traffic is balanced dynamically with adaptive stream resolutions for low data usage.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-8 rounded-2xl bg-[#0B1728] border border-brand-border hover:border-sky-500/50 transition duration-300">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Stateless JWT Auth</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Short-lived, signed video grants prevent unauthorized room snooping while decoupling the mobile client from the SFU engine.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-8 rounded-2xl bg-[#0B1728] border border-brand-border hover:border-sky-500/50 transition duration-300">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400 mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Host Administration</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Centralized web dashboard for provisioning dedicated meeting hosts, resetting credentials, and tracking room performance.
                    </p>
                </div>
            </div>
        </section>

        <!-- Mobile App Feature Section -->
        <section id="mobile" class="py-20 bg-gradient-to-b from-transparent to-[#081220] border-t border-brand-border">
            <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-xs font-bold text-sky-400 uppercase tracking-wider">Mobile Experience</span>
                    <h2 class="text-3xl md:text-5xl font-extrabold text-white mt-3 leading-tight">
                        Built For On-The-Go Collaboration
                    </h2>
                    <p class="text-slate-400 mt-4 text-base leading-relaxed">
                        The Cloud News mobile app delivers full WebRTC conferencing with hardware-accelerated video rendering, intuitive meeting controls, and instant room entry without complex setup.
                    </p>

                    <div class="mt-8 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">&check;</div>
                            <span class="text-slate-300 text-sm font-medium">Seamless Android (APK) & iOS Support</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">&check;</div>
                            <span class="text-slate-300 text-sm font-medium">Automatic Echo Cancellation & Noise Suppression</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">&check;</div>
                            <span class="text-slate-300 text-sm font-medium">Dynamic Mesh & Multicast Screen Sharing</span>
                        </div>
                    </div>
                </div>

                <!-- Visual App Card -->
                <div class="rounded-3xl bg-[#0B1728] border border-brand-border p-8 text-center shadow-2xl relative overflow-hidden">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-sky-600 to-sky-400 flex items-center justify-center mx-auto shadow-xl shadow-sky-500/30 mb-6">
                        <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white">Cloud News Mobile Client</h3>
                    <p class="text-sm text-slate-400 mt-2 max-w-sm mx-auto">
                        React Native CLI native app with LiveKit WebRTC drivers.
                    </p>
                    <div class="mt-6 flex justify-center gap-3">
                        <span class="px-3.5 py-1.5 rounded-lg bg-slate-800 text-xs font-mono text-slate-300 border border-slate-700">React Native 0.87</span>
                        <span class="px-3.5 py-1.5 rounded-lg bg-slate-800 text-xs font-mono text-slate-300 border border-slate-700">WebRTC 144</span>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="border-t border-brand-border bg-[#03070e] py-12">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-sky-600 flex items-center justify-center text-white font-bold text-sm">
                    云
                </div>
                <span class="text-sm font-semibold text-white">Cloud News Meet &copy; {{ date('Y') }}</span>
                <span class="text-xs text-slate-500">| cloudnewsmeet.com</span>
            </div>

            <div class="flex items-center gap-6 text-xs text-slate-400">
                <a href="{{ route('login') }}" class="hover:text-white transition">Admin Portal</a>
                <a href="#features" class="hover:text-white transition">Features</a>
                <a href="#metrics" class="hover:text-white transition">Status</a>
            </div>
        </div>
    </footer>

</body>

</html>