<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminProfile;
use App\Models\GuardianProfile;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of users with their roles.
     */
    public function index(): Response
    {
        $users = User::select('id', 'name', 'email', 'phone', 'role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('admin/users/index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for editing a user's role.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit-role', [
            'user' => $user,
            'currentRole' => $user->role,
            'profile' => $user->profile(),
        ]);
    }

    /**
     * Update the user's role.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:super-admin,teacher,student,guardian'],
            'profile_data' => ['nullable', 'array'],
        ]);

        $oldRole = $user->role;
        $newRole = $validated['role'];
        $profileData = $validated['profile_data'] ?? [];

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Update the user's role
            $user->role = $newRole;
            $user->save();

            // If the role has changed, handle profile creation/deletion
            if ($oldRole !== $newRole) {
                // Delete old profile if exists
                if ($oldRole) {
                    $this->deleteOldProfile($user, $oldRole);
                }

                // Create new profile
                $this->createNewProfile($user, $newRole, $profileData);
            } else {
                // Update existing profile
                $this->updateExistingProfile($user, $newRole, $profileData);
            }

            DB::commit();

            return redirect()->route('admin.users.index')
                ->with('success', "User role updated to {$newRole}");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update user role: ' . $e->getMessage());
        }
    }

    /**
     * Delete the old profile based on the previous role.
     */
    private function deleteOldProfile(User $user, string $oldRole): void
    {
        switch ($oldRole) {
            case 'super-admin':
                $user->adminProfile()->delete();
                break;
            case 'teacher':
                $user->teacherProfile()->delete();
                break;
            case 'student':
                $user->studentProfile()->delete();
                break;
            case 'guardian':
                $user->guardianProfile()->delete();
                break;
        }
    }

    /**
     * Create a new profile based on the new role.
     */
    private function createNewProfile(User $user, string $newRole, array $profileData): void
    {
        switch ($newRole) {
            case 'super-admin':
                $user->adminProfile()->create($profileData);
                break;
            case 'teacher':
                $user->teacherProfile()->create($profileData);
                break;
            case 'student':
                $user->studentProfile()->create($profileData);
                break;
            case 'guardian':
                $user->guardianProfile()->create($profileData);
                break;
        }
    }

    /**
     * Update an existing profile.
     */
    private function updateExistingProfile(User $user, string $role, array $profileData): void
    {
        switch ($role) {
            case 'super-admin':
                if ($user->adminProfile) {
                    $user->adminProfile->update($profileData);
                } else {
                    $user->adminProfile()->create($profileData);
                }
                break;
            case 'teacher':
                if ($user->teacherProfile) {
                    $user->teacherProfile->update($profileData);
                } else {
                    $user->teacherProfile()->create($profileData);
                }
                break;
            case 'student':
                if ($user->studentProfile) {
                    $user->studentProfile->update($profileData);
                } else {
                    $user->studentProfile()->create($profileData);
                }
                break;
            case 'guardian':
                if ($user->guardianProfile) {
                    $user->guardianProfile->update($profileData);
                } else {
                    $user->guardianProfile()->create($profileData);
                }
                break;
        }
    }
}
