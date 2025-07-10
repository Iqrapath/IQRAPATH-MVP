# Project Summary

## Design System Enforcement (June 2024)

### Overview
This project enforces a strict design system for all UI development. The design system includes a specific color palette, font families, and typography rules. All custom styles and components must adhere to these tokens. Tailwind CSS is used for utility classes, but all color and font utilities are overridden by the design system.

### Key Actions Taken
- **CSS Updated:**
  - Defined only the design system's color palette (Midnight Blue, White, Greyscale 50-900) as CSS variables in `app.css`.
  - Defined only the design system's font families (Nunito, Inter, Poppins) as CSS variables.
  - All heading, body, and button styles are set to match the design system's font, size, weight, and line height.
  - Utility classes for all body and button text styles are included for consistent use.
  - Comments added to clarify that only these tokens should be used—no Tailwind default colors or fonts.
  - `.btn` class updated to match the design system's button style (border-radius, padding, border, color, background, cursor, transition).

## Authentication System Redesign (July 2024)

### Overview
The authentication system UI has been completely redesigned to match the new design system and improve user experience. This includes login, registration, and password reset flows.

### Key Actions Taken
- **Authentication Pages Redesigned:**
  - Login page updated with new layout, form fields, and social login options
  - Registration page redesigned with improved form layout and validation
  - Password reset flow (forgot password and reset password) redesigned for better usability
  - Added show/hide password toggles for better user experience
  - Implemented consistent error states and validation messages
  - Applied the teal color scheme consistently across all auth pages

- **Email Templates Customized:**
  - Created custom password reset notification using Markdown templates
  - Designed branded email templates with IqraPath logo and color scheme
  - Implemented responsive email design that works across devices
  - Added Islamic greeting and professional formatting
  - Created both HTML and text fallback versions for email clients

### Developer Guidance
- **Do not use** Tailwind's default color or font utilities. Use only the CSS variables and classes defined in `app.css`.
- All new components and pages must use the design system tokens for color, font, and typography.
- If you need to add new design tokens (e.g., spacing, border radius), update `app.css` and document the change here.
- For email templates, use the custom components in `resources/views/vendor/mail/html/` to maintain brand consistency.
- When adding new authentication features, follow the established patterns for form design and validation.
- This file will be updated as the project evolves.

---
1. _Last updated: 10th July 2024_ 