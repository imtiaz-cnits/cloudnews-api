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
        $totalHosts = User::where('role', 'host')->count();
        $totalGuests = User::where('is_guest', true)->orWhere('role', 'guest')->count();
        $activeMeetings = Meeting::where('is_active', true)->count();
        $totalMeetings = Meeting::count();

        $recentHosts = User::where('role', 'host')
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
