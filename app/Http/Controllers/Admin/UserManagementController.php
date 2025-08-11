<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by search (name or email)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('email_verified_at', '!=', null);
            } elseif ($request->status === 'inactive') {
                $query->where('email_verified_at', null);
            }
        }

        // Filter by role
        if ($request->has('role') && $request->role && $request->role !== 'all') {
            if ($request->role === 'unassigned') {
                $query->whereNull('role');
            } else {
                $query->where('role', $request->role);
            }
        }

        // Get paginated users
        $users = $query->select('id', 'name', 'email', 'avatar', 'role', 'email_verified_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'status' => $user->email_verified_at ? 'active' : 'inactive',
                ];
            });

        // Get all available roles for filter dropdown
        $roles = User::whereNotNull('role')
            ->distinct()
            ->pluck('role')
            ->toArray();

        // Add 'unassigned' role if there are users without roles
        if (User::whereNull('role')->exists()) {
            $roles[] = 'unassigned';
        }

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
                'role' => $request->role ?? 'all',
            ],
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/users/create');
    }

    public function store(Request $request)
    {
        // TODO: Implement user creation
        return redirect()->route('admin.user-management.index');
    }

    public function show(User $user)
    {
        return Inertia::render('admin/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'status' => $user->email_verified_at ? 'active' : 'inactive',
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
            ]
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'status' => $user->email_verified_at ? 'active' : 'inactive',
            ]
        ]);
    }

    public function update(Request $request, User $user)
    {
        // TODO: Implement user update
        return redirect()->route('admin.user-management.index');
    }

    public function destroy(User $user)
    {
        // TODO: Implement user deletion
        return redirect()->route('admin.user-management.index');
    }
}
