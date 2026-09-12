<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class HostController extends Controller
{
    /**
     * Display a paginated listing of host users.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $hosts = User::where('role', 'host')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->withCount('hostedMeetings')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.hosts.index', compact('hosts', 'search'));
    }

    /**
     * Show the form for creating a new host.
     */
    public function create(): View
    {
        return view('dashboard.hosts.create');
    }

    /**
     * Store a newly created host in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $plainPassword = ! empty($validated['password'])
            ? $validated['password']
            : Str::random(12);

        $host = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($plainPassword),
            'role' => 'host',
            'is_guest' => false,
        ]);

        $msg = "Host account '{$host->name}' created successfully.";
        if (empty($validated['password'])) {
            $msg .= " Generated Password: {$plainPassword}";
        }

        return redirect()->route('dashboard.hosts.index')->with('success', $msg);
    }

    /**
     * Show the form for editing the specified host.
     */
    public function edit(User $host): View|RedirectResponse
    {
        if ($host->role === 'admin') {
            return redirect()->route('dashboard.hosts.index')
                ->with('error', 'Administrator accounts cannot be modified via Host Management.');
        }

        return view('dashboard.hosts.edit', compact('host'));
    }

    /**
     * Update the specified host in storage.
     */
    public function update(Request $request, User $host): RedirectResponse
    {
        if ($host->role === 'admin') {
            return redirect()->route('dashboard.hosts.index')
                ->with('error', 'Administrator accounts cannot be modified via Host Management.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($host->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($host->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $host->update($updateData);

        return redirect()->route('dashboard.hosts.index')
            ->with('success', "Host '{$host->name}' updated successfully.");
    }

    /**
     * Remove the specified host safely from storage.
     */
    public function destroy(User $host): RedirectResponse
    {
        if ($host->id === Auth::id() || $host->role === 'admin') {
            return redirect()->route('dashboard.hosts.index')
                ->with('error', 'Administrator accounts cannot be deleted.');
        }

        DB::transaction(function () use ($host) {
            // Revoke all personal access tokens
            $host->tokens()->delete();

            // Safely delete meeting participant records associated with this user
            DB::table('meeting_participants')->where('user_id', $host->id)->delete();

            // Find all meetings hosted by this user and remove their participant records
            $meetingIds = $host->hostedMeetings()->pluck('id');
            if ($meetingIds->isNotEmpty()) {
                DB::table('meeting_participants')->whereIn('meeting_id', $meetingIds)->delete();
                $host->hostedMeetings()->delete();
            }

            // Finally remove the host record
            $host->delete();
        });

        return redirect()->route('dashboard.hosts.index')
            ->with('success', "Host '{$host->name}' and all associated meeting records were safely removed.");
    }

    /**
     * Reset the host's password.
     */
    public function resetPassword(Request $request, User $host): RedirectResponse
    {
        if ($host->role === 'admin') {
            return redirect()->route('dashboard.hosts.index')
                ->with('error', 'Administrator accounts cannot be reset via this action.');
        }

        $request->validate([
            'new_password' => ['nullable', 'string', 'min:8'],
        ]);

        $newPassword = $request->filled('new_password')
            ? $request->input('new_password')
            : Str::random(12);

        $host->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoke active API tokens to force re-authentication
        $host->tokens()->delete();

        return redirect()->route('dashboard.hosts.index')
            ->with('success', "Password for {$host->name} ({$host->email}) has been reset to: {$newPassword}");
    }
}
