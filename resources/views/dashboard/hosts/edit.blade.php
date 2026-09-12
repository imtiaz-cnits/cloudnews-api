@extends('layouts.admin')

@section('title', 'Edit Host')
@section('page_heading', 'Edit Host Account')

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
        <div class="mb-6 pb-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-white tracking-tight">Edit Host: {{ $host->name }}</h2>
                <p class="text-xs text-slate-400 mt-0.5">User ID: #{{ $host->id }} &bull; Role: {{ ucfirst($host->role) }}</p>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-sky-500/10 text-sky-400 text-xs font-mono border border-sky-500/20">
                {{ $host->hostedMeetings()->count() }} {{ Str::plural('meeting', $host->hostedMeetings()->count()) }}
            </span>
        </div>

        <form method="POST" action="{{ route('dashboard.hosts.update', $host->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                    Full Name <span class="text-rose-400">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $host->name) }}" required
                    class="w-full px-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white text-sm focus:outline-none focus:border-sky-500 transition">
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
                        <input type="text" id="username" name="username" value="{{ old('username', $host->username) }}"
                            placeholder="username"
                            class="w-full pl-8 pr-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white text-sm focus:outline-none focus:border-sky-500 transition">
                    </div>
                    @error('username')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                        Email Address <span class="text-rose-400">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email', $host->email) }}" required
                        class="w-full px-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white text-sm focus:outline-none focus:border-sky-500 transition">
                    @error('email')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Change Password -->
            <div>
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                    Change Password <span class="text-slate-500 font-normal">(Optional)</span>
                </label>
                <input type="password" id="password" name="password"
                    placeholder="Leave blank to keep current password"
                    class="w-full px-4 py-3 rounded-xl bg-[#060D17] border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-sky-500 transition">
                <p class="text-[11px] text-slate-500 mt-1">Leave empty if you do not wish to change the host's existing password.</p>
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
                    Save Changes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
