<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Admin Dashboard overview.
     */
    public function index(): View
    {
        $totalHosts = User::hosts()->count();
        $totalGuests = User::guests()->count();
        $activeMeetings = Meeting::where('is_active', true)->count();
        $totalMeetings = Meeting::count();

        $recentHosts = User::hosts()
            ->latest()
            ->take(5)
            ->get();

        $recentMeetings = Meeting::with('host')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'totalHosts',
            'totalGuests',
            'activeMeetings',
            'totalMeetings',
            'recentHosts',
            'recentMeetings'
        ));
    }
}
