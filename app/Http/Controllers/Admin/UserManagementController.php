<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // Always add 'unassigned' role for consistency
        $roles[] = 'unassigned';

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
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:super-admin,admin,teacher,student,guardian,unassigned'],
        ]);

        // Prevent changing own role
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own role'
            ], 400);
        }

        $oldRole = $user->role;
        $newRole = $validated['role'] === 'unassigned' ? null : $validated['role'];

        try {
            DB::transaction(function() use ($user, $newRole, $oldRole) {
                // Update the user's role
                $user->role = $newRole;
                $user->save();

                // If the role has changed, handle profile creation/deletion
                if ($oldRole !== $newRole) {
                    // Delete old profile if exists
                    if ($oldRole) {
                        $this->deleteOldProfile($user, $oldRole);
                    }

                    // Create new profile only if role is not unassigned
                    if ($newRole) {
                        $this->createNewProfile($user, $newRole);
                    }
                }
            });

            // Only dispatch event if everything succeeded and role is not unassigned
            if ($newRole) {
                event(new \App\Events\UserRoleAssigned($user, $newRole));
            }

            $roleDisplay = $newRole ? ucfirst($newRole) : 'Unassigned';
            
            return response()->json([
                'success' => true,
                'message' => "User role updated to {$roleDisplay}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'status' => $user->email_verified_at ? 'active' : 'inactive',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role change failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete the old profile based on the previous role.
     */
    private function deleteOldProfile(User $user, string $oldRole): void
    {
        switch ($oldRole) {
            case 'teacher':
                $user->teacherProfile()->delete();
                break;
            case 'student':
                $user->studentProfile()->delete();
                break;
            case 'guardian':
                $user->guardianProfile()->delete();
                break;
            case 'admin':
            case 'super-admin':
                $user->adminProfile()->delete();
                break;
        }
    }

    /**
     * Create new profile based on the new role.
     */
    private function createNewProfile(User $user, string $newRole): void
    {
        switch ($newRole) {
            case 'teacher':
                $user->teacherProfile()->create([
                    'user_id' => $user->id,
                    'bio' => 'New teacher profile',
                    'subjects' => [],
                    'hourly_rate' => 0,
                    'experience_years' => 0,
                ]);
                break;
            case 'student':
                $user->studentProfile()->create([
                    'user_id' => $user->id,
                    'age' => 0,
                    'grade_level' => 'Beginner',
                    'learning_goals' => 'To be defined',
                ]);
                break;
            case 'guardian':
                $user->guardianProfile()->create([
                    'user_id' => $user->id,
                    'relationship' => 'Parent',
                    'emergency_contact' => $user->phone,
                ]);
                break;
            case 'admin':
            case 'super-admin':
                $user->adminProfile()->create([
                    'user_id' => $user->id,
                    'department' => 'Administration',
                    'admin_level' => $newRole === 'super-admin' ? 'Super Admin' : 'Admin',
                    'permissions' => json_encode([
                        'users' => ['create', 'read', 'update', 'delete'],
                        'roles' => ['create', 'read', 'update', 'delete'],
                        'settings' => ['read', 'update'],
                    ]),
                    'bio' => 'System administrator',
                ]);
                break;
        }
    }

    public function destroy(User $user)
    {
        // TODO: Implement user deletion
        return redirect()->route('admin.user-management.index');
    }
}
