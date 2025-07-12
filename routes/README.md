# IQRAPATH Route Organization

This directory contains all the route definitions for the IQRAPATH application. Routes are organized into separate files by feature area to improve maintainability and readability.

## Route Files

- **web.php**: Main entry point and home page routes
- **auth.php**: Authentication routes (login, registration, password reset)
- **settings.php**: User profile and settings routes
- **dashboard.php**: Dashboard routes for all user roles
- **admin.php**: Admin-specific routes for user management
- **financial.php**: Financial routes for transactions, payouts, and earnings
- **subscriptions.php**: Subscription plan and user subscription routes
- **notifications.php**: Notification management and user notification routes
- **sessions.php**: Teaching session and attendance routes
- **payments.php**: Payment processing and wallet management routes
- **feedback.php**: Feedback, support tickets, and dispute management routes

## Route Groups

Routes are typically grouped by:
1. Authentication requirements
2. Role requirements
3. Feature area

## Middleware

Common middleware used:
- `auth`: Ensures the user is authenticated
- `verified`: Ensures the user has verified their email
- `role:X`: Ensures the user has the specified role

## Adding New Routes

When adding new routes:
1. Determine which feature area the route belongs to
2. Add it to the appropriate route file
3. Use consistent naming conventions
4. Group related routes together
5. Apply appropriate middleware

## Route Naming Conventions

Routes follow the pattern: `{area}.{resource}.{action}`

Examples:
- `admin.users.index`
- `teacher.documents.show`
- `subscriptions.plans` 