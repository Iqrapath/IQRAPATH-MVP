# Database Seeders Structure

This directory contains the database seeders for the IQRAPATH application. The seeders have been reorganized to separate concerns and make them more maintainable.

## Seeder Files

### 1. DatabaseSeeder.php
Main seeder that calls all other seeders in the correct order.

### 2. AdminSeeder.php
Creates admin users and their profiles:
- 1 main admin (admin@sch.com)
- 12 additional random admin users
- Total: 13 admin users

### 3. TeacherSeeder.php
Creates teacher users and their profiles:
- 1 main teacher (teacher@sch.com)
- 5 specific teachers with known data and Unsplash avatars
- 25 additional random teachers
- Total: 31 teachers

### 4. StudentSeeder.php
Creates student users and their profiles:
- 1 main student (student@sch.com)
- 30 additional random students
- Total: 31 students

### 5. GuardianSeeder.php
Creates guardian users and their profiles:
- 1 main guardian (guardian@sch.com)
- 20 additional random guardians
- Total: 21 guardians

### 6. UnassignedUserSeeder.php
Creates unassigned users for testing:
- 1 main unassigned user (unassigned@sch.com)
- 15 additional random unassigned users
- Total: 16 unassigned users

### 7. UserSeeder.php
Handles relationships between users:
- Assigns guardians to students
- Updates guardian children count

## Factory Files

### 1. UserFactory.php
Base factory for creating users with different roles.

### 2. AdminProfileFactory.php
Factory for creating admin profiles with various permission levels.

### 3. TeacherProfileFactory.php
Factory for creating teacher profiles with realistic data.

### 4. StudentProfileFactory.php
Factory for creating student profiles with educational data.

### 5. GuardianProfileFactory.php
Factory for creating guardian profiles with relationship data.

## Usage

To run all seeders:
```bash
php artisan db:seed
```

To run a specific seeder:
```bash
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=TeacherSeeder
php artisan db:seed --class=StudentSeeder
php artisan db:seed --class=GuardianSeeder
```

## User Counts

After running all seeders, you will have:
- **Total Users**: 112
  - 13 Admins
  - 31 Teachers
  - 31 Students
  - 21 Guardians
  - 16 Unassigned Users

## Default Login Credentials

- **Admin**: admin@sch.com / 123password
- **Teacher**: teacher@sch.com / 123password
- **Student**: student@sch.com / 123password
- **Guardian**: guardian@sch.com / 123password
- **Unassigned**: unassigned@sch.com / 123password

## Notes

- All users use the initials system for avatars (avatar = null)
- Teachers have realistic profiles with subjects and ratings
- Students are assigned to guardians automatically
- Guardian children count is calculated and updated automatically
- All profiles include realistic data using Faker
