# 📋 Task Management System Guide

## Overview
This guide explains the new task management system that allows admins to create, edit, and delete tasks, while students can view and complete tasks assigned to them.

## 🚀 Features

### Admin Features
- ✅ Create new tasks with title, description, priority, and due date
- ✅ Edit existing tasks
- ✅ Delete tasks
- ✅ View all tasks with filtering and search
- ✅ Real-time task updates via WebSocket
- ✅ Task statistics and overview

### Student Features
- ✅ View assigned tasks
- ✅ Mark tasks as completed
- ✅ View task details
- ✅ Real-time task notifications
- ✅ Task progress tracking

## 🔧 Setup Instructions

### 1. Start the Server
```bash
cd server
npm install
npm start
```

The server will run on `http://localhost:8080`

### 2. Access the Application
- **Admin Dashboard:** `http://localhost:8080/Admin-dashboard.html`
- **Student Dashboard:** `http://localhost:8080/student-dashboard.html`
- **Login Page:** `http://localhost:8080/index.html`

## 📝 How to Use

### For Admins

#### Creating a Task
1. **Login as Admin:**
   - Email: `admin@school.com`
   - Password: `admin123`

2. **Navigate to Tasks Section:**
   - Click on "Tasks" in the sidebar
   - Or use the "Add Task" quick action button

3. **Fill the Task Form:**
   - **Title:** Required - The task name
   - **Description:** Optional - Detailed task description
   - **Priority:** High/Medium/Low
   - **Due Date:** Required - When the task is due
   - **Due Time:** Optional - Specific time

4. **Submit:**
   - Click "Create Task"
   - The task will appear immediately in the task list
   - Students will see the task in real-time

#### Editing a Task
1. **Find the Task:**
   - Use search or filters to locate the task
   - Click the "Edit" button (pencil icon)

2. **Modify Fields:**
   - Update any task details
   - Change status if needed

3. **Save Changes:**
   - Click "Update Task"
   - Changes are applied immediately

#### Deleting a Task
1. **Find the Task:**
   - Locate the task in the list
   - Click the "Delete" button (trash icon)

2. **Confirm Deletion:**
   - Confirm the action in the popup
   - Task is permanently removed

### For Students

#### Viewing Tasks
1. **Login as Student:**
   - Email: `student@school.com`
   - Password: `student123`

2. **Access Tasks:**
   - Click on "My Tasks" tab
   - View all assigned tasks

3. **Task Information:**
   - Task title and description
   - Due date and time
   - Priority level
   - Current status

#### Completing Tasks
1. **Find Active Task:**
   - Look for tasks with "Pending" status
   - Click the "Mark Complete" button (checkmark icon)

2. **Task is Completed:**
   - Status changes to "Completed"
   - Completion time is recorded
   - Task moves to completed section

#### Viewing Task Details
1. **Click Details:**
   - Click the "View Details" button (eye icon)
   - Modal shows complete task information

2. **Information Displayed:**
   - Full description
   - Creation and due dates
   - Priority and status
   - Creator information

## 🔌 API Endpoints

### Authentication
```http
POST /login
Content-Type: application/json

{
  "email": "admin@school.com",
  "password": "admin123",
  "userType": "admin"
}
```

### Task Management

#### Get All Tasks
```http
GET /api/tasks
Authorization: Bearer <token>
```

#### Get Tasks for Specific Student
```http
GET /api/tasks?studentId=student_001
Authorization: Bearer <token>
```

#### Create New Task (Admin Only)
```http
POST /api/tasks
Authorization: Bearer <token>
Content-Type: application/json

{
  "title": "Complete Assignment",
  "description": "Finish the math homework",
  "priority": "high",
  "dueDate": "2024-01-15",
  "dueTime": "23:59",
  "createdBy": "admin_001",
  "createdByName": "Administrator"
}
```

#### Update Task (Admin Only)
```http
PUT /api/tasks/:taskId
Authorization: Bearer <token>
Content-Type: application/json

{
  "title": "Updated Task Title",
  "description": "Updated description",
  "priority": "medium",
  "dueDate": "2024-01-20",
  "status": "in-progress"
}
```

#### Delete Task (Admin Only)
```http
DELETE /api/tasks/:taskId
Authorization: Bearer <token>
```

#### Mark Task as Complete
```http
PUT /api/tasks/:taskId/complete
Authorization: Bearer <token>
Content-Type: application/json

{
  "completedBy": "student_001"
}
```

## 🔄 Real-Time Updates

The system uses WebSocket connections for real-time updates:

### WebSocket Events
- `task_created` - New task created
- `task_updated` - Task modified
- `task_deleted` - Task removed
- `connection_established` - Client connected

### Automatic Updates
- Tasks appear immediately when created
- Changes are reflected in real-time
- No page refresh required
- Works across multiple browser tabs

## 📊 Task Statistics

### Admin Dashboard
- **Total Tasks:** All tasks in the system
- **Active Tasks:** Pending and in-progress tasks
- **Completed Tasks:** Finished tasks
- **Overdue Tasks:** Tasks past due date

### Student Dashboard
- **Completed Tasks:** Tasks marked as done
- **Total Tasks:** All assigned tasks
- **Upcoming Tasks:** Pending tasks due soon

## 🎨 Task Priority Levels

- **🔴 High:** Urgent tasks requiring immediate attention
- **🟡 Medium:** Standard priority tasks
- **🟢 Low:** Non-urgent tasks

## 📅 Task Status

- **⏳ Pending:** Task created, not started
- **🔄 In Progress:** Task being worked on
- **✅ Completed:** Task finished
- **⚠️ Overdue:** Task past due date

## 🔍 Search and Filtering

### Admin Features
- **Search:** Find tasks by title or description
- **Status Filter:** All/Pending/In Progress/Completed
- **Priority Filter:** All/High/Medium/Low
- **Sort by:** Due date (earliest first)

### Student Features
- **Search:** Find specific tasks
- **Status View:** See task completion status
- **Due Date:** Track upcoming deadlines

## 🛠️ Technical Details

### Frontend Technologies
- **HTML5:** Structure and forms
- **CSS3:** Styling and responsive design
- **JavaScript (ES6+):** Interactivity and API calls
- **Bootstrap 5:** UI components and layout
- **Font Awesome:** Icons

### Backend Technologies
- **Node.js:** Runtime environment
- **Express.js:** Web framework
- **WebSocket (ws):** Real-time communication
- **CORS:** Cross-origin resource sharing

### Data Storage
- **In-Memory Storage:** For development/demo
- **Local Storage:** Client-side fallback
- **Session Storage:** User authentication

## 🚨 Error Handling

### Common Issues
1. **"Task title is required"** - Fill in the task title
2. **"Due date is required"** - Select a due date
3. **"Admin access required"** - Login as admin
4. **"Task not found"** - Task may have been deleted

### Offline Mode
- System works without server connection
- Data stored in browser localStorage
- Syncs when connection restored
- Shows "Offline mode" notifications

## 🔐 Security Features

- **Token-based Authentication:** Secure API access
- **Role-based Access:** Admin vs Student permissions
- **Input Validation:** Prevents invalid data
- **XSS Protection:** HTML escaping
- **CSRF Protection:** Token validation

## 📱 Responsive Design

- **Desktop:** Full feature access
- **Tablet:** Optimized layout
- **Mobile:** Touch-friendly interface
- **Cross-browser:** Works on all modern browsers

## 🎯 Best Practices

### For Admins
- Use clear, descriptive task titles
- Set realistic due dates
- Provide detailed descriptions
- Use appropriate priority levels
- Review and update tasks regularly

### For Students
- Check tasks regularly
- Mark tasks complete when done
- Contact admin for clarification
- Plan ahead for due dates
- Use task details for full information

## 🔄 Future Enhancements

- **Task Assignment:** Assign tasks to specific students
- **File Attachments:** Upload documents to tasks
- **Comments:** Add notes and feedback
- **Notifications:** Email/SMS reminders
- **Calendar Integration:** Sync with external calendars
- **Progress Tracking:** Track partial completion
- **Task Templates:** Reusable task formats
- **Bulk Operations:** Manage multiple tasks at once

## 📞 Support

For technical support or questions:
- Check the browser console for errors
- Verify server is running on port 8080
- Ensure proper authentication
- Clear browser cache if needed
- Check network connectivity

---

**Happy Task Managing! 🎉** 