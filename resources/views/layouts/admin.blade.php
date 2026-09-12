<!DOCTYPE html>
<html lang="en" class="h-full bg-[#040912]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') | Cloud News Meet (云讯)</title>

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
                            cardLight: '#132238',
                            border: 'rgba(56, 189, 248, 0.22)',
                            hover: '#0369A1'
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

        .glass-panel {
            background: rgba(11, 23, 40, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(56, 189, 248, 0.3);
            border-radius: 9999px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(4, 9, 18, 0.5);
        }
    </style>
</head>

<body class="h-full text-slate-200 flex flex-col antialiased">
    <div class="flex h-full min-h-screen overflow-hidden">

        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-[#081220] border-r border-brand-border flex-shrink-0 flex flex-col justify-between hidden md:flex">
            <div>
                <!-- Brand Header -->
                <div class="h-16 flex items-center px-6 border-b border-brand-border gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-600 to-sky-400 flex items-center justify-center shadow-lg shadow-sky-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-white text-base tracking-tight flex items-center gap-1.5">
                            Cloud News <span class="text-xs px-1.5 py-0.5 rounded bg-sky-500/15 text-sky-400 font-mono">云讯</span>
                        </div>
                        <div class="text-[11px] text-slate-400 font-medium tracking-wide uppercase">Admin Portal</div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="p-4 space-y-1.5">
                    <a href="{{ route('dashboard.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard.index') ? 'bg-sky-500/15 text-sky-300 border border-sky-500/30 font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('dashboard.index') ? 'text-sky-400' : 'text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>

                    <a href="{{ route('dashboard.hosts.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard.hosts.*') ? 'bg-sky-500/15 text-sky-300 border border-sky-500/30 font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('dashboard.hosts.*') ? 'text-sky-400' : 'text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Host Users
                    </a>

                    <div class="pt-4 pb-2 px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        External
                    </div>

                    <a href="{{ route('home') }}" target="_blank" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 transition-all">
                        <span class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            View Landing Page
                        </span>
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </nav>
            </div>

            <!-- User Footer in Sidebar -->
            <div class="p-4 border-t border-brand-border bg-[#060e19]">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center font-bold text-white text-sm">
                        {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="overflow-hidden flex-1">
                        <div class="text-sm font-semibold text-white truncate">{{ Auth::user()->name ?? 'Administrator' }}</div>
                        <div class="text-xs text-sky-400 truncate">Super Admin</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('dashboard.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-semibold border border-rose-500/20 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Log Out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-[#040912]">

            <!-- Top Navbar -->
            <header class="h-16 border-b border-brand-border bg-[#0B1728]/70 backdrop-blur-md flex items-center justify-between px-6 z-10 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-bold text-white tracking-tight">@yield('page_heading', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-xs text-slate-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        LiveKit SFU Engine Ready
                    </div>

                    <a href="{{ route('home') }}" class="text-xs font-medium text-slate-400 hover:text-white px-3 py-1.5 rounded-lg border border-slate-800 hover:bg-slate-800/60 transition hidden md:inline-block">
                        cloudnewsmeet.com &nearr;
                    </a>

                    <form method="POST" action="{{ route('dashboard.logout') }}" class="md:hidden">
                        @csrf
                        <button type="submit" class="text-xs text-rose-400 font-semibold px-2.5 py-1.5 rounded bg-rose-500/10 border border-rose-500/20">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <!-- Scrollable Page Body -->
            <main class="flex-1 overflow-y-auto custom-scrollbar p-6 md:p-8">

                <!-- Flash Messages -->
                @if(session('success'))
                <div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm shadow-lg shadow-emerald-500/5">
                    <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm shadow-lg shadow-rose-500/5">
                    <svg class="w-5 h-5 text-rose-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm">
                    <div class="font-bold mb-1 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Please correct the following errors:
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-300/90">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @yield('scripts')
</body>

</html>