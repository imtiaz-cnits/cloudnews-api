@extends('layouts.admin')

@section('title', 'Meetings & Rooms')
@section('page_heading', 'Meeting & Room Management')

@section('content')
<div class="space-y-6">

    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Active Rooms Card -->
        <a href="{{ route('dashboard.meetings.index', ['status' => 'active']) }}"
            class="glass-panel p-4 rounded-2xl border border-brand-border hover:border-emerald-500/40 transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 group-hover:text-slate-300">Active Live Rooms</span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
            <div class="mt-2 text-2xl font-bold text-white tracking-tight flex items-baseline gap-2">
                {{ $activeRoomsCount }}
                <span class="text-xs text-emerald-400 font-medium font-mono">LiveKit SFU</span>
            </div>
        </a>

        <!-- Total Meetings -->
        <a href="{{ route('dashboard.meetings.index', ['status' => 'all']) }}"
            class="glass-panel p-4 rounded-2xl border border-brand-border hover:border-sky-500/40 transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 group-hover:text-slate-300">Total Rooms Hosted</span>
                <div class="w-6 h-6 rounded-lg bg-sky-500/10 text-sky-400 flex items-center justify-center text-xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-white tracking-tight">
                {{ $totalMeetings }}
            </div>
        </a>

        <!-- Scheduled Meetings -->
        <a href="{{ route('dashboard.meetings.index', ['status' => 'scheduled']) }}"
            class="glass-panel p-4 rounded-2xl border border-brand-border hover:border-indigo-500/40 transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 group-hover:text-slate-300">Upcoming Scheduled</span>
                <div class="w-6 h-6 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-white tracking-tight">
                {{ $scheduledMeetingsCount }}
            </div>
        </a>

        <!-- Ended Meetings -->
        <a href="{{ route('dashboard.meetings.index', ['status' => 'ended']) }}"
            class="glass-panel p-4 rounded-2xl border border-brand-border hover:border-slate-600 transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 group-hover:text-slate-300">Completed Rooms</span>
                <div class="w-6 h-6 rounded-lg bg-slate-800 text-slate-400 flex items-center justify-center text-xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <div class="mt-2 text-2xl font-bold text-white tracking-tight">
                {{ $endedMeetingsCount }}
            </div>
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 p-1 rounded-xl bg-[#0B1728] border border-slate-800">
            <a href="{{ route('dashboard.meetings.index', ['status' => 'all', 'search' => $search]) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'all' ? 'bg-sky-500/20 text-sky-300 border border-sky-500/30' : 'text-slate-400 hover:text-white' }}">
                All ({{ $totalMeetings }})
            </a>
            <a href="{{ route('dashboard.meetings.index', ['status' => 'active', 'search' => $search]) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'text-slate-400 hover:text-white' }}">
                Live Active ({{ $activeRoomsCount }})
            </a>
            <a href="{{ route('dashboard.meetings.index', ['status' => 'scheduled', 'search' => $search]) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'scheduled' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-white' }}">
                Scheduled ({{ $scheduledMeetingsCount }})
            </a>
            <a href="{{ route('dashboard.meetings.index', ['status' => 'ended', 'search' => $search]) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'ended' ? 'bg-slate-700/50 text-slate-300 border border-slate-600' : 'text-slate-400 hover:text-white' }}">
                Ended ({{ $endedMeetingsCount }})
            </a>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="{{ route('dashboard.meetings.index') }}" class="flex items-center gap-2 max-w-md w-full">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by title, code, room name, host..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#0B1728] border border-slate-700/80 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-sky-500 transition">
            </div>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition">
                Search
            </button>
            @if(!empty($search))
            <a href="{{ route('dashboard.meetings.index', ['status' => $status]) }}" class="px-3 py-2.5 rounded-xl bg-slate-800/50 hover:bg-slate-800 text-slate-400 text-xs transition">
                Clear
            </a>
            @endif
        </form>
    </div>

    <!-- Floating / Sticky Bulk Action Toolbar -->
    <div id="bulkMeetingBar" class="hidden p-3.5 rounded-2xl bg-sky-950/80 border border-sky-500/40 backdrop-blur-md flex flex-wrap items-center justify-between gap-3 shadow-xl shadow-sky-950/50 transition-all">
        <div class="flex items-center gap-3">
            <span id="selectedMeetingCount" class="px-2.5 py-1 rounded-full bg-sky-500/20 text-sky-300 font-bold text-xs border border-sky-500/30">
                0 rooms selected
            </span>
            <span class="text-xs text-slate-300 font-medium">Bulk Room Management Actions</span>
        </div>

        <div class="flex items-center gap-2">
            <!-- Bulk End Rooms Action -->
            <button type="button" onclick="submitBulkMeetingAction('end')"
                class="px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/20 text-xs font-semibold transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                End Selected Rooms
            </button>

            <!-- Bulk Delete Action -->
            <button type="button" onclick="submitBulkMeetingAction('delete')"
                class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-semibold transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete Selected
            </button>

            <!-- Deselect All -->
            <button type="button" onclick="clearMeetingSelection()"
                class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-medium transition">
                Deselect All
            </button>
        </div>
    </div>

    <!-- Hidden Bulk Action Submission Form -->
    <form id="bulkMeetingForm" method="POST" action="{{ route('dashboard.meetings.bulk-action') }}" class="hidden">
        @csrf
        <input type="hidden" name="action" id="bulkMeetingActionInput">
        <div id="bulkMeetingIdsContainer"></div>
    </form>

    <!-- Meetings Table -->
    <div class="glass-panel rounded-2xl border border-brand-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                        <th class="py-4 px-4 w-10 text-center">
                            <input type="checkbox" id="selectAllMeetings"
                                class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-sky-500 focus:ring-sky-500 focus:ring-offset-slate-900 cursor-pointer">
                        </th>
                        <th class="py-4 px-4 font-semibold">Meeting Room</th>
                        <th class="py-4 px-6 font-semibold">Host Profile</th>
                        <th class="py-4 px-6 font-semibold">Status</th>
                        <th class="py-4 px-6 font-semibold">Participants</th>
                        <th class="py-4 px-6 font-semibold">Timeline</th>
                        <th class="py-4 px-6 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($meetings as $meeting)
                    <tr class="hover:bg-slate-800/30 transition">
                        <!-- Checkbox -->
                        <td class="py-4 px-4 text-center">
                            <input type="checkbox" value="{{ $meeting->id }}"
                                class="meeting-checkbox w-4 h-4 rounded bg-slate-900 border-slate-700 text-sky-500 focus:ring-sky-500 focus:ring-offset-slate-900 cursor-pointer">
                        </td>

                        <!-- Meeting Title & Code -->
                        <td class="py-4 px-4">
                            <div>
                                <div class="font-semibold text-white text-sm flex items-center gap-2">
                                    {{ $meeting->title ?: 'Instant Meeting' }}
                                    @if($meeting->hasPasscode())
                                    <span class="px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 text-[10px] font-mono border border-amber-500/20" title="Password Protected">
                                        Protected
                                    </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="font-mono text-sky-400 text-xs font-bold">
                                        {{ $meeting->meeting_code }}
                                    </span>
                                    <span class="text-slate-600">&bull;</span>
                                    <span class="font-mono text-slate-500 text-[11px] truncate max-w-[150px]" title="{{ $meeting->room_name }}">
                                        {{ $meeting->room_name }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Host Profile -->
                        <td class="py-4 px-6">
                            @if($meeting->host)
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-sky-600 to-indigo-500 flex items-center justify-center font-bold text-white text-xs shadow">
                                    {{ strtoupper(substr($meeting->host->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-white text-xs">{{ $meeting->host->name }}</div>
                                    <div class="text-slate-400 text-[11px]">{{ $meeting->host->email }}</div>
                                </div>
                            </div>
                            @else
                            <span class="text-slate-500 italic">Host Deleted</span>
                            @endif
                        </td>

                        <!-- Status -->
                        <td class="py-4 px-6">
                            @if($meeting->is_active && $meeting->is_host_online)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20 text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Live Room
                            </span>
                            @elseif($meeting->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-300 font-semibold border border-amber-500/20 text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                Host Offline
                            </span>
                            @elseif($meeting->scheduled_at && $meeting->scheduled_at > now())
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-300 font-semibold border border-indigo-500/20 text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                Scheduled
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-800/80 text-slate-400 border border-slate-700/60 text-[11px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                Ended
                            </span>
                            @endif
                        </td>

                        <!-- Participants -->
                        <td class="py-4 px-6 text-slate-300">
                            <span class="px-2.5 py-1 rounded-full bg-slate-800 text-slate-300 font-medium border border-slate-700/60 text-[11px]">
                                {{ $meeting->participants_count }} {{ Str::plural('user', $meeting->participants_count) }}
                            </span>
                        </td>

                        <!-- Timeline -->
                        <td class="py-4 px-6 text-slate-400 text-[11px]">
                            @if($meeting->is_active)
                            <span class="text-emerald-400 font-medium">Started {{ $meeting->started_at ? $meeting->started_at->diffForHumans() : 'recently' }}</span>
                            @elseif($meeting->ended_at)
                            <span>Ended {{ $meeting->ended_at->diffForHumans() }}</span>
                            @elseif($meeting->scheduled_at)
                            <span class="text-indigo-400">For {{ $meeting->scheduled_at->format('M d, Y H:i') }}</span>
                            @else
                            <span>Created {{ $meeting->created_at ? $meeting->created_at->diffForHumans() : '—' }}</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- End Meeting (if active) -->
                                @if($meeting->is_active)
                                <form method="POST" action="{{ route('dashboard.meetings.end', $meeting->id) }}"
                                    onsubmit="return confirm('End meeting room \'{{ $meeting->meeting_code }}\'? All active participants will be disconnected.');"
                                    class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="px-2.5 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/20 text-[11px] font-medium transition"
                                        title="End Active Meeting Room">
                                        End Room
                                    </button>
                                </form>
                                @endif

                                <!-- Delete Meeting Action -->
                                <form method="POST" action="{{ route('dashboard.meetings.destroy', $meeting->id) }}"
                                    onsubmit="return confirm('Permanently delete meeting \'{{ $meeting->meeting_code }}\'? All chat history and participant records will be purged.');"
                                    class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-2.5 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-medium transition"
                                        title="Delete Meeting Room">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500">
                            <div class="w-12 h-12 rounded-2xl bg-slate-800/80 flex items-center justify-center mx-auto text-slate-400 mb-3">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-slate-400 font-medium">No meeting rooms found</p>
                            <p class="text-xs text-slate-500 mt-1">Try refining your search query or status filter.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($meetings->hasPages())
        <div class="p-4 border-t border-slate-800 bg-slate-900/40">
            {{ $meetings->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Multi-Selection Script for Meetings -->
<script>
    const selectAllMeetings = document.getElementById('selectAllMeetings');
    const meetingCheckboxes = document.querySelectorAll('.meeting-checkbox');
    const bulkMeetingBar = document.getElementById('bulkMeetingBar');
    const selectedMeetingCount = document.getElementById('selectedMeetingCount');
    const bulkMeetingForm = document.getElementById('bulkMeetingForm');
    const bulkMeetingActionInput = document.getElementById('bulkMeetingActionInput');
    const bulkMeetingIdsContainer = document.getElementById('bulkMeetingIdsContainer');

    function updateMeetingSelectionState() {
        const selected = Array.from(meetingCheckboxes).filter(cb => cb.checked);
        const count = selected.length;

        if (count > 0) {
            bulkMeetingBar.classList.remove('hidden');
            selectedMeetingCount.textContent = count + ' room' + (count > 1 ? 's' : '') + ' selected';
        } else {
            bulkMeetingBar.classList.add('hidden');
        }

        if (selectAllMeetings) {
            selectAllMeetings.checked = (count > 0 && count === meetingCheckboxes.length);
            selectAllMeetings.indeterminate = (count > 0 && count < meetingCheckboxes.length);
        }
    }

    if (selectAllMeetings) {
        selectAllMeetings.addEventListener('change', function() {
            meetingCheckboxes.forEach(cb => {
                cb.checked = selectAllMeetings.checked;
            });
            updateMeetingSelectionState();
        });
    }

    meetingCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateMeetingSelectionState);
    });

    function clearMeetingSelection() {
        if (selectAllMeetings) selectAllMeetings.checked = false;
        meetingCheckboxes.forEach(cb => cb.checked = false);
        updateMeetingSelectionState();
    }

    function submitBulkMeetingAction(action) {
        const selected = Array.from(meetingCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
        if (selected.length === 0) return;

        let confirmMsg = '';
        if (action === 'end') {
            confirmMsg = 'Are you sure you want to end ' + selected.length + ' selected meeting room(s)? Active participants will be disconnected.';
        } else if (action === 'delete') {
            confirmMsg = 'Are you sure you want to permanently delete ' + selected.length + ' selected meeting room(s)? All participant and chat data will be purged. This action cannot be undone.';
        }

        if (!confirm(confirmMsg)) return;

        bulkMeetingActionInput.value = action;
        bulkMeetingIdsContainer.innerHTML = '';
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_ids[]';
            input.value = id;
            bulkMeetingIdsContainer.appendChild(input);
        });

        bulkMeetingForm.submit();
    }
</script>
@endsection
