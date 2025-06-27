# Attendance Token & Lecture System Guide

## Overview

This system implements a comprehensive attendance and lecture management system with token-based access control, ensuring students must attend before accessing lectures and complete feedback forms.

## Key Features

### 1. Attendance Token System
- **Daily Token Generation**: Admins generate unique tokens for each day
- **One-Time Use**: Each student can only use a token once per day
- **Automatic Expiration**: Tokens expire at midnight
- **Token Validation**: Secure token validation with usage tracking

### 2. Lecture Access Control
- **Attendance Required**: Students must attend before accessing lectures
- **Date-Based Access**: Students can only see lectures from today and previous days
- **Role-Based Permissions**: Admins have full access, students have restricted access

### 3. Feedback System
- **Daily Feedback**: Students must complete daily feedback before accessing lectures
- **Final Feedback**: Required when training is finished
- **Automatic Prompts**: System automatically shows feedback forms when required

## System Flow

### For Administrators

1. **Start Daily Session**:
   - Go to Admin Dashboard → Attendance section
   - Click "Generate Token" to create a new attendance token
   - Share the token with students

2. **Monitor Attendance**:
   - View real-time attendance status
   - See how many students have used the token
   - Track attendance records

3. **Finish Session**:
   - Click "Finish Attendance" when session is complete
   - This prevents further attendance marking
   - Students can still access lectures they've already unlocked

4. **Manage Lectures**:
   - Create lectures with categories, content, and dates
   - Students can only access lectures after attending
   - Lectures are filtered by date and category

### For Students

1. **Mark Attendance**:
   - Go to attendance.html or use the attendance tab
   - Enter the token provided by the instructor
   - Token is validated and marked as used
   - Attendance record is created

2. **Complete Feedback** (if required):
   - System checks if daily feedback is needed
   - If required, feedback form appears automatically
   - Must complete feedback before accessing lectures

3. **Access Lectures**:
   - After attending and completing feedback, lectures become available
   - Can view lectures from today and previous days
   - Lectures are organized by category and date

4. **Training Completion**:
   - When training ends, final feedback is required
   - After submitting final feedback, redirected to completion page
   - Can still access dashboard for review

## API Endpoints

### Attendance Token Management

```
POST /api/attendance-token/generate
- Generate new attendance token for today
- Admin only

POST /api/attendance-token/finish
- Finish attendance session for today
- Admin only

GET /api/attendance-token/status
- Get current token status
- Public access

POST /api/attendance-token/validate
- Validate attendance token
- Public access

POST /api/attendance-token/use
- Mark token as used by student
- Public access
```

### Lecture Management

```
GET /api/lectures
- Get lectures (filtered by attendance for students)
- Authenticated access

POST /api/lectures
- Create new lecture
- Admin only

GET /api/lectures/{id}
- Get specific lecture
- Authenticated access

PUT /api/lectures/{id}
- Update lecture
- Admin only

DELETE /api/lectures/{id}
- Delete lecture
- Admin only

GET /api/lectures/today
- Get today's lectures
- Authenticated access

GET /api/lectures/yesterday
- Get yesterday's lectures
- Authenticated access

GET /api/lectures/categories
- Get lecture categories
- Authenticated access
```

### Feedback Management

```
GET /api/feedbacks
- Get feedback records
- Authenticated access

POST /api/feedbacks
- Submit feedback
- Student only

GET /api/feedbacks/questions?type={type}
- Get feedback questions
- Authenticated access

GET /api/feedbacks/check-daily
- Check if daily feedback is required
- Student only

GET /api/feedbacks/check-final
- Check if final feedback is required
- Student only
```

## Database Structure

### Attendance Tokens
```json
{
  "id": "attendance_token_1",
  "token": "att_abc123def456",
  "date": "2024-12-15",
  "status": "active",
  "createdBy": "admin_1",
  "createdAt": "2024-12-15T09:00:00Z",
  "expiresAt": "2024-12-16T00:00:00Z",
  "usedBy": ["student_1", "student_2"]
}
```

### Lectures
```json
{
  "id": "lecture_1",
  "title": "Introduction to Web Development",
  "content": "Lecture content...",
  "category": "web-development",
  "date": "2024-12-15",
  "duration": 60,
  "videoUrl": null,
  "attachments": [],
  "createdBy": "admin_1",
  "createdAt": "2024-12-15T09:00:00Z",
  "status": "active"
}
```

### Feedback
```json
{
  "id": "feedback_1",
  "studentId": "student_1",
  "studentName": "John Doe",
  "type": "daily",
  "date": "2024-12-15",
  "answers": {
    "understanding": "4",
    "difficulty": "3",
    "engagement": "5",
    "questions": "None",
    "suggestions": "Great session!"
  },
  "submittedAt": "2024-12-15T10:30:00Z",
  "status": "submitted"
}
```

## Frontend Components

### Admin Dashboard
- **Attendance Token Controls**: Generate, monitor, and finish tokens
- **Token Display**: Show current token with copy functionality
- **Status Indicators**: Real-time attendance status
- **Lecture Management**: Create and manage lectures

### Student Dashboard
- **Lectures Tab**: Access lectures after attending
- **Attendance Tab**: Mark attendance with tokens
- **Feedback Integration**: Automatic feedback prompts

### Attendance Page
- **Token Input**: Secure token validation
- **Student Information**: Auto-filled from session
- **Status Feedback**: Clear success/error messages

### Lectures Page
- **Access Control**: Redirects to attendance if not attended
- **Filtering**: By category and date
- **Lecture Viewer**: Modal-based lecture display

## Security Features

1. **Token Security**:
   - Cryptographically secure token generation
   - One-time use per student per day
   - Automatic expiration at midnight

2. **Access Control**:
   - JWT-based authentication
   - Role-based permissions
   - Attendance verification before lecture access

3. **Data Protection**:
   - Input validation and sanitization
   - CSRF protection
   - Secure cookie handling

## Usage Examples

### Starting a Training Session
1. Admin logs into dashboard
2. Navigates to Attendance section
3. Clicks "Generate Token"
4. Copies and shares token with students
5. Students use token to mark attendance
6. Admin monitors attendance in real-time

### Student Workflow
1. Student receives attendance token
2. Goes to attendance.html
3. Enters token and marks attendance
4. Completes daily feedback (if required)
5. Accesses lectures through dashboard
6. Completes final feedback when training ends

### Managing Lectures
1. Admin creates lectures with categories and dates
2. Students can only access lectures after attending
3. Lectures are filtered by date (today and previous)
4. Students can search and filter lectures

## Troubleshooting

### Common Issues

1. **Token Not Working**:
   - Check if token is expired (after midnight)
   - Verify token hasn't been used by this student
   - Ensure token is for today's date

2. **Can't Access Lectures**:
   - Verify student has attended today
   - Check if daily feedback is completed
   - Ensure lecture date is today or previous

3. **Feedback Not Showing**:
   - Check if student has attended
   - Verify feedback requirements
   - Clear browser cache and try again

### Error Messages

- `"Attendance token is no longer active"`: Token has been finished by admin
- `"Attendance token has expired"`: Token expired at midnight
- `"Student has already used this attendance token"`: Token already used
- `"You must attend today's session before accessing lectures"`: Attendance required
- `"You have already submitted feedback for today"`: Feedback already submitted

## Configuration

### Environment Variables
- `JWT_SECRET`: Secret key for JWT tokens
- `API_BASE_URL`: Base URL for API endpoints
- `SESSION_TIMEOUT`: Session timeout in minutes

### File Permissions
- Ensure `data/` directory is writable
- JSON files should be readable by web server
- Log files should be writable

## Maintenance

### Daily Tasks
- Monitor attendance token status
- Check for expired tokens
- Review feedback submissions

### Weekly Tasks
- Archive old attendance records
- Review lecture content
- Analyze feedback data

### Monthly Tasks
- Clean up old data
- Update lecture categories
- Review system performance

## Support

For technical support or questions about the attendance and lecture system, please refer to the API documentation or contact the development team. 