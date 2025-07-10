<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class UserStatusController extends Controller
{
    /**
     * Update the user's status.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'status_type' => ['required', 'string', 'in:online,away,busy,offline'],
            'status_message' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->status_type = $validated['status_type'];
        $user->status_message = $validated['status_message'] ?? null;
        $user->save();

        return back();
    }
}
