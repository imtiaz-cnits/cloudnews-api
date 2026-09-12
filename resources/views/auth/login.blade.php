<!DOCTYPE html>
<html lang="en" class="h-full bg-[#040912]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Cloud News Meet (云讯)</title>

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
                            border: 'rgba(56, 189, 248, 0.22)'
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
    </style>
</head>

<body class="h-full flex items-center justify-center p-6 text-slate-200 antialiased bg-[#040912] relative overflow-hidden">

    <!-- Background Gradient Orb -->
    <div class="absolute w-[500px] h-[500px] bg-sky-600/15 rounded-full blur-3xl pointer-events-none -top-40 -left-40"></div>
    <div class="absolute w-[400px] h-[400px] bg-indigo-600/10 rounded-full blur-3xl pointer-events-none -bottom-20 -right-20"></div>

    <div class="w-full max-w-md relative z-10">

        <!-- Brand Header -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-600 to-sky-400 flex items-center justify-center shadow-lg shadow-sky-500/30 group-hover:scale-105 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
            </a>
            <h1 class="mt-4 text-2xl font-extrabold text-white tracking-tight flex items-center justify-center gap-2">
                Cloud News <span class="text-xs px-2 py-0.5 rounded bg-sky-500/20 text-sky-400 font-mono border border-sky-500/30">云讯</span>
            </h1>
            <p class="mt-1 text-sm text-slate-400">Admin Dashboard Authentication</p>
        </div>

        <!-- Glassmorphic Card -->
        <div class="bg-[#0B1728]/90 border border-brand-border rounded-2xl p-8 shadow-2xl backdrop-blur-xl">

            <!-- Error Alert -->
            @if(session('error'))
            <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @if($errors->has('login'))
            <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $errors->first('login') }}</span>
            </div>
            @endif

            @if(session('success'))
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                @csrf

                <!-- Username or Email Field -->
                <div>
                    <label for="login" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                        Username or Email Address
                    </label>
                    <div class="relative">
                        <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus
                            placeholder="admin or admin@cloudnewsmeet.com"
                            class="w-full px-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition">
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••••••"
                            class="w-full px-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition">
                    </div>
                    @error('password')
                    <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-sky-600 focus:ring-sky-500 focus:ring-offset-slate-900">
                        <span class="text-xs text-slate-400">Remember session</span>
                    </label>
                    <span class="text-xs text-slate-500 font-mono">Protected Area</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-500 hover:to-sky-400 text-white font-bold text-sm shadow-lg shadow-sky-500/25 transition transform active:scale-[0.98]">
                    Sign In to Dashboard
                </button>
            </form>
        </div>

        <!-- Back to Site Link -->
        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-xs font-medium text-slate-400 hover:text-sky-400 transition inline-flex items-center gap-1.5">
                &larr; Back to Public Landing Page
            </a>
        </div>
    </div>

</body>

</html>