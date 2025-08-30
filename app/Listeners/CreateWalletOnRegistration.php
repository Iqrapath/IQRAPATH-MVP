<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\UnifiedWalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateWalletOnRegistration
{
    // Removed ShouldQueue for immediate execution - wallets should be created synchronously

    /**
     * The unified wallet service instance.
     *
     * @var \App\Services\UnifiedWalletService
     */
    protected $walletService;

    /**
     * Create the event listener.
     */
    public function __construct(UnifiedWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        Log::info('CreateWalletOnRegistration listener triggered', ['user_id' => $event->user->id]);
        
        $user = $event->user;
        
        try {
            // Only create wallet if user has a valid role for wallet creation
            if (in_array($user->role, ['student', 'teacher', 'guardian'])) {
                $wallet = $this->walletService->getWalletForUser($user);
                
                Log::info('Wallet created/retrieved for new user', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'wallet_type' => get_class($wallet),
                    'wallet_id' => $wallet->id,
                    'initial_balance' => $wallet->balance
                ]);
            } else {
                Log::info('Wallet creation skipped - user role does not require wallet', [
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error creating wallet for new user', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't rethrow - wallet creation failure shouldn't block registration
            // The UnifiedWalletService will handle creation on first access as fallback
        }
    }
}
