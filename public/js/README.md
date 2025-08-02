# JavaScript Files Organization

This directory contains the external JavaScript files for the DDD Learning Portal application. All inline JavaScript has been extracted from the HTML files and organized into separate JavaScript files for better maintainability and performance.

## Files

### `login.js`
- JavaScript functionality for the login/registration page (`login.html`)
- Includes tab navigation, form handling, authentication, and user registration
- Key functions: `showTab()`, `showResult()`, form event listeners
- Handles login and registration API calls

### `dashboard.js`
- JavaScript functionality for the user dashboard (`dashboard.html`)
- Includes authentication checking, user info loading, and exam assignment management
- Key functions: `checkAuth()`, `loadUserInfo()`, `loadAssignments()`, `displayAssignments()`
- Manages exam assignment display and navigation to exam taking

### `exam.js`
- JavaScript functionality for the exam taking interface (`exam.html`)
- Includes exam loading, timer management, question handling, and submission
- Key functions: `startExam()`, `displayExam()`, `submitExam()`, `startTimer()`
- Handles exam progress tracking and assignment completion

### `admin.js`
- JavaScript functionality for the admin portal (`admin.html`)
- Includes dashboard statistics, CRUD operations for topics, questions, exams, and assignments
- Key functions: `loadDashboard()`, `loadTopics()`, `loadQuestions()`, `loadExams()`, `loadAssignments()`
- Manages all admin operations including creating, editing, and deleting content

## Benefits of External JavaScript

1. **Maintainability**: Easier to update functionality across the application
2. **Performance**: JavaScript files can be cached by browsers
3. **Reusability**: Functions can be shared between pages
4. **Separation of Concerns**: HTML structure is separated from behavior
5. **File Size**: HTML files are smaller and cleaner
6. **Debugging**: JavaScript issues can be identified and fixed more easily

## Usage

Each HTML file now includes a script tag to its corresponding JavaScript file in the `<head>` or before the closing `</body>` tag:

```html
<script src="/js/[filename].js"></script>
```

## Common Patterns

Some common patterns used across the JavaScript files:
- **Event Listeners**: All forms and interactive elements use proper event listeners
- **Async/Await**: API calls use modern async/await syntax for better error handling
- **Error Handling**: Comprehensive try-catch blocks for API calls
- **DOM Manipulation**: Consistent use of `document.getElementById()` and `querySelector()`
- **Local Storage**: JWT tokens are stored and retrieved from localStorage
- **Form Validation**: Client-side validation before API calls

## API Integration

All JavaScript files integrate with the backend API endpoints:
- Authentication: `/auth/login`, `/auth/register`
- Dashboard: `/exam-assignments/user/{userId}`
- Exam: `/api/learning/start-exam-attempt`, `/api/learning/submit-exam-attempt`
- Admin: `/topics`, `/questions`, `/exams`, `/exam-assignments`, `/users`

## Browser Compatibility

The JavaScript code uses modern ES6+ features:
- Arrow functions
- Template literals
- Async/await
- Fetch API
- LocalStorage
- ClassList API

For older browser support, consider using a transpiler like Babel. 