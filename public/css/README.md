# CSS Files Organization

This directory contains the external CSS files for the DDD Learning Portal application. All inline styles have been extracted from the HTML files and organized into separate CSS files for better maintainability and performance.

## Files

### `login.css`
- Styles for the login/registration page (`login.html`)
- Includes form styling, tab navigation, and responsive design
- Key components: `.container`, `.form-group`, `.tabs`, `.tab`, `.btn`

### `dashboard.css`
- Styles for the user dashboard (`dashboard.html`)
- Includes user info display, exam assignments, and action buttons
- Key components: `.header`, `.user-info`, `.exam-assignments`, `.exam-card`, `.btn`

### `exam.css`
- Styles for the exam taking interface (`exam.html`)
- Includes exam layout, timer, questions, and progress tracking
- Key components: `.container`, `.header`, `.timer`, `.question`, `.option`, `.progress-bar`

### `admin.css`
- Styles for the admin portal (`admin.html`)
- Includes dashboard statistics, navigation tabs, forms, and data tables
- Key components: `.nav-tabs`, `.content`, `.stats-grid`, `.data-table`, `.form-group`

## Benefits of External CSS

1. **Maintainability**: Easier to update styles across the application
2. **Performance**: CSS files can be cached by browsers
3. **Reusability**: Styles can be shared between pages
4. **Separation of Concerns**: HTML structure is separated from styling
5. **File Size**: HTML files are smaller and cleaner

## Usage

Each HTML file now includes a link to its corresponding CSS file in the `<head>` section:

```html
<link rel="stylesheet" href="/css/[filename].css">
```

## Common Styles

Some common styles are shared across multiple pages:
- Button styles (`.btn`, `.btn-primary`, `.btn-success`, etc.)
- Form styles (`.form-group`, `input`, `textarea`)
- Status indicators (`.status-pending`, `.status-completed`, `.status-overdue`)
- Loading states (`.loading`)
- Utility classes (`.hidden`, `.alert`, `.alert-success`, `.alert-error`) 