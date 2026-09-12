@extends('layouts.admin')

@section('title', 'Add New Host')
@section('page_heading', 'Provision New Meeting Host')

@section('content')
<div class="max-w-2xl mx-auto">
    
    <!-- Breadcrumb / Back Link -->
    <div class="mb-6">
        <a href="{{ route('dashboard.hosts.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-sky-400 transition">
            &larr; Back to Host Accounts
        </a>
    </div>

    <!-- Form Panel -->
    <div class="glass-panel rounded-2xl border border-brand-border p-6 md:p-8 shadow-2xl">
        <div class="mb-6 pb-4 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white tracking-tight">Host Account Information</h2>
            <p class="text-xs text-slate-400 mt-0.5">This user will have privileges to create rooms and initiate video conferences.</p>
        </div>

        <form method="POST" action="{{ route('dashboard.hosts.store') }}" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                    Full Name <span class="text-rose-400">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                    placeholder="e.g. Professor Sarah Connor"
                    class="w-full px-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-sky-500 transition">
                @error('name')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Username & Email Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                        Username <span class="text-slate-500 font-normal">(Optional)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm">&#64;</span>
                        <input type="text" id="username" name="username" value="{{ old('username') }}"
                            placeholder="sarah_host"
                            class="w-full pl-8 pr-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-sky-500 transition">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Allows login without typing full email.</p>
                    @error('username')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                        Email Address <span class="text-rose-400">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        placeholder="sarah@example.com"
                        class="w-full px-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-sky-500 transition">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                    Password <span class="text-slate-500 font-normal">(Leave blank to auto-generate)</span>
                </label>
                <input type="password" id="password" name="password"
                    placeholder="••••••••••••"
                    class="w-full px-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-sky-500 transition">
                <p class="text-[11px] text-slate-400 mt-1.5 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                    If left empty, a secure 12-character random password will be created and shown upon save.
                </p>
                @error('password')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('dashboard.hosts.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-500 hover:to-sky-400 text-white font-bold text-xs shadow-lg shadow-sky-500/20 transition">
                    Create Host Account
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
