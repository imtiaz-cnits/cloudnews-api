<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingHostSession;
use App\Models\MeetingMessage;
use App\Models\MeetingParticipant;
use App\Services\LiveKitService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeetingAdminController extends Controller
{
    public function __construct(
        protected LiveKitService $liveKitService
    ) {}

    /**
     * Display a paginated listing of meeting rooms with search and filters.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));

        $query = Meeting::with('host')
            ->withCount('participants');

        // Apply Search
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('meeting_code', 'like', "%{$search}%")
                    ->orWhere('room_name', 'like', "%{$search}%")
                    ->orWhereHas('host', function ($hQuery) use ($search) {
                        $hQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        // Apply Status Filter
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'scheduled') {
            $query->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>', now())
                ->where('is_active', false)
                ->whereNull('ended_at');
        } elseif ($status === 'ended') {
            $query->where(function ($q) {
                $q->where('is_active', false)
                    ->whereNotNull('ended_at');
            });
        }

        $meetings = $query->latest()
            ->paginate(10)
            ->withQueryString();

        // Summary counts for dashboard metrics
        $totalMeetings = Meeting::count();
        $activeRoomsCount = Meeting::where('is_active', true)->count();
        $scheduledMeetingsCount = Meeting::whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->where('is_active', false)
            ->whereNull('ended_at')
            ->count();
        $endedMeetingsCount = Meeting::where('is_active', false)
            ->whereNotNull('ended_at')
            ->count();

        return view('dashboard.meetings.index', compact(
            'meetings',
            'search',
            'status',
            'totalMeetings',
            'activeRoomsCount',
            'scheduledMeetingsCount',
            'endedMeetingsCount'
        ));
    }

    /**
     * Terminate an active meeting room.
     */
    public function endMeeting(Meeting $meeting): RedirectResponse
    {
        $meeting->update([
            'is_active' => false,
            'is_host_online' => false,
            'ended_at' => now(),
        ]);

        // Mark active participants departed
        MeetingParticipant::where('meeting_id', $meeting->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);

        // Release host sessions
        MeetingHostSession::where('meeting_id', $meeting->id)->delete();

        // Terminate room on LiveKit SFU
        $this->liveKitService->deleteRoom($meeting->room_name);

        return redirect()->route('dashboard.meetings.index')
            ->with('success', "Meeting room '{$meeting->meeting_code}' has been ended successfully.");
    }

    /**
     * Permanently delete a meeting room and associated records.
     */
    public function destroy(Meeting $meeting): RedirectResponse
    {
        DB::transaction(function () use ($meeting) {
            MeetingParticipant::where('meeting_id', $meeting->id)->delete();
            MeetingMessage::where('meeting_id', $meeting->id)->delete();
            MeetingHostSession::where('meeting_id', $meeting->id)->delete();
            $meeting->delete();
        });

        // Terminate room on LiveKit SFU
        $this->liveKitService->deleteRoom($meeting->room_name);

        return redirect()->route('dashboard.meetings.index')
            ->with('success', "Meeting room '{$meeting->meeting_code}' was permanently deleted.");
    }

    /**
     * Handle bulk actions (bulk end, bulk delete) on selected meetings.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:end,delete'],
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer', 'exists:meetings,id'],
        ]);

        $ids = $validated['selected_ids'];
        $action = $validated['action'];

        if ($action === 'end') {
            $activeMeetings = Meeting::whereIn('id', $ids)
                ->where('is_active', true)
                ->get();

            $count = $activeMeetings->count();

            foreach ($activeMeetings as $meeting) {
                $meeting->update([
                    'is_active' => false,
                    'is_host_online' => false,
                    'ended_at' => now(),
                ]);

                MeetingParticipant::where('meeting_id', $meeting->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => now()]);

                MeetingHostSession::where('meeting_id', $meeting->id)->delete();

                $this->liveKitService->deleteRoom($meeting->room_name);
            }

            return redirect()->route('dashboard.meetings.index')
                ->with('success', "Successfully ended {$count} active meeting room(s).");
        }

        if ($action === 'delete') {
            $meetings = Meeting::whereIn('id', $ids)->get();
            $count = $meetings->count();

            DB::transaction(function () use ($ids) {
                MeetingParticipant::whereIn('meeting_id', $ids)->delete();
                MeetingMessage::whereIn('meeting_id', $ids)->delete();
                MeetingHostSession::whereIn('meeting_id', $ids)->delete();
                Meeting::whereIn('id', $ids)->delete();
            });

            foreach ($meetings as $meeting) {
                $this->liveKitService->deleteRoom($meeting->room_name);
            }

            return redirect()->route('dashboard.meetings.index')
                ->with('success', "Successfully deleted {$count} meeting room(s) and their records.");
        }

        return redirect()->route('dashboard.meetings.index');
    }
}
