<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Retrieve all available users/hosts for direct messaging and contact list.
     */
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user('sanctum');
        if ($currentUser && $currentUser->isGuest()) {
            return $this->errorResponse('Guests do not have access to user directory', 403);
        }
        $search = $request->query('search');

        $query = User::query()
            ->select(['id', 'name', 'username', 'email', 'avatar_url', 'role', 'is_guest']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_guest')) {
            $query->where('is_guest', $request->boolean('is_guest'));
        }

        if ($request->boolean('exclude_self') && $currentUser) {
            $query->where('id', '!=', $currentUser->id);
        }

        $users = $query->orderBy('is_guest', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function (User $user): array {
                $generatedUsername = $user->username;
                if (empty($generatedUsername) && ! empty($user->email)) {
                    $generatedUsername = explode('@', $user->email)[0];
                }
                if (empty($generatedUsername) && ! empty($user->name)) {
                    $slug = Str::slug($user->name, '');
                    $generatedUsername = ! empty($slug) ? $slug : 'user_'.$user->id;
                }
                if (empty($generatedUsername)) {
                    $generatedUsername = 'user_'.$user->id;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $generatedUsername,
                    'email' => $user->email ?? '',
                    'avatar_url' => $user->avatar_url,
                    'role' => $user->role ?? ($user->is_guest ? 'guest' : 'host'),
                    'is_guest' => (bool) $user->is_guest,
                ];
            });

        return $this->successResponse($users, 'Users retrieved successfully');
    }

    /**
     * Update current authenticated user's profile (name, avatar_url).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $currentUser = $request->user('sanctum');
        if (! $currentUser) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'avatar_url' => 'nullable|string',
        ]);

        if (isset($validated['name']) && ! empty(trim($validated['name']))) {
            $currentUser->name = trim($validated['name']);
        }

        if (array_key_exists('avatar_url', $validated)) {
            $currentUser->avatar_url = $validated['avatar_url'];
        }

        $currentUser->save();

        return $this->successResponse([
            'id' => $currentUser->id,
            'name' => $currentUser->name,
            'username' => $currentUser->username,
            'email' => $currentUser->email,
            'avatar_url' => $currentUser->avatar_url,
        ], 'Profile updated successfully');
    }
}
