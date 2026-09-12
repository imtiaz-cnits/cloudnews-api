@extends('layouts.admin')

@section('title', 'Overview')
@section('page_heading', 'System Overview')

@section('content')
<div class="space-y-8">

    <!-- Top Welcome Banner -->
    <div class="p-6 md:p-8 rounded-2xl bg-gradient-to-r from-sky-950/60 via-slate-900/60 to-slate-900 border border-brand-border flex flex-col md:flex-row items-start md:items-center justify-between gap-6 shadow-xl backdrop-blur-md">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-500/15 border border-sky-500/30 text-sky-400 text-xs font-semibold mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>System Health: Operational</span>
            </div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
                Welcome to Cloud News (云讯) Admin
            </h2>
            <p class="text-sm text-slate-400 mt-1 max-w-2xl">
                Provision meeting hosts, monitor real-time LiveKit rooms, and configure video infrastructure for <span class="text-sky-300 font-mono">cloudnewsmeet.com</span>.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.hosts.create') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-500 hover:to-sky-400 text-white font-semibold text-sm shadow-lg shadow-sky-500/20 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Host User
            </a>
        </div>
    </div>

    <!-- 4 Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Card 1: Total Hosts -->
        <div class="glass-panel p-6 rounded-2xl shadow-lg border border-brand-border hover:border-sky-500/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Host Users</span>
                <div class="w-10 h-10 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-black text-white font-mono">{{ $totalHosts }}</div>
                <div class="text-xs text-slate-400 mt-1 flex items-center justify-between">
                    <span>Provisioned accounts</span>
                    <a href="{{ route('dashboard.hosts.index') }}" class="text-sky-400 hover:text-sky-300 font-medium">Manage &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Card 2: Active Meetings -->
        <div class="glass-panel p-6 rounded-2xl shadow-lg border border-brand-border hover:border-rose-500/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Rooms</span>
                <div class="w-10 h-10 rounded-xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-black text-rose-400 font-mono flex items-center gap-2">
                    {{ $activeMeetings }}
                    @if($activeMeetings > 0)
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                    @endif
                </div>
                <div class="text-xs text-slate-400 mt-1">Live SFU conference streams</div>
            </div>
        </div>

        <!-- Card 3: Total Meetings -->
        <div class="glass-panel p-6 rounded-2xl shadow-lg border border-brand-border hover:border-emerald-500/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Meetings</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-black text-white font-mono">{{ $totalMeetings }}</div>
                <div class="text-xs text-slate-400 mt-1">Conferences created to date</div>
            </div>
        </div>

        <!-- Card 4: Guest Users -->
        <div class="glass-panel p-6 rounded-2xl shadow-lg border border-brand-border hover:border-indigo-500/40 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Guest Participants</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-black text-white font-mono">{{ $totalGuests }}</div>
                <div class="text-xs text-slate-400 mt-1">One-time attendees & guests</div>
            </div>
        </div>

    </div>

    <!-- Data Overview Tables (Recent Hosts & Recent Meetings) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <!-- Recent Hosts Table -->
        <div class="glass-panel rounded-2xl p-6 border border-brand-border">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-base font-bold text-white">Recent Host Accounts</h3>
                    <p class="text-xs text-slate-400">Newly registered meeting organizers</p>
                </div>
                <a href="{{ route('dashboard.hosts.index') }}" class="text-xs text-sky-400 hover:text-sky-300 font-semibold">
                    View All &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider">
                            <th class="pb-3 font-semibold">Host</th>
                            <th class="pb-3 font-semibold">Username</th>
                            <th class="pb-3 font-semibold">Created</th>
                            <th class="pb-3 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($recentHosts as $h)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 font-medium text-white">
                                <div class="truncate max-w-[140px]">{{ $h->name }}</div>
                                <div class="text-[11px] text-slate-400 truncate max-w-[140px]">{{ $h->email }}</div>
                            </td>
                            <td class="py-3 font-mono text-slate-300">
                                {{ $h->username ?? '—' }}
                            </td>
                            <td class="py-3 text-slate-400">
                                {{ $h->created_at ? $h->created_at->diffForHumans() : '—' }}
                            </td>
                            <td class="py-3 text-right">
                                <a href="{{ route('dashboard.hosts.edit', $h->id) }}" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-[11px] transition">
                                    Edit
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-500">
                                No host accounts found yet. <a href="{{ route('dashboard.hosts.create') }}" class="text-sky-400 hover:underline">Create one</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Meetings Table -->
        <div class="glass-panel rounded-2xl p-6 border border-brand-border">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-base font-bold text-white">Recent Conference Sessions</h3>
                    <p class="text-xs text-slate-400">Latest LiveKit meetings created</p>
                </div>
                <span class="text-xs font-mono text-slate-500">LiveKit SFU</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider">
                            <th class="pb-3 font-semibold">Title / Room</th>
                            <th class="pb-3 font-semibold">Host</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold text-right">Started</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($recentMeetings as $m)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 font-medium text-white">
                                <div class="truncate max-w-[150px]">{{ $m->title }}</div>
                                <div class="text-[11px] text-sky-400 font-mono truncate max-w-[150px]">{{ $m->room_name }}</div>
                            </td>
                            <td class="py-3 text-slate-300">
                                {{ $m->host->name ?? 'Unknown' }}
                            </td>
                            <td class="py-3">
                                @if($m->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Active
                                </span>
                                @else
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-400">
                                    Ended
                                </span>
                                @endif
                            </td>
                            <td class="py-3 text-right text-slate-400">
                                {{ $m->created_at ? $m->created_at->diffForHumans() : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-500">
                                No meetings created yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection