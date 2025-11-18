<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'message' => $request->session()->get('message'),
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                    'avatar' => $request->user()->avatar,
                    'location' => $request->user()->location,
                    'role' => $request->user()->role,
                    'status_type' => $request->user()->status_type,
                    'status_message' => $request->user()->status_message,
                    'last_active_at' => $request->user()->last_active_at,
                    'wallet_balance' => $this->getUserWalletBalance($request->user()),
                    'wallet' => $this->getUserWallet($request->user()),
                ] : null,
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * Get the wallet balance for the authenticated user based on their role.
     *
     * @param \App\Models\User $user
     * @return float|null
     */
    private function getUserWalletBalance($user): ?float
    {
        if (!$user) {
            return null;
        }

        switch ($user->role) {
            case 'student':
                $wallet = $user->getOrCreateWallet();
                return (float) $wallet->balance;
            
            case 'teacher':
                $wallet = $user->teacherWallet;
                return $wallet ? (float) $wallet->balance : 0.0;
            
            case 'guardian':
                $wallet = $user->guardianWallet;
                return $wallet ? (float) $wallet->balance : 0.0;
            
            default:
                return null;
        }
    }

    /**
     * Get the wallet object for the authenticated user based on their role.
     *
     * @param \App\Models\User $user
     * @return array|null
     */
    private function getUserWallet($user): ?array
    {
        if (!$user) {
            return null;
        }

        switch ($user->role) {
            case 'student':
                $wallet = $user->getOrCreateWallet();
                return [
                    'id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'payment_id' => $wallet->payment_id,
                    'balance' => (float) $wallet->balance,
                    'total_spent' => (float) $wallet->total_spent,
                    'total_refunded' => (float) $wallet->total_refunded,
                    'default_payment_method_id' => $wallet->default_payment_method_id,
                    'auto_renew_enabled' => $wallet->auto_renew_enabled,
                    'created_at' => $wallet->created_at,
                    'updated_at' => $wallet->updated_at,
                ];
            
            case 'teacher':
                $wallet = $user->teacherWallet;
                return $wallet ? [
                    'id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'payment_id' => $wallet->payment_id ?? null,
                    'balance' => (float) $wallet->balance,
                    'total_spent' => (float) $wallet->total_spent,
                    'total_refunded' => (float) $wallet->total_refunded,
                    'default_payment_method_id' => $wallet->default_payment_method_id,
                    'auto_renew_enabled' => $wallet->auto_renew_enabled,
                    'created_at' => $wallet->created_at,
                    'updated_at' => $wallet->updated_at,
                ] : null;
            
            case 'guardian':
                $wallet = $user->guardianWallet;
                return $wallet ? [
                    'id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'payment_id' => $wallet->payment_id ?? null,
                    'balance' => (float) $wallet->balance,
                    'total_spent' => (float) $wallet->total_spent,
                    'total_refunded' => (float) $wallet->total_refunded,
                    'default_payment_method_id' => $wallet->default_payment_method_id,
                    'auto_renew_enabled' => $wallet->auto_renew_enabled,
                    'created_at' => $wallet->created_at,
                    'updated_at' => $wallet->updated_at,
                ] : null;
            
            default:
                return null;
        }
    }
}
