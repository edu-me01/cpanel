# PHP API for Training Control Panel

This is a PHP-based REST API that replaces the Node.js backend for the Training Control Panel application.

## Features

- **Authentication**: JWT-based authentication for admin and student users
- **Task Management**: Create, read, update, delete tasks with role-based access
- **Student Management**: Manage student profiles and information
- **Attendance Tracking**: Record and manage student attendance
- **Submission Management**: Handle task submissions and grading
- **File-based Storage**: Simple JSON file storage (no database required)

## Setup

### Requirements

- PHP 7.4 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

### Installation

1. **Place the API files** in your web server directory (e.g., `htdocs/training-cpanel/api/`)

2. **Set permissions** for the data directory:
   ```bash
   chmod 755 api/data/
   ```

3. **Configure your web server** to serve the API:
   - For Apache: Ensure mod_rewrite is enabled
   - For Nginx: Add appropriate rewrite rules

4. **Update frontend configuration** in `js/config.js`:
   ```javascript
   server: {
     host: 'localhost',
     port: 80, // or your web server port
     protocol: 'http'
   }
   ```

## API Endpoints

### Authentication

- `POST /api/login` - User login
- `GET /api/health` - Health check

### Tasks

- `GET /api/tasks` - Get all tasks (filtered by user role)
- `POST /api/tasks` - Create new task (admin only)
- `PUT /api/tasks/{id}` - Update task (admin only)
- `DELETE /api/tasks/{id}` - Delete task (admin only)
- `PUT /api/tasks/{id}/complete` - Mark task as complete

### Students

- `GET /api/students` - Get all students (admin only)
- `POST /api/students` - Create new student (admin only)
- `PUT /api/students/{id}` - Update student (admin only)
- `DELETE /api/students/{id}` - Delete student (admin only)

### Attendance

- `GET /api/attendance` - Get attendance records
- `POST /api/attendance` - Create attendance record (admin only)
- `PUT /api/attendance/{id}` - Update attendance record (admin only)
- `DELETE /api/attendance/{id}` - Delete attendance record (admin only)

### Submissions

- `GET /api/submissions` - Get submissions
- `POST /api/submissions` - Create submission
- `PUT /api/submissions/{id}` - Update submission
- `DELETE /api/submissions/{id}` - Delete submission

## Authentication

The API uses JWT tokens for authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your-jwt-token>
```

### Default Users

The system creates default users on first run:

- **Admin**: `admin@school.com` / `admin123`
- **Student**: `student@school.com` / `student123`

## Data Storage

Data is stored in JSON files in the `data/` directory:

- `users.json` - User accounts
- `tasks.json` - Task data
- `students.json` - Student information
- `attendance.json` - Attendance records
- `submissions.json` - Task submissions

## Security Features

- JWT token authentication
- Role-based access control
- Input validation and sanitization
- CORS headers for cross-origin requests
- Security headers (X-Content-Type-Options, X-Frame-Options, etc.)

## Error Handling

The API returns consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Internal server error" // Only in 500 errors
}
```

## Migration from Node.js

This PHP API is a direct replacement for the Node.js backend. Key changes:

1. **No WebSocket support** - Real-time updates use polling instead
2. **File-based storage** - No database required
3. **Simplified authentication** - JWT tokens instead of session-based
4. **RESTful endpoints** - Standard HTTP methods for all operations

## Troubleshooting

### Common Issues

1. **404 errors**: Ensure mod_rewrite is enabled and .htaccess is working
2. **Permission errors**: Check file permissions on the data directory
3. **CORS errors**: Verify the web server is properly configured
4. **Authentication failures**: Check that JWT tokens are being sent correctly

### Debug Mode

To enable debug logging, modify `config/database.php` and add error logging.

## License

This API is part of the Training Control Panel project. 