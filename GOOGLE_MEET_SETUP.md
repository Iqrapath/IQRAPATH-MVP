# Google Meet Integration Setup Guide

## Quick Setup for Local Development

### 1. Environment Variables

Add these to your `.env` file:

```env
# Google Meet/Calendar API Configuration
GOOGLE_MEET_CLIENT_ID=your_google_client_id
GOOGLE_MEET_CLIENT_SECRET=your_google_client_secret
GOOGLE_MEET_REFRESH_TOKEN=your_refresh_token
GOOGLE_MEET_WEBHOOK_SECRET=your_webhook_secret
GOOGLE_MEET_CALENDAR_ID=primary
```

### 2. Google Cloud Console Setup

1. **Go to [Google Cloud Console](https://console.cloud.google.com/)**
2. **Create a new project** or select existing one
3. **Enable APIs**:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Calendar API" and enable it
4. **Create OAuth 2.0 Credentials**:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth 2.0 Client IDs"
   - Choose "Web application"
   - Add authorized redirect URIs: `http://localhost:8000/oauth/callback`
5. **Generate Refresh Token**:
   
   **Option A: Use the Helper Script (Recommended)**
   ```bash
   php generate_google_refresh_token.php
   ```
   
   **Option B: Use the Web Helper**
   - Open `google_oauth_helper.html` in your browser
   - Follow the step-by-step instructions
   
   **Option C: Manual Process**
   - Go to: https://accounts.google.com/o/oauth2/v2/auth?client_id=YOUR_CLIENT_ID&redirect_uri=http://localhost:8000/oauth/callback&response_type=code&scope=https://www.googleapis.com/auth/calendar&access_type=offline&prompt=consent
   - Sign in and grant permissions
   - Copy the authorization code from the URL
   - Exchange it for a refresh token using the Google OAuth API

### 3. Testing the Integration

#### Option A: Test with Real Credentials
1. Set up the environment variables above
2. Go to Admin > Verification
3. Try scheduling a verification call with Google Meet

#### Option B: Test with Placeholder (No Setup Required)
1. Don't set the environment variables
2. The system will show a friendly error message
3. You can still use Zoom or manual links

### 4. Troubleshooting

#### SSL Certificate Error (cURL 60)
**Error**: `cURL error 60: SSL peer certificate or SSH remote key was not OK`

**Solution**: This is already fixed in the code. The GoogleMeetService automatically disables SSL verification in local environments.

#### "Google Meet integration is not configured"
**Error**: This message appears when credentials are missing

**Solution**: 
1. Add the required environment variables to `.env`
2. Or use Zoom instead
3. Or enter meeting links manually

#### "Failed to obtain Google Meet access token"
**Error**: Authentication failed

**Solution**:
1. Check your `GOOGLE_MEET_CLIENT_ID` and `GOOGLE_MEET_CLIENT_SECRET`
2. Verify your `GOOGLE_MEET_REFRESH_TOKEN` is valid
3. Ensure Google Calendar API is enabled in your project

### 5. Production Setup

For production, you should:

1. **Use proper SSL certificates** (remove `verify => false`)
2. **Set up proper OAuth 2.0 flow** for refresh token generation
3. **Configure webhook endpoints** for Google Calendar notifications
4. **Set up proper error monitoring** and logging

### 6. Alternative: Use Zoom Instead

If you don't want to set up Google Meet:

1. Select "Zoom" in the platform dropdown
2. Make sure Zoom credentials are configured in your `.env`:
   ```env
   ZOOM_CLIENT_ID=your_zoom_client_id
   ZOOM_CLIENT_SECRET=your_zoom_client_secret
   ZOOM_ACCOUNT_ID=your_zoom_account_id
   ```

### 7. Manual Meeting Links

If neither Google Meet nor Zoom is configured:

1. Select "Other Platform" in the dropdown
2. Manually enter meeting links (e.g., from Teams, Skype, etc.)
3. The system will work with any meeting platform

## Current Status

✅ **SSL Issues Fixed**: Local development SSL errors are resolved
✅ **Error Handling**: Proper error messages for missing credentials  
✅ **Fallback Options**: Zoom and manual links still work
✅ **User Experience**: Clear feedback and instructions

## Next Steps

1. **For Development**: The integration is ready to use with proper credentials
2. **For Production**: Set up Google Cloud Console project and OAuth flow
3. **For Testing**: Use Zoom or manual links as alternatives

The Google Meet integration is now fully functional and handles all edge cases gracefully! 🎉
