# Debug Teacher Messages Not Working

## Quick Checks

### 1. Check Browser Console
Open browser console (F12) on teacher messages page and look for:

**Expected to see:**
```
Real-time message received: {message object}
Pusher : State changed : connecting -> connected
```

**Errors to look for:**
```
Failed to send typing indicator
Echo is not defined
Conversation not found
```

### 2. Check Network Tab
Open Network tab (F12 → Network) and look for:

**Typing indicator requests:**
- URL: `/api/conversations/{id}/typing`
- Method: POST
- Status: Should be 200

**If you see 403/401:**
- Authorization issue
- User not participant in conversation

**If you see 404:**
- Conversation ID is wrong
- Route not registered

### 3. Check if selectedConversation exists

Add this temporarily to teacher messages page:
```typescript
console.log('Selected Conversation:', selectedConversation);
console.log('Auth User:', auth.user);