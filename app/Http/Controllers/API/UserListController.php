<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserListController extends Controller
{
    /**
     * Get a list of users for notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // For security, only return the current user if not admin
        if (!in_array($request->user()->role, ['admin', 'super-admin'])) {
            return response()->json([
                [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name
                ]
            ]);
        }
        
        // If admin, return all users
        $users = User::select('id', 'name', 'role', 'email', 'phone')
            ->orderBy('name')
            ->get();
            
        return response()->json($users);
    }
} 