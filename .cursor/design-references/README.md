# Design References Directory

This directory contains reference images that Cursor uses to implement UI components exactly as designed.

## How to Use

### 1. Add Design Images Here
Place your design mockups, wireframes, and UI specifications in this directory structure:

```
.cursor/design-references/
├── admin/
│   ├── dashboard/
│   │   ├── overview.png
│   │   ├── users-list.png
│   │   ├── user-detail.png
│   │   └── stats-cards.png
│   ├── notifications/
│   │   ├── create-notification.png
│   │   ├── notifications-list.png
│   │   └── notification-detail.png
│   ├── users/
│   │   ├── user-list.png
│   │   ├── user-form.png
│   │   └── user-profile.png
│   └── settings/
├── student/
│   ├── dashboard.png
│   ├── booking-flow.png
│   ├── profile.png
│   ├── progress-tracking.png
│   └── subscription-management.png
├── teacher/
│   ├── dashboard.png
│   ├── availability-calendar.png
│   ├── earnings-overview.png
│   ├── session-management.png
│   └── verification-process.png
├── guardian/
│   ├── dashboard.png
│   ├── children-management.png
│   ├── payment-management.png
│   └── messaging.png
├── auth/
│   ├── login.png
│   ├── register.png
│   ├── forgot-password.png
│   └── profile-completion.png
└── components/
    ├── forms/
    │   ├── contact-form.png
    │   ├── booking-form.png
    │   └── payment-form.png
    ├── modals/
    │   ├── confirmation-modal.png
    │   ├── booking-modal.png
    │   └── notification-modal.png
    ├── cards/
    │   ├── stat-card.png
    │   ├── user-card.png
    │   └── session-card.png
    ├── tables/
    │   ├── users-table.png
    │   ├── bookings-table.png
    │   └── transactions-table.png
    └── navigation/
        ├── sidebar.png
        ├── header.png
        └── breadcrumbs.png
```

### 2. Naming Convention

Use this pattern for all images:
```
[section]/[page]/[component]-[state]-[viewport].png

Examples:
admin/users/list-default-desktop.png
admin/users/list-loading-desktop.png
admin/users/create-modal-desktop.png
student/dashboard/overview-default-mobile.png
components/cards/stat-card-success-desktop.png
components/forms/booking-form-error-mobile.png
```

### 3. Required States

For each component, provide images for:
- **Default state** - Normal appearance
- **Loading state** - When data is being fetched
- **Error state** - When something goes wrong
- **Empty state** - When no data is available
- **Success state** - After successful operations
- **Hover/Focus states** - Interactive states (optional)

### 4. Viewport Sizes

Provide designs for:
- **Desktop** - 1440px width minimum
- **Mobile** - 375px width minimum
- **Tablet** - 768px width (optional)

### 5. Image Quality Guidelines

- **Format**: PNG for UI mockups, JPG for photos
- **Resolution**: High enough to see all details clearly
- **Annotations**: Use red boxes/arrows to highlight specific requirements
- **Text**: Ensure all text in designs is readable

## How Cursor Uses These Images

When you ask Cursor to implement UI components, it will:

1. **Look for reference image** in the appropriate directory
2. **Analyze the design** for exact spacing, colors, typography
3. **Implement pixel-perfect** matching code
4. **Refuse to deviate** from the provided design
5. **Ask for clarification** if design is ambiguous

## Example Usage

When you say: "Create the admin users list page"

Cursor will:
1. Look for `.cursor/design-references/admin/users/list-*.png`
2. Implement the exact layout, spacing, and styling shown
3. Use only the colors, fonts, and components shown in the image
4. Match the responsive behavior if multiple viewport images exist

## Tips for Better Results

### 1. Be Specific
Instead of: "Make it look nice"
Use: "Implement exactly as shown in admin/dashboard/overview.png"

### 2. Provide Context
Include multiple states:
- `admin/users/list-default.png` - Normal state
- `admin/users/list-loading.png` - Loading state
- `admin/users/list-empty.png` - No users state

### 3. Highlight Key Areas
Use annotations on your images to point out:
- Exact spacing measurements
- Specific colors or gradients
- Interactive behaviors
- Responsive breakpoints

### 4. Update Images When Designs Change
Always update the reference images when designs evolve. Cursor will continue to implement based on the images in this directory.

## Integration with Development

* The UI design enforcement rules (`.cursor/rules/ui-design-enforcement.mdc`) will automatically reference images in this directory and ensure Cursor implements designs exactly as specified.
* When design/image doesn't come with responsive view create it

Remember: These images are the source of truth for all UI implementation. Code must match the design, not the other way around.
