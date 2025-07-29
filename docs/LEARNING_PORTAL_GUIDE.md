# Learning Portal Guide

## Overview

The Learning Portal is a comprehensive educational platform built on top of the existing DDD application. It provides functionality for administrators to create and manage learning topics and exams, while allowing users to take practice tests.

## Features

### 🎯 Core Features

- **Topic Management**: Create and organize learning topics by difficulty level
- **Exam Creation**: Build comprehensive exams with multiple question types
- **User Role System**: Admin and Super Admin roles for content management
- **Exam Attempt Tracking**: Monitor user progress and performance
- **Learning Statistics**: Comprehensive analytics and reporting

### 📚 Topic System

Topics are organized by difficulty levels:
- **Beginner**: Basic concepts and fundamentals
- **Intermediate**: Advanced concepts and practical applications
- **Advanced**: Complex topics and specialized knowledge
- **Expert**: Master-level content and advanced techniques

### 📝 Exam System

Exams include:
- **Multiple Choice Questions**: Standard multiple choice with single correct answer
- **True/False Questions**: Simple true or false questions
- **Single Choice Questions**: Questions with one correct option
- **Configurable Duration**: Set time limits for exams
- **Passing Score Requirements**: Define minimum scores to pass
- **Point System**: Assign different point values to questions

## Database Schema

### Tables

1. **users** (enhanced with role column)
   - `id` (UUID)
   - `name` (VARCHAR)
   - `email` (VARCHAR)
   - `password_hash` (VARCHAR)
   - `role` (VARCHAR) - 'user', 'admin', 'superadmin'
   - `is_active` (BOOLEAN)
   - `created_at` (DATETIME)
   - `updated_at` (DATETIME)

2. **topics**
   - `id` (UUID)
   - `title` (VARCHAR)
   - `description` (TEXT)
   - `level` (ENUM: 'beginner', 'intermediate', 'advanced', 'expert')
   - `is_active` (BOOLEAN)
   - `created_at` (DATETIME)
   - `updated_at` (DATETIME)
   - `deleted_at` (DATETIME)

3. **exams**
   - `id` (UUID)
   - `title` (VARCHAR)
   - `description` (TEXT)
   - `duration_minutes` (INT)
   - `passing_score_percentage` (INT)
   - `topic_id` (UUID, FK to topics)
   - `is_active` (BOOLEAN)
   - `created_at` (DATETIME)
   - `updated_at` (DATETIME)
   - `deleted_at` (DATETIME)

4. **questions**
   - `id` (UUID)
   - `text` (TEXT)
   - `type` (ENUM: 'multiple_choice', 'true_false', 'single_choice')
   - `exam_id` (UUID, FK to exams)
   - `options` (JSON)
   - `correct_option` (INT)
   - `points` (INT)
   - `is_active` (BOOLEAN)
   - `created_at` (DATETIME)
   - `updated_at` (DATETIME)
   - `deleted_at` (DATETIME)

5. **exam_attempts**
   - `id` (UUID)
   - `user_id` (UUID, FK to users)
   - `exam_id` (UUID, FK to exams)
   - `score` (INT)
   - `passed` (BOOLEAN)
   - `started_at` (DATETIME)
   - `completed_at` (DATETIME)
   - `deleted_at` (DATETIME)

## API Endpoints

### Authentication Required (All endpoints)

All learning portal endpoints require authentication. Users must be logged in to access any functionality.

### Topic Management

#### List Topics
```
GET /topics
```
Returns all active topics with their details.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "title": "PHP Fundamentals",
      "description": "Learn the basics of PHP...",
      "level": "beginner",
      "level_display": "Beginner",
      "is_active": true,
      "created_at": "2024-01-01 10:00:00"
    }
  ]
}
```

#### Create Topic (Admin Only)
```
POST /topics
Content-Type: application/json

{
  "title": "New Topic",
  "description": "Topic description",
  "level": "beginner"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Topic created successfully",
  "data": {
    "id": "uuid",
    "title": "New Topic",
    "description": "Topic description",
    "level": "beginner",
    "level_display": "Beginner"
  }
}
```

### Exam Management

#### List Exams
```
GET /exams
```
Returns all active exams with their details.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "title": "PHP Basics Quiz",
      "description": "Test your knowledge...",
      "duration_minutes": 30,
      "duration_display": "30 minutes",
      "passing_score_percentage": 70,
      "passing_score_display": "70%",
      "topic_id": "uuid",
      "is_active": true,
      "created_at": "2024-01-01 10:00:00"
    }
  ]
}
```

#### Create Exam (Admin Only)
```
POST /exams
Content-Type: application/json

{
  "title": "New Exam",
  "description": "Exam description",
  "duration_minutes": 45,
  "passing_score_percentage": 75,
  "topic_id": "topic-uuid"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Exam created successfully",
  "data": {
    "id": "uuid",
    "title": "New Exam",
    "description": "Exam description",
    "duration_minutes": 45,
    "duration_display": "45 minutes",
    "passing_score_percentage": 75,
    "passing_score_display": "75%",
    "topic_id": "topic-uuid"
  }
}
```

### Learning Statistics

#### Get Learning Stats
```
GET /learning/stats
```
Returns comprehensive learning statistics.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_topics": 8,
    "total_exams": 6,
    "total_users": 25,
    "topics_by_level": {
      "beginner": 1,
      "intermediate": 2,
      "advanced": 3,
      "expert": 2
    }
  }
}
```

## Setup Instructions

### 1. Run Database Migrations

Execute the migration files in order:

```bash
# Add role column to users table
mysql -u your_user -p your_database < database/migrations/002_add_role_to_users_table.sql

# Create topics table
mysql -u your_user -p your_database < database/migrations/003_create_topics_table.sql

# Create exams table
mysql -u your_user -p your_database < database/migrations/004_create_exams_table.sql

# Create questions table
mysql -u your_user -p your_database < database/migrations/005_create_questions_table.sql

# Create exam_attempts table
mysql -u your_user -p your_database < database/migrations/006_create_exam_attempts_table.sql
```

### 2. Create Admin User

```bash
php scripts/create-admin-user.php
```

Follow the prompts to create an admin user with appropriate credentials.

### 3. Test the System

```bash
# Test the learning portal structure
php scripts/test-learning-portal.php

# Seed with sample data
php scripts/seed-learning-data.php
```

### 4. Verify Installation

1. Start the application server
2. Log in with your admin credentials
3. Test the API endpoints:
   - `GET /topics` - Should return empty array or seeded topics
   - `GET /exams` - Should return empty array or seeded exams
   - `GET /learning/stats` - Should return statistics

## Usage Examples

### Creating a Topic

```bash
curl -X POST http://localhost/topics \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "title": "Advanced PHP Patterns",
    "description": "Learn advanced design patterns in PHP",
    "level": "advanced"
  }'
```

### Creating an Exam

```bash
curl -X POST http://localhost/exams \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "title": "Design Patterns Test",
    "description": "Test your knowledge of PHP design patterns",
    "duration_minutes": 60,
    "passing_score_percentage": 80,
    "topic_id": "TOPIC_UUID_HERE"
  }'
```

### Viewing Learning Statistics

```bash
curl -X GET http://localhost/learning/stats \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Security Considerations

### Role-Based Access Control

- **User Role**: Can view topics and take exams
- **Admin Role**: Can create and manage topics and exams
- **Super Admin Role**: Full system access including user management

### Authentication

- All endpoints require valid JWT authentication
- Tokens expire after 1 hour
- Session-based authentication for web interface

### Data Validation

- All input is validated through Value Objects
- SQL injection protection through prepared statements
- XSS protection through proper output encoding

## Future Enhancements

### Planned Features

1. **Question Management Interface**: Web interface for creating and editing questions
2. **Exam Taking Interface**: User-friendly exam interface with timer
3. **Progress Tracking**: Detailed user progress and performance analytics
4. **Certificate System**: Generate certificates for completed exams
5. **Bulk Import**: Import topics and exams from CSV/Excel files
6. **Advanced Analytics**: Detailed reporting and insights
7. **Mobile Interface**: Responsive design for mobile devices

### Technical Improvements

1. **Caching**: Implement Redis caching for better performance
2. **File Uploads**: Support for image and document uploads
3. **Real-time Updates**: WebSocket integration for live updates
4. **API Versioning**: Versioned API endpoints
5. **Rate Limiting**: Implement API rate limiting
6. **Audit Logging**: Comprehensive audit trail

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Verify database credentials in `.env`
   - Ensure database server is running
   - Check network connectivity

2. **Permission Errors**
   - Verify user has admin role
   - Check JWT token validity
   - Ensure proper authentication headers

3. **Validation Errors**
   - Check input format and required fields
   - Verify enum values (level, question type, etc.)
   - Ensure proper JSON formatting

### Debug Mode

Enable debug mode by setting environment variable:
```bash
export APP_DEBUG=true
```

This will provide detailed error messages and stack traces.

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review the API documentation
3. Check the application logs
4. Contact the development team

---

**Note**: This learning portal is built on a solid DDD foundation with proper separation of concerns, making it easy to extend and maintain. 