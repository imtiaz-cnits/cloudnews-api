<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /**
     * Display the public landing page for Cloud News Meet.
     */
    public function index(): View
    {
        $activeMeetingsCount = Meeting::where('is_active', true)->count();
        $totalHostsCount = User::where('role', 'host')->count();

        return view('landing', [
            'activeMeetingsCount' => $activeMeetingsCount,
            'totalHostsCount' => $totalHostsCount,
        ]);
    }
}
