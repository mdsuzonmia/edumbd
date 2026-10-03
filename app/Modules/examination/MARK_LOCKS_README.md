# Mark Locks Feature

This feature provides a comprehensive lock/unlock system for examination marks with full audit trail.

## Database Setup

Run the migration to create the `mark_locks` table:

```bash
php spark migrate
```

Or manually run the migration:
```bash
php spark migrate:latest
```

## Features

### 1. Lock Marks
- **Route:** `POST examination/marks/lock`
- **Purpose:** Lock marks for a specific exam/class/subject combination
- **Required Parameters:**
  - `school_id` - School ID
  - `exam_id` - Exam ID
  - `class_id` - Class ID
- **Optional Parameters:**
  - `session_id` - Academic year/session
  - `section_id` - Section ID
  - `subject_id` - Subject ID
  - `distribution_id` - Mark distribution ID
  - `lock_reason` - Reason for locking

### 2. Unlock Marks
- **Route:** `POST examination/marks/unlock`
- **Purpose:** Unlock previously locked marks
- **Required Parameters:**
  - `school_id` - School ID
  - `exam_id` - Exam ID
  - `class_id` - Class ID
- **Optional Parameters:**
  - `section_id` - Section ID
  - `subject_id` - Subject ID
  - `distribution_id` - Mark distribution ID
  - `unlock_reason` - Reason for unlocking

### 3. Check Lock Status
- **Route:** `POST examination/marks/check-lock-status`
- **Purpose:** Check if marks are locked for specific criteria
- **Response:**
  ```json
  {
    "status": true,
    "is_locked": true,
    "locked_at": "2026-06-26 10:30:00",
    "lock_reason": "Final marks",
    "locked_by_name": "John Doe"
  }
  ```

### 4. View Locked Marks List
- **Route:** `GET examination/marks/locked-marks`
- **Purpose:** View all locked marks with filtering options
- **Query Parameters:**
  - `school_id` - Filter by school
  - `exam_id` - Filter by exam

## Database Schema

The `mark_locks` table includes:
- `id` - Primary key
- `school_id` - School identifier
- `school_owner_uid` - User who owns the school
- `exam_id` - Exam identifier
- `session_id` - Academic session
- `class_id` - Class identifier
- `section_id` - Section identifier (nullable)
- `subject_id` - Subject identifier (nullable)
- `distribution_id` - Distribution identifier (nullable)
- `is_locked` - Lock status (0/1)
- `locked_at` - Timestamp when locked
- `locked_by` - User ID who locked
- `unlocked_at` - Timestamp when unlocked
- `unlocked_by` - User ID who unlocked
- `lock_reason` - Reason for locking/unlocking
- `remarks` - Additional notes
- `created_at` - Record creation timestamp
- `updated_at` - Record update timestamp

## Usage Examples

### Lock Marks via AJAX
```javascript
$.post('<?= site_url("examination/marks/lock") ?>', {
    school_id: 1,
    exam_id: 5,
    class_id: 10,
    section_id: 3,
    subject_id: 7,
    lock_reason: 'Final examination marks',
    <?= csrf_field() ?>
}, function(response) {
    console.log(response);
});
```

### Check Lock Status
```javascript
$.post('<?= site_url("examination/marks/check-lock-status") ?>', {
    school_id: 1,
    exam_id: 5,
    class_id: 10,
    <?= csrf_field() ?>
}, function(response) {
    if (response.is_locked) {
        console.log('Marks are locked by:', response.locked_by_name);
        console.log('Locked at:', response.locked_at);
    }
});
```

## Model Methods

### MarkLockModel

- `isLocked($school_id, $exam_id, $class_id, $section_id, $subject_id, $distribution_id)` - Check if marks are locked
- `getLockStatus(...)` - Get lock record details
- `getLockedMarksBySchool($school_id, $exam_id)` - Get all locked marks for a school
- `getLockHistory($school_id, $exam_id, $class_id, $section_id, $subject_id)` - Get lock/unlock history
- `setLock($data)` - Create or update lock record

## Benefits

1. **Full Audit Trail:** Track who locked/unlocked marks and when
2. **Flexible Locking:** Lock at different levels (exam/class/subject/distribution)
3. **Prevention of Duplicate Locks:** Built-in check to prevent double-locking
4. **Complete History:** Maintains history of lock/unlock actions with reasons
5. **Easy Management:** View all locked marks in one place with ability to unlock

## Notes

- The lock is based on a unique combination of school, exam, class, section, subject, and distribution
- Multiple lock records can exist for the same criteria over time (history is preserved)
- The `is_locked` flag indicates the current status
- Unlocking appends the unlock reason to the existing lock reason for complete audit trail