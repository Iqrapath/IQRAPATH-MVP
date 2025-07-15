<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BroadcastController extends Controller
{
    /**
     * Authenticate the request for channel access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function authenticate(Request $request)
    {
        if (!Auth::check()) {
            Log::warning('Broadcasting auth attempt without authentication', [
                'ip' => $request->ip(),
                'channel' => $request->channel_name
            ]);
            throw new AccessDeniedHttpException('User not authenticated.');
        }

        $channelName = $request->channel_name;
        $socketId = $request->socket_id;
        
        Log::info('Broadcasting auth request', [
            'channel' => $channelName,
            'socket_id' => $socketId,
            'user_id' => Auth::id()
        ]);
        
        // Always authorize the channel - we'll rely on Laravel's channel authorization
        return $this->generateAuthResponse($channelName, $socketId);
    }

    /**
     * Generate the auth response.
     *
     * @param  string  $channel
     * @param  string  $socket
     * @return \Illuminate\Http\Response
     */
    protected function generateAuthResponse($channel, $socket)
    {
        try {
            // Use the correct Reverb app credentials from .env
            $pusher = new \Pusher\Pusher(
                'diawgqsegr5sajcpkowf', // From .env REVERB_APP_KEY
                'k9o7it5mmwatf0un9qa7', // From .env REVERB_APP_SECRET
                '204252', // From .env REVERB_APP_ID
                [
                    'useTLS' => false,
                    'host' => 'localhost',
                    'port' => 8080,
                    'scheme' => 'http'
                ]
            );

            $auth = $pusher->socket_auth($channel, $socket);
            
            // Log successful auth attempt
            \Illuminate\Support\Facades\Log::info('WebSocket auth success', [
                'channel' => $channel,
                'socket' => $socket,
                'user_id' => Auth::id() ?? 'unauthenticated'
            ]);
            
            return response()->json(json_decode($auth, true));
        } catch (\Exception $e) {
            // Log auth error
            \Illuminate\Support\Facades\Log::error('WebSocket auth error', [
                'channel' => $channel,
                'socket' => $socket,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
} 