# Teacher Management Pages

This directory contains the admin teacher management pages for the IQRAPATH platform.

## Files

### `index.tsx`
- **Purpose**: Main teacher listing page with search, filtering, and management actions
- **Features**:
  - Teacher table with pagination
  - Search by name/email
  - Filter by status, subject, rating
  - Actions: Approve, Edit, View Profile, View Performance, Reject
  - Status badges and verification indicators

### `show.tsx`
- **Purpose**: Detailed teacher profile view page
- **Features**:
  - Profile header with avatar and basic info
  - Earnings summary card (wallet balance, total earned, pending payouts)
  - Contact and professional details
  - About section with bio and additional info
  - Session statistics (total, completed, upcoming, cancelled)
  - Upcoming sessions list
  - Edit buttons for profile sections

## Routes

The pages are accessible via the following routes:
- `GET /admin/teachers` - Teacher listing (index.tsx)
- `GET /admin/teachers/{id}` - Teacher profile view (show.tsx)

## Data Flow

1. **Controller**: `TeacherManagementController` handles data fetching and processing
2. **Models**: Uses `User`, `TeacherProfile`, `TeacherEarning`, `TeachingSession` models
3. **Frontend**: React components with TypeScript interfaces for type safety

## Key Features

- **Responsive Design**: Works on desktop and mobile devices
- **Real-time Data**: Fetches live data from database
- **Type Safety**: Full TypeScript support with proper interfaces
- **Admin Actions**: Complete teacher management capabilities
- **Visual Design**: Matches the platform's design system with teal accents

## Usage

1. Navigate to `/admin/teachers` to see all teachers
2. Use search and filters to find specific teachers
3. Click "View Profile" in the dropdown menu to see detailed teacher information
4. Use the various action buttons to manage teacher accounts
