<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AdminRolesController extends Controller
{
    /**
     * Display a listing of admin roles.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $roles = AdminRole::all();
        
        return Inertia::render('Admin/Settings/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new admin role.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $availablePermissions = $this->getAvailablePermissions();
        
        return Inertia::render('Admin/Settings/Roles/Create', [
            'availablePermissions' => $availablePermissions,
        ]);
    }

    /**
     * Store a newly created admin role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:admin_roles,name',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        AdminRole::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => json_encode($validated['permissions']),
        ]);

        // Clear the roles cache
        $this->clearRolesCache();

        return redirect()->route('admin.settings.roles.index')
            ->with('success', 'Admin role created successfully.');
    }

    /**
     * Show the form for editing the specified admin role.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $role = AdminRole::findOrFail($id);
        $availablePermissions = $this->getAvailablePermissions();
        
        return Inertia::render('Admin/Settings/Roles/Edit', [
            'role' => $role,
            'rolePermissions' => json_decode($role->permissions, true),
            'availablePermissions' => $availablePermissions,
        ]);
    }

    /**
     * Update the specified admin role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $role = AdminRole::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:admin_roles,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => json_encode($validated['permissions']),
        ]);

        // Clear the roles cache
        $this->clearRolesCache();

        return redirect()->route('admin.settings.roles.index')
            ->with('success', 'Admin role updated successfully.');
    }

    /**
     * Remove the specified admin role from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $role = AdminRole::findOrFail($id);
        
        // Check if any users are assigned to this role
        $usersWithRole = User::where('admin_role_id', $id)->count();
        
        if ($usersWithRole > 0) {
            return redirect()->route('admin.settings.roles.index')
                ->with('error', 'Cannot delete role. There are ' . $usersWithRole . ' users assigned to this role.');
        }
        
        $role->delete();
        
        // Clear the roles cache
        $this->clearRolesCache();

        return redirect()->route('admin.settings.roles.index')
            ->with('success', 'Admin role deleted successfully.');
    }

    /**
     * Assign a role to a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignRole(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:admin_roles,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->admin_role_id = $validated['role_id'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully.'
        ]);
    }

    /**
     * Get all available permissions.
     *
     * @return array
     */
    protected function getAvailablePermissions()
    {
        return [
            'settings' => [
                'view_general_settings',
                'edit_general_settings',
                'view_financial_settings',
                'edit_financial_settings',
                'view_security_settings',
                'edit_security_settings',
                'view_feature_flags',
                'edit_feature_flags',
                'manage_roles',
            ],
            'users' => [
                'view_users',
                'create_users',
                'edit_users',
                'delete_users',
                'approve_teachers',
                'verify_documents',
            ],
            'content' => [
                'manage_content_pages',
                'manage_faqs',
                'manage_blog',
                'manage_resources',
            ],
            'financial' => [
                'view_transactions',
                'process_refunds',
                'manage_payouts',
                'view_financial_reports',
            ],
        ];
    }

    /**
     * Clear the roles cache.
     *
     * @return void
     */
    protected function clearRolesCache()
    {
        Cache::forget('admin_roles');
    }
}
