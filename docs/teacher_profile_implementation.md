# Teacher Profile Implementation

## Database Structure

We've updated the following tables to support teacher profiles:

1. **teacher_profiles**
   - Added `intro_video_url` for storing intro videos
   - Added `education` for educational background
   - Added `qualification` for qualifications
   - Added `rating` and `reviews_count` for teacher ratings

2. **subjects**
   - Added `is_active` field to enable/disable subjects

3. **teacher_availabilities**
   - Added `time_zone` for teacher's timezone
   - Added `preferred_teaching_hours` for preferred hours
   - Added `availability_type` for part-time/full-time status

## Controllers

We've created the following controllers for teacher profile management:

1. **ProfileController** (`app/Http/Controllers/Teacher/ProfileController.php`)
   - `index()` - Display teacher profile page
   - `updateBasicInfo()` - Update basic user information
   - `updateProfile()` - Update teacher-specific information
   - `updateAvatar()` - Upload/update profile picture
   - `uploadIntroVideo()` - Upload/update intro video

2. **SubjectController** (`app/Http/Controllers/Teacher/SubjectController.php`)
   - `store()` - Add a new subject
   - `update()` - Update subject status
   - `destroy()` - Delete a subject

3. **AvailabilityController** (`app/Http/Controllers/Teacher/AvailabilityController.php`)
   - `store()` - Add a new availability
   - `update()` - Update an existing availability
   - `destroy()` - Delete an availability
   - `updatePreferences()` - Update timezone and preferred hours

## Policies

We've created policies to handle authorization:

1. **SubjectPolicy** - Ensures teachers can only manage their own subjects
2. **TeacherAvailabilityPolicy** - Ensures teachers can only manage their own availabilities

## Routes

We've added the following routes for teacher profile management:

```php
// Teacher profile routes
Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
Route::put('/profile/basic-info', [ProfileController::class, 'updateBasicInfo'])->name('profile.update-basic-info');
Route::put('/profile/teacher-info', [ProfileController::class, 'updateProfile'])->name('profile.update-teacher-info');
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
Route::post('/profile/intro-video', [ProfileController::class, 'uploadIntroVideo'])->name('profile.upload-intro-video');

// Teacher subjects management
Route::post('/profile/subjects', [SubjectController::class, 'store'])->name('profile.subjects.store');
Route::put('/profile/subjects/{subject}', [SubjectController::class, 'update'])->name('profile.subjects.update');
Route::delete('/profile/subjects/{subject}', [SubjectController::class, 'destroy'])->name('profile.subjects.destroy');

// Teacher availability management
Route::post('/profile/availabilities', [AvailabilityController::class, 'store'])->name('profile.availabilities.store');
Route::put('/profile/availabilities/{availability}', [AvailabilityController::class, 'update'])->name('profile.availabilities.update');
Route::delete('/profile/availabilities/{availability}', [AvailabilityController::class, 'destroy'])->name('profile.availabilities.destroy');
Route::put('/profile/availability-preferences', [AvailabilityController::class, 'updatePreferences'])->name('profile.availability-preferences.update');
```

## Next Steps

To complete the teacher profile implementation, we need to:

1. Create frontend components for the teacher profile page
2. Implement the intro video upload component
3. Create subject management UI with checkboxes
4. Create availability management UI with day selection
5. Implement timezone selection dropdown

The backend is now fully prepared to handle all the functionality shown in the UI mockups. 