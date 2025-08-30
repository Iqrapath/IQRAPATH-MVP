# Student Dashboard Components

This directory contains all the components used in the student dashboard. These components are designed to provide students with a comprehensive view of their learning journey, upcoming classes, progress tracking, and teacher recommendations.

## Components

### HeroBanner
- **Purpose**: Welcoming banner with personalized greeting
- **Props**: `name` (string), `subtitle` (string)
- **Features**: Teal gradient background, decorative images, responsive design
- **Design**: Matches the exact design reference with "Welcome {name}!" and "Ready to start learning?"

### StudentStatsCard
- **Purpose**: Displays key learning statistics
- **Props**: `headerAction` (ReactNode), `stats` (StatItem[])
- **Features**: Grid layout for stats, "Browse Teachers" action button
- **Design**: Shows "Your Stats" title with three stat pills (Total Class, Class Completed, Upcoming Class)

### StatPill
- **Purpose**: Individual stat display with icon and gradient background
- **Props**: `title` (string), `value` (number|string), `icon` (ReactNode), `gradient` (string)
- **Features**: Gradient backgrounds, icon support, responsive design
- **Design**: Horizontal pill layout with specific gradients (purple-blue, green-blue, yellow-orange)

### UpcomingClasses
- **Purpose**: Shows scheduled classes with join functionality
- **Props**: `classes` (ClassItem[])
- **Features**: Class status indicators, start session buttons, empty state
- **Design**: "Upcoming Class" title with "View ALL Class" action, shows class details with "Start Session" buttons

### RecommendedTeachers
- **Purpose**: Displays teacher recommendations
- **Props**: `teachers` (TeacherItem[])
- **Features**: Teacher cards with ratings, view profile functionality, recommendation badges
- **Design**: "Top Rated Teachers for You" title with teacher cards showing ratings and "View Profile" links

### RecentActivity
- **Purpose**: Shows recent learning activities and achievements
- **Props**: `activities` (ActivityItem[])
- **Features**: Activity types with icons, timestamps, empty state
- **Design**: Activity feed with colored icons and timestamps

## Usage Example

```tsx
import { 
    HeroBanner, 
    StudentStatsCard, 
    UpcomingClasses, 
    RecommendedTeachers,
    RecentActivity
} from './components';

// In your dashboard component
<HeroBanner name="Ahmed" subtitle="Ready to start learning?" />
<StudentStatsCard stats={statsData} />
<UpcomingClasses classes={classesData} />
<RecentActivity activities={activitiesData} />
<RecommendedTeachers teachers={teachersData} />
```

## Data Structures

### StatItem
```typescript
interface StatItem {
    title: string;
    value: number | string;
    icon: ReactNode;
    gradient: string;
}
```

### ClassItem
```typescript
interface ClassItem {
    id: number;
    title: string;
    teacher: string;
    subject: string;
    date: string;
    time: string;
    status: 'Confirmed' | 'Pending' | 'Completed';
    imageUrl: string;
    meetingUrl?: string;
}
```

### TeacherItem
```typescript
interface TeacherItem {
    id: number;
    name: string;
    subjects: string;
    location: string;
    rating: number;
    price: string;
    avatarUrl: string;
    isRecommended?: boolean;
}
```

### ActivityItem
```typescript
interface ActivityItem {
    id: number;
    type: 'class_completed' | 'achievement' | 'assignment' | 'session_booked';
    title: string;
    description: string;
    timestamp: string;
    icon: ReactNode;
    color: string;
}
```

## Design Implementation

The components follow the exact design reference with:

### Color Scheme
- **Primary**: Teal gradient (`from-teal-600 via-teal-500 to-emerald-400`) for hero banner
- **Stats Gradients**: 
  - Purple to blue (`from-purple-50 to-blue-50`)
  - Green to blue (`from-green-50 to-blue-50`) 
  - Yellow to orange (`from-yellow-50 to-orange-50`)
- **Action Colors**: `#2c7870` (teal) for links and buttons

### Layout Structure
1. **Hero Banner** - Teal gradient with welcome message
2. **Your Stats** - Three stat pills with specific gradients
3. **Upcoming Class** - Class listings with "Start Session" buttons
4. **Recent Activity** - Activity feed with colored icons
5. **Top Rated Teachers for You** - Teacher cards with ratings

### Typography
- **Main Titles**: Bold, larger font sizes
- **Subtitles**: Regular weight, smaller font sizes
- **Numbers in Stats**: Large, bold display

### Icons
- **FileText** - for Total Class stat
- **Trophy** - for Class Completed stat  
- **MoreHorizontal** - for Upcoming Class stat
- **Star** - for ratings
- **MapPin** - for locations
- **Calendar** - for scheduling
- **Trophy** - for achievements

## Styling

All components use Tailwind CSS classes and follow the design system:
- **Border radius**: `rounded-[28px]` for cards, `rounded-2xl` for smaller elements
- **Shadows**: `shadow-sm` for subtle elevation
- **Responsive design**: Mobile-first approach
- **Consistent spacing**: `space-y-8` between sections
