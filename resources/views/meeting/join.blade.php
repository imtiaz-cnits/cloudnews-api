<!DOCTYPE html>
<html lang="en" class="bg-[#040912]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $meeting ? $meeting->title : 'Join Meeting' }} | Cloud News (云讯)</title>
    <meta name="description" content="Join Cloud News meeting {{ $meetingCode }}. High-definition real-time video conferencing.">

    <!-- Open Graph for WhatsApp, Facebook, Telegram -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $meeting ? $meeting->title : 'Cloud News Video Meeting' }} ({{ $meetingCode }})">
    <meta property="og:description" content="Tap to join video call on Cloud News (云讯). Meeting ID: {{ $meetingCode }}">
    <meta property="og:url" content="{{ url('/room/' . $meetingCode) }}">
    <meta property="og:site_name" content="Cloud News Meet">

    <!-- Meta refresh fallback for mobile devices -->
    <meta http-equiv="refresh" content="1;url={{ $deepLinkScheme }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
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
            background: #040912;
        }

        .ambient-glow {
            background: radial-gradient(circle at 50% 20%, rgba(2, 132, 199, 0.28) 0%, rgba(4, 9, 18, 0) 75%);
        }

        .pulse-animation {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse-ring {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.1); }
        }
    </style>

    <!-- Immediate App Launch Script -->
    <script>
        const deepLinkScheme = "{{ $deepLinkScheme }}";
        const androidIntent = "{{ $androidIntent }}";

        function triggerAppLaunch() {
            const isAndroid = /Android/i.test(navigator.userAgent);
            const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);

            if (isAndroid) {
                // Best reliability on Android Chrome, WhatsApp webview & Facebook browser
                window.location.href = androidIntent;
                setTimeout(() => {
                    window.location.href = deepLinkScheme;
                }, 400);
            } else if (isIOS) {
                window.location.href = deepLinkScheme;
            } else {
                window.location.href = deepLinkScheme;
            }
        }

        // Trigger automatically on page load
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(triggerAppLaunch, 150);
        });
    </script>
</head>

<body class="text-slate-100 min-h-screen flex flex-col items-center justify-between p-4 sm:p-6 ambient-glow">

    <!-- Header / Brand -->
    <header class="w-full max-w-md flex items-center justify-between py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-600 to-sky-400 flex items-center justify-center shadow-lg shadow-sky-500/25">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <span class="font-bold text-white text-base tracking-tight block">Cloud News</span>
                <span class="text-[10px] text-sky-400 font-semibold tracking-wider uppercase block">云讯视频会议</span>
            </div>
        </a>

        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-animation"></span>
            Live Ready
        </span>
    </header>

    <!-- Main Card -->
    <main class="w-full max-w-md my-auto py-4">
        <div class="bg-[#0B1728]/90 backdrop-blur-2xl border border-brand-border rounded-3xl p-6 sm:p-8 shadow-2xl shadow-sky-950/50">

            <!-- Icon Header -->
            <div class="flex justify-center mb-5">
                <div class="w-16 h-16 rounded-2xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 shadow-inner">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>

            <!-- Title & Host -->
            <div class="text-center mb-6">
                <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight mb-2">
                    {{ $meeting ? $meeting->title : 'Cloud News Meeting' }}
                </h1>
                <p class="text-sm text-slate-400">
                    Hosted by <span class="text-sky-300 font-medium">{{ $meeting && $meeting->host ? $meeting->host->name : 'Cloud News Host' }}</span>
                </p>
            </div>

            <!-- Meeting Code Box -->
            <div class="bg-black/50 border border-white/10 rounded-2xl p-4 mb-6 text-center">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Meeting ID</span>
                <div class="flex items-center justify-center gap-3">
                    <span id="meetingCodeText" class="font-mono text-2xl font-bold tracking-widest text-sky-400">{{ $meetingCode }}</span>
                    <button onclick="copyCode()" class="p-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition" title="Copy ID">
                        <svg id="copyIcon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
                <p id="copyStatus" class="text-[11px] text-emerald-400 font-medium mt-1 hidden">Copied to clipboard!</p>
            </div>

            <!-- Primary Action: Open App -->
            <button onclick="triggerAppLaunch()" class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-sky-600 to-sky-400 hover:from-sky-500 hover:to-sky-300 text-white font-bold text-base shadow-lg shadow-sky-500/30 active:scale-[0.98] transition-all flex items-center justify-center gap-2 mb-3">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Open in Cloud News App
            </button>

            <!-- Helper Notice -->
            <p class="text-xs text-center text-slate-400 leading-relaxed">
                App should open automatically. Tap button above to join as guest directly in the app.
            </p>

            <!-- App Installation Help -->
            <div class="mt-6 pt-5 border-t border-white/10 text-center">
                <p class="text-xs text-slate-400 mb-2">Don't have Cloud News installed?</p>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-sky-400 hover:text-sky-300 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Cloud News App
                </a>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-md text-center py-4 text-xs text-slate-500">
        &copy; {{ date('Y') }} Cloud News (云讯) Meet. Ultra HD WebRTC Conferencing.
    </footer>

    <script>
        function copyCode() {
            const code = document.getElementById('meetingCodeText').innerText;
            navigator.clipboard.writeText(code).then(() => {
                const status = document.getElementById('copyStatus');
                status.classList.remove('hidden');
                setTimeout(() => status.classList.add('hidden'), 2500);
            });
        }
    </script>
</body>

</html>
