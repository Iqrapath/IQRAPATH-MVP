# Teacher Show Components

This directory contains reusable components for the teacher profile show page.

## Components

### `TeacherProfileHeader`
- **Purpose**: Displays the teacher's profile header with background image, avatar, name, role, location, verification status, and earnings card
- **Props**: `teacher`, `profile`, `earnings?`
- **Features**: 
  - Background image with fallback to gradient
  - Circular avatar with fallback initials
  - Teacher information display (name, role, location, verification)
  - Earnings card on the right side with wallet balance, total earned, and pending payouts
  - Flex layout with justify-between for proper spacing



### `TeacherContactDetails`
- **Purpose**: Displays teacher contact and professional information
- **Props**: `teacher`, `profile`, `totalSessions`
- **Features**:
  - Email and phone information
  - Subjects and session count
  - Rating and reviews
  - Edit buttons for each section

### `TeacherAboutSection`
- **Purpose**: Shows teacher biography and additional profile details
- **Props**: `profile`
- **Features**:
  - Biography text with fallback message
  - Experience, languages, and teaching mode
  - Edit button for profile information



## Usage

```tsx
import {
  TeacherProfileHeader,
  TeacherContactDetails,
  TeacherAboutSection
} from './show-components';

// In your main component:
<TeacherProfileHeader teacher={teacher} profile={profile} earnings={earnings} />
<TeacherContactDetails teacher={teacher} profile={profile} totalSessions={totalSessions} />
<TeacherAboutSection profile={profile} />
```

## Benefits

- **Modularity**: Each component has a single responsibility
- **Reusability**: Components can be used in other parts of the application
- **Maintainability**: Easier to update and debug individual components
- **Type Safety**: Full TypeScript support with proper interfaces
- **Consistency**: Uniform styling and behavior across components
