# Student Card Components

A collection of reusable student card components for the IQRAPATH application.

## Components

### 1. StudentCard
A flexible student card component that can be used in various contexts.

```tsx
import { StudentCard } from '@/components/student-cards';

<StudentCard
    student={{
        id: 1,
        name: "Muhammad Usman",
        avatar: "/path/to/avatar.jpg",
        specialization: "Hifz (Memorization)",
        isOnline: true
    }}
    request={{
        description: "Need help revising previous memorized parts.",
        dateToStart: "Mar 3",
        time: "Morning (7:00 AM - 8:00 AM)",
        subjects: ["Hifz (Memorization)", "Fiqh (Intermediate)"],
        price: "$30",
        priceNaira: "₦15,000"
    }}
    onViewProfile={() => console.log('View Profile')}
    onChat={() => console.log('Chat')}
    onVideoCall={() => console.log('Video Call')}
/>
```

### 2. StudentSessionRequestCard
A specialized card for displaying session requests with all required fields.

```tsx
import { StudentSessionRequestCard } from '@/components/student-cards';

<StudentSessionRequestCard
    student={{
        id: 1,
        name: "Muhammad Usman",
        avatar: "/path/to/avatar.jpg",
        specialization: "Hifz (Memorization)",
        isOnline: true
    }}
    request={{
        description: "Need help revising previous memorized parts.",
        dateToStart: "Mar 3",
        time: "Morning (7:00 AM - 8:00 AM)",
        subjects: ["Hifz (Memorization)", "Fiqh (Intermediate)"],
        price: "$30",
        priceNaira: "₦15,000"
    }}
    onViewProfile={() => console.log('View Profile')}
    onChat={() => console.log('Chat')}
    onVideoCall={() => console.log('Video Call')}
/>
```

### 3. StudentProfileCard
A simple profile card for displaying basic student information.

```tsx
import { StudentProfileCard } from '@/components/student-cards';

<StudentProfileCard
    student={{
        id: 1,
        name: "Muhammad Usman",
        avatar: "/path/to/avatar.jpg",
        specialization: "Hifz (Memorization)",
        isOnline: true,
        subjects: ["Hifz (Memorization)", "Fiqh (Intermediate)"]
    }}
    onViewProfile={() => console.log('View Profile')}
    onChat={() => console.log('Chat')}
    onVideoCall={() => console.log('Video Call')}
/>
```

## Features

- **Responsive Design**: Works on all screen sizes
- **Online Status**: Shows online indicator when student is online
- **Avatar Fallback**: Displays initials when no avatar is provided
- **Subject Tags**: Displays subjects as styled badges
- **Action Buttons**: Configurable action buttons (View Profile, Chat, Video Call)
- **Pricing Display**: Shows pricing information in teal-colored badges
- **TypeScript Support**: Fully typed with proper interfaces

## Styling

All components use Tailwind CSS classes and follow the IQRAPATH design system:
- **Colors**: Teal accents, gray text, white backgrounds
- **Borders**: Rounded corners with subtle borders
- **Shadows**: Light shadows for depth
- **Spacing**: Consistent padding and margins
