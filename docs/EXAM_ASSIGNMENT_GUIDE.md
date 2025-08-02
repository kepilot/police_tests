# Exam Assignment System Guide

## Overview

The Exam Assignment System allows administrators to assign exams to specific users with due dates, track completion status, and manage exam attempts. This system provides a complete workflow from exam creation to result analysis.

## Features

### 🎯 Core Features

- **Exam Assignment**: Assign exams to specific users with due dates
- **Assignment Tracking**: Monitor assignment status (pending, completed, overdue)
- **Exam Attempts**: Create and manage exam attempts
- **Automatic Scoring**: Calculate scores and determine pass/fail status
- **Statistics**: Track exam performance and analytics
- **User Interface**: Web-based exam taking interface

### 📋 Assignment Management

- **Due Date Management**: Set and track assignment due dates
- **Status Tracking**: Monitor completion status
- **Overdue Detection**: Automatically identify overdue assignments
- **Bulk Operations**: Assign exams to multiple users

### 📊 Exam Taking

- **Timer Support**: Configurable exam duration with countdown timer
- **Question Types**: Support for multiple choice and true/false questions
- **Progress Tracking**: Real-time progress indication
- **Auto-submission**: Automatic submission when time expires
- **Result Display**: Immediate feedback with detailed results

## Database Schema

### exam_assignments Table

```sql
CREATE TABLE exam_assignments (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    exam_id VARCHAR(36) NOT NULL,
    assigned_by VARCHAR(36) NOT NULL,
    assigned_at DATETIME NOT NULL,
    due_date DATETIME NULL,
    is_completed BOOLEAN DEFAULT FALSE NOT NULL,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY unique_user_exam_assignment (user_id, exam_id)
);
```

### exam_attempts Table

```sql
CREATE TABLE exam_attempts (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    exam_id VARCHAR(36) NOT NULL,
    score INT DEFAULT 0 NOT NULL,
    passed BOOLEAN DEFAULT FALSE NOT NULL,
    started_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    deleted_at DATETIME NULL
);
```

## Usage Examples

### 1. Assign an Exam to a User

```php
$learningController = new LearningController($container);

$result = $learningController->assignExamToUser(
    $userId,
    $examId,
    $assignedBy,
    '2025-08-15 23:59:59' // Due date
);

if ($result['success']) {
    echo "Exam assigned successfully!";
}
```

### 2. Get User's Assignments

```php
$assignments = $learningController->getUserAssignments($userId);

foreach ($assignments['data'] as $assignment) {
    echo "Assignment ID: " . $assignment['id'];
    echo "Due Date: " . $assignment['due_date'];
    echo "Status: " . ($assignment['is_completed'] ? 'Completed' : 'Pending');
    echo "Overdue: " . ($assignment['is_overdue'] ? 'Yes' : 'No');
}
```

### 3. Start an Exam Attempt

```php
$attempt = $learningController->startExamAttempt($userId, $examId);

if ($attempt['success']) {
    $attemptId = $attempt['data']['attempt_id'];
    $questions = $attempt['data']['questions'];
    $examInfo = $attempt['data']['exam'];
}
```

### 4. Submit Exam Answers

```php
$answers = [
    'question_id_1' => 0, // First option selected
    'question_id_2' => 1, // Second option selected
    'question_id_3' => 0, // True for true/false
];

$result = $learningController->submitExamAttempt($attemptId, $answers);

if ($result['success']) {
    echo "Score: " . $result['data']['score'] . "/" . $result['data']['max_score'];
    echo "Percentage: " . $result['data']['percentage'] . "%";
    echo "Passed: " . ($result['data']['passed'] ? 'Yes' : 'No');
}
```

## Web Interface

### Exam Taking Interface

Access the exam interface at: `http://localhost/exam.html?examId={exam_id}&userId={user_id}`

Features:
- **Timer Display**: Shows remaining time
- **Progress Bar**: Visual progress indicator
- **Question Navigation**: Easy question navigation
- **Auto-save**: Automatic progress saving
- **Results Display**: Immediate results after submission

### Key Features

1. **Responsive Design**: Works on desktop and mobile devices
2. **Timer Warning**: Alerts when time is running low
3. **Progress Tracking**: Shows completion percentage
4. **Confirmation Dialogs**: Prevents accidental submissions
5. **Error Handling**: Graceful error handling and recovery

## API Endpoints

### Assignment Management

- `POST /api/learning/assign-exam` - Assign exam to user
- `GET /api/learning/user-assignments/{userId}` - Get user's assignments
- `GET /api/learning/pending-assignments/{userId}` - Get pending assignments
- `GET /api/learning/overdue-assignments/{userId}` - Get overdue assignments

### Exam Taking

- `POST /api/learning/start-exam-attempt` - Start exam attempt
- `POST /api/learning/submit-exam-attempt` - Submit exam answers
- `PUT /api/learning/mark-assignment-completed` - Mark assignment as completed

## Testing

### Run the Demo

```bash
# Run the comprehensive demonstration
php scripts/demo-exam-assignment.php

# Run the test suite
php scripts/test-exam-assignment.php
```

### Demo Credentials

- **Admin**: `demo.admin@example.com` / `AdminPass123!`
- **Student**: `demo.student@example.com` / `StudentPass123!`

## Configuration

### Exam Settings

- **Duration**: Configurable exam duration in minutes
- **Passing Score**: Minimum percentage required to pass
- **Question Types**: Multiple choice and true/false questions
- **Points System**: Configurable points per question

### Assignment Settings

- **Due Dates**: Optional due dates for assignments
- **Auto-completion**: Automatic completion marking
- **Overdue Detection**: Automatic overdue status updates

## Best Practices

### For Administrators

1. **Set Realistic Due Dates**: Consider user workload and exam complexity
2. **Monitor Progress**: Regularly check assignment completion rates
3. **Analyze Results**: Use statistics to improve exam quality
4. **Provide Feedback**: Give users feedback on their performance

### For Users

1. **Plan Your Time**: Start exams well before the due date
2. **Review Questions**: Read questions carefully before answering
3. **Save Progress**: Use the save progress feature regularly
4. **Check Results**: Review your results to understand areas for improvement

## Troubleshooting

### Common Issues

1. **Assignment Not Found**: Ensure the user has been assigned the exam
2. **Timer Issues**: Check browser compatibility and JavaScript settings
3. **Submission Errors**: Verify all questions have been answered
4. **Database Errors**: Check database connectivity and permissions

### Error Messages

- `"No assignment found for this exam"` - User hasn't been assigned the exam
- `"Exam has already been completed"` - User has already taken this exam
- `"Time is up"` - Exam duration has expired
- `"Invalid answer format"` - Answer format is incorrect

## Future Enhancements

### Planned Features

- **Bulk Assignment**: Assign exams to multiple users at once
- **Exam Templates**: Reusable exam templates
- **Advanced Analytics**: Detailed performance analytics
- **Mobile App**: Native mobile application
- **Offline Support**: Offline exam taking capability
- **Proctoring**: Advanced proctoring features

### Integration Possibilities

- **LMS Integration**: Integration with Learning Management Systems
- **Email Notifications**: Automated email reminders
- **Calendar Integration**: Calendar event creation for due dates
- **Reporting**: Advanced reporting and analytics
- **API Access**: RESTful API for external integrations 