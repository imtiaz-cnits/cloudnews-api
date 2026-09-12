@extends('layouts.admin')

@section('title', 'Host Users')
@section('page_heading', 'Host User Management')

@section('content')
<div class="space-y-6">
    
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <!-- Search Bar -->
        <form method="GET" action="{{ route('dashboard.hosts.index') }}" class="flex items-center gap-2 max-w-md w-full">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name, email, or username..." 
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#0B1728] border border-slate-700/80 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-sky-500 transition">
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition">
                Search
            </button>
            @if(!empty($search))
                <a href="{{ route('dashboard.hosts.index') }}" class="px-3 py-2.5 rounded-xl bg-slate-800/50 hover:bg-slate-800 text-slate-400 text-xs transition">
                    Clear
                </a>
            @endif
        </form>

        <!-- New Host Button -->
        <a href="{{ route('dashboard.hosts.create') }}" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-sky-600 to-sky-500 hover:from-sky-500 hover:to-sky-400 text-white font-semibold text-xs shadow-lg shadow-sky-500/20 transition flex items-center justify-center gap-2 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New Host
        </a>
    </div>

    <!-- Hosts Table -->
    <div class="glass-panel rounded-2xl border border-brand-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                        <th class="py-4 px-6 font-semibold">Host Profile</th>
                        <th class="py-4 px-6 font-semibold">Username</th>
                        <th class="py-4 px-6 font-semibold">Meetings Hosted</th>
                        <th class="py-4 px-6 font-semibold">Provisioned</th>
                        <th class="py-4 px-6 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($hosts as $host)
                        <tr class="hover:bg-slate-800/30 transition">
                            <!-- Host Name & Email -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-sky-600 to-cyan-500 flex items-center justify-center font-bold text-white text-xs shadow">
                                        {{ strtoupper(substr($host->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-white text-sm">{{ $host->name }}</div>
                                        <div class="text-slate-400 text-[11px]">{{ $host->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Username -->
                            <td class="py-4 px-6 font-mono text-slate-300">
                                @if($host->username)
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-sky-400 border border-slate-700/60">
                                        &#64;{{ $host->username }}
                                    </span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>

                            <!-- Meetings Hosted -->
                            <td class="py-4 px-6 text-slate-300">
                                <span class="px-2.5 py-1 rounded-full bg-sky-500/10 text-sky-400 font-bold border border-sky-500/20 text-[11px]">
                                    {{ $host->hosted_meetings_count }} {{ Str::plural('room', $host->hosted_meetings_count) }}
                                </span>
                            </td>

                            <!-- Provisioned Date -->
                            <td class="py-4 px-6 text-slate-400">
                                {{ $host->created_at ? $host->created_at->format('M d, Y') : '—' }}
                                <div class="text-[10px] text-slate-500">
                                    {{ $host->created_at ? $host->created_at->diffForHumans() : '' }}
                                </div>
                            </td>

                            <!-- Action Buttons -->
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Reset Password Action -->
                                    <button type="button" 
                                        onclick="openResetModal('{{ $host->id }}', '{{ addslashes($host->name) }}', '{{ addslashes($host->email) }}')"
                                        class="px-2.5 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/20 text-[11px] font-medium transition"
                                        title="Reset Password">
                                        Reset Pass
                                    </button>

                                    <!-- Edit Action -->
                                    <a href="{{ route('dashboard.hosts.edit', $host->id) }}" 
                                        class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-[11px] font-medium transition"
                                        title="Edit Details">
                                        Edit
                                    </a>

                                    <!-- Delete Action -->
                                    <form method="POST" action="{{ route('dashboard.hosts.destroy', $host->id) }}" 
                                        onsubmit="return confirm('Are you sure you want to permanently delete host \'{{ addslashes($host->name) }}\'? All associated meetings will be safely cleaned up.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                            class="px-2.5 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-medium transition"
                                            title="Delete Host">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-2xl bg-slate-800/80 flex items-center justify-center mx-auto text-slate-400 mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <p class="text-sm text-slate-400 font-medium">No host accounts found</p>
                                <p class="text-xs text-slate-500 mt-1">Try refining your search query or register a new host.</p>
                                <a href="{{ route('dashboard.hosts.create') }}" class="mt-4 inline-block px-4 py-2 rounded-xl bg-sky-600 text-white font-semibold text-xs">
                                    Create New Host
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($hosts->hasPages())
            <div class="p-4 border-t border-slate-800/80 bg-slate-900/40">
                {{ $hosts->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-[#0B1728] border border-brand-border rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-white">Reset Host Password</h3>
            <button type="button" onclick="closeResetModal()" class="text-slate-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <p class="text-xs text-slate-400">
            Reset credentials for <span id="modalHostName" class="text-white font-semibold"></span> (<span id="modalHostEmail" class="text-sky-400"></span>).
        </p>

        <form id="resetPasswordForm" method="POST" action="">
            @csrf
            
            <div class="space-y-3">
                <div>
                    <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                        New Password (Optional)
                    </label>
                    <input type="text" id="new_password" name="new_password" 
                        placeholder="Leave blank to auto-generate secure password"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-[#060D17] border border-slate-700 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-sky-500 transition">
                    <p class="text-[11px] text-slate-500 mt-1">If left blank, an auto-generated 12-character password will be returned in the flash alert.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <button type="button" onclick="closeResetModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition shadow-lg shadow-amber-500/20">
                    Confirm Reset
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openResetModal(hostId, hostName, hostEmail) {
        document.getElementById('modalHostName').innerText = hostName;
        document.getElementById('modalHostEmail').innerText = hostEmail;
        document.getElementById('resetPasswordForm').action = '/dashboard/hosts/' + hostId + '/reset-password';
        document.getElementById('new_password').value = '';
        
        const modal = document.getElementById('resetPasswordModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeResetModal() {
        const modal = document.getElementById('resetPasswordModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
