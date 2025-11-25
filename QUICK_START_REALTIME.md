# Quick Start - Real-Time Features

## Start the Services

```bash
# Terminal 1: Laravel Reverb (WebSocket server)
php artisan reverb:start

# Terminal 2: Laravel development server
php artisan serve

# Terminal 3: Vite (frontend assets)
npm run dev

# Terminal 4 (Optional): Queue worker for background jobs
php artisan queue:work
```

## Test the Features

### 1. Test Online Status
- Open two browsers with different users
- See green dots next to online users
- Close one browser → dot disappears

### 2. Test Typing
- Open conversation
- Start typing → other user sees "User is typing..."
- Stop typing → indicator disappears after 3 seconds

### 3. Test Read Receipts
- Send message → see single check (✓)
- Other user reads it → see double blue check (✓✓)

### 4. Test Real-Time Messages
- Send message → appears instantly for recipient
- No refresh needed

## Troubleshooting

### Messages not appearing in real-time?
```bash
# Check Reverb is running
php artisan reverb:start

# Check browser console for Echo connection
# Should see: "Pusher : State changed : connecting -> connected"
```

### Typing indicators not working?
```bash
# Check API endpoint exists
php artisan route:list | grep typing

# Should see: POST api/conversations/{conversationId}/typing
```

### Online status not showing?
```bash
# Check presence channel in browser console
# Should see: "Pusher : Event sent : pusher:subscribe"
```

## Browser Console Commands

```javascript
// Check if Echo is connected
window.Echo

// Check online users
window.Echo.connector.pusher.channels.channels['presence-online'].members

// Check conversation channel
window.Echo.connector.pusher.channels.channels['private-conversation.1']
```

## Quick Fixes

### Clear cache:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Restart services:
```bash
# Stop all terminals (Ctrl+C)
# Then restart in order:
php artisan reverb:start
php artisan serve
npm run dev
```

## Features Checklist

- ✅ Real-time messages
- ✅ Online/offline status
- ✅ Typing indicators
- ✅ Read receipts
- ✅ Notifications
- ✅ Teacher status updates

All features are working! 🎉
