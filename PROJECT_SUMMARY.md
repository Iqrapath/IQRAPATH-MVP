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

### Developer Guidance
- **Do not use** Tailwind's default color or font utilities. Use only the CSS variables and classes defined in `app.css`.
- All new components and pages must use the design system tokens for color, font, and typography.
- If you need to add new design tokens (e.g., spacing, border radius), update `app.css` and document the change here.
- This file will be updated as the project evolves.

---
1. _Last updated: 10th June 2024_ 