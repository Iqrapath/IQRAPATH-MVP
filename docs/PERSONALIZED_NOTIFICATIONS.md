# Personalized Notifications

IQRAPATH's notification system supports personalized content for each recipient. This document explains how this feature works and how to use it.

## Overview

The notification system allows for creating a single notification that can be sent to multiple recipients, with each recipient receiving personalized content based on their specific details (name, role, etc.).

## How It Works

1. **Notification Templates**: Define templates with placeholders like `[user_name]`, `[amount_paid]`, etc.
2. **Notification Creation**: Create notifications from templates or directly with placeholders.
3. **Personalization**: When a notification is delivered to a recipient, the placeholders are replaced with actual values specific to that recipient.
4. **Storage**: The personalized content is stored in the `personalized_content` column of the `notification_recipients` table as a JSON object.

## Available Placeholders

### Basic Placeholders
- `[user_name]`: The recipient's full name
- `[first_name]`: The recipient's first name
- `[last_name]`: The recipient's last name
- `[user_email]`: The recipient's email address
- `[user_role]`: The recipient's role (teacher, student, guardian, admin)
- `[date]`: Current date (formatted as "Month Day, Year")
- `[time]`: Current time (formatted as "Hour:Minute AM/PM")
- `[app_name]`: The application name from config

### Role-Specific Placeholders

#### Teacher
- `[teacher_subject]`: Teacher's primary subject
- `[teacher_rating]`: Teacher's rating
- `[teacher_experience]`: Teacher's experience

#### Student
- `[student_level]`: Student's learning level
- `[student_grade]`: Student's grade

#### Guardian
- `[guardian_children]`: Number of children under the guardian

### Custom Placeholders

You can also include custom placeholders in the notification metadata, which will be processed along with the standard placeholders.

## How to Use

### Creating a Template with Placeholders

```php
$template = NotificationTemplate::create([
    'name' => 'payment_confirmation',
    'title' => 'Payment Confirmation - [amount_paid]',
    'body' => 'Dear [user_name],\n\nYour payment of [amount_paid] has been received.\n\nThank you,\n[app_name] Team',
    'type' => 'payment',
    'is_active' => true,
]);
```

### Creating a Notification with Metadata

```php
$notification = $notificationService->createNotification([
    'title' => 'Payment Confirmation - [amount_paid]',
    'body' => 'Dear [user_name],\n\nYour payment of [amount_paid] has been received.\n\nThank you,\n[app_name] Team',
    'type' => 'payment',
    'metadata' => [
        'amount_paid' => '$50.00',
        'payment_date' => '2023-10-15',
    ],
]);
```

### Accessing Personalized Content

When retrieving notifications for display, use the accessor methods to get the personalized content:

```php
$recipient = NotificationRecipient::find($id);

// Get personalized title
$title = $recipient->personalized_title;

// Get personalized body
$body = $recipient->personalized_body;
```

## Implementation Details

### NotificationRecipient Model

The `NotificationRecipient` model has accessors that automatically return either the personalized content (if available) or fall back to the original notification content:

```php
public function getPersonalizedTitleAttribute()
{
    if (isset($this->personalized_content['title'])) {
        return $this->personalized_content['title'];
    }
    
    return $this->notification ? $this->notification->title : '';
}

public function getPersonalizedBodyAttribute()
{
    if (isset($this->personalized_content['body'])) {
        return $this->personalized_content['body'];
    }
    
    return $this->notification ? $this->notification->body : '';
}
```

### NotificationService

The `NotificationService` handles the placeholder replacement in the `processPlaceholders` method, which is called right before delivering a notification to a recipient:

```php
public function processPlaceholders(Notification $notification, User $user): array
{
    $title = $notification->title;
    $body = $notification->body;
    
    // Get metadata from notification (may contain custom placeholders)
    $metadata = $notification->metadata ?? [];
    
    // Basic user placeholders
    $placeholders = [
        'user_name' => $user->name,
        'first_name' => explode(' ', $user->name)[0] ?? $user->name,
        // ... other placeholders
    ];
    
    // Role-specific placeholders
    // ... code to add role-specific placeholders
    
    // Merge with metadata placeholders
    $placeholders = array_merge($placeholders, $metadata);
    
    // Replace all placeholders
    foreach ($placeholders as $key => $value) {
        $title = str_replace("[$key]", $value, $title);
        $body = str_replace("[$key]", $value, $body);
    }
    
    return [
        'title' => $title,
        'body' => $body
    ];
}
```

## Best Practices

1. **Define Standard Placeholders**: Maintain a list of standard placeholders in your documentation.
2. **Test Personalization**: Test notifications with different recipient types to ensure placeholders are replaced correctly.
3. **Fallback Values**: Consider providing fallback values for placeholders that might not have a value for every user.
4. **Metadata Usage**: Use the metadata field for notification-specific placeholders that aren't directly related to the user. 