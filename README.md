# Task Management System

A comprehensive school task management system with real-time updates, built with Node.js, Express, WebSocket, and vanilla JavaScript.

## Features

### 🔐 Authentication & Authorization
- **Admin Login**: `admin@school.com` / `admin123`
- **Student Login**: `student@school.com` / `student123`
- Role-based access control (Admin/Student)
- Session management with tokens
- Automatic redirects based on user type

### 📋 Task Management
- **Create Tasks**: Admins can create tasks with title, description, priority, due date/time
- **Edit Tasks**: Update task details and status
- **Delete Tasks**: Remove tasks (admin only)
- **Complete Tasks**: Students can mark tasks as completed
- **Real-time Updates**: WebSocket integration for live updates
- **Filtering & Search**: Search by title/description, filter by status/priority
- **Sorting**: Sort by due date, title, priority, status

### 📊 Dashboard Features
- **Admin Dashboard**: Complete task management, student overview, analytics
- **Student Dashboard**: View assigned tasks, mark completion, track progress
- **Real-time Statistics**: Live updates of task counts and status
- **Modern UI**: Responsive design with Bootstrap 5

### 🔄 Real-time Features
- WebSocket connection for live updates
- Automatic data synchronization
- Real-time notifications
- Live dashboard updates

## Tech Stack

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Modern styling with custom themes
- **JavaScript (ES6+)** - Vanilla JS with modern features
- **Bootstrap 5** - Responsive UI framework
- **Font Awesome** - Icons
- **Chart.js** - Analytics and charts

### Backend
- **Node.js** - Runtime environment
- **Express.js** - Web framework
- **WebSocket (ws)** - Real-time communication
- **CORS** - Cross-origin resource sharing
- **UUID** - Unique identifier generation

### Data Storage
- **In-Memory Storage** - For development/demo
- **Map Data Structures** - Efficient data management
- **Session Storage** - Client-side session management

## Installation & Setup

### Prerequisites
- Node.js (v14 or higher)
- npm or yarn

### 1. Clone the Repository
```bash
git clone <repository-url>
cd cpanel
```

### 2. Install Dependencies
```bash
# Install server dependencies
cd server
npm install

# Install client dependencies (if any)
cd ..
npm install
```

### 3. Start the Server
```bash
# From the server directory
cd server
npm start

# Or using the provided batch file (Windows)
start.bat
```

### 4. Access the Application
- **Admin Dashboard**: http://localhost:5000/Admin-dashboard.html
- **Student Dashboard**: http://localhost:5000/student-dashboard.html
- **Main Page**: http://localhost:5000/index.html

## API Documentation

### Authentication Endpoints

#### POST /login
Authenticate user and get access token.

**Request Body:**
```json
{
  "email": "admin@school.com",
  "password": "admin123",
  "userType": "admin"
}
```

**Response:**
```json
{
  "success": true,
  "token": "generated_token",
  "userType": "admin"
}
```

### Task Management Endpoints

#### GET /api/tasks
Get all tasks (with optional student filtering).

**Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `studentId` (optional): Filter tasks for specific student

**Response:**
```json
[
  {
    "id": "task_1234567890_abc123",
    "title": "Complete Math Assignment",
    "description": "Solve all problems in Chapter 5",
    "priority": "high",
    "dueDate": "2024-12-15",
    "dueTime": "23:59",
    "status": "pending",
    "createdBy": "admin",
    "createdByName": "Admin",
    "createdAt": "2024-12-10T10:00:00.000Z",
    "assignedTo": null
  }
]
```

#### POST /api/tasks
Create a new task (Admin only).

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "New Task",
  "description": "Task description",
  "priority": "medium",
  "dueDate": "2024-12-20",
  "dueTime": "14:00",
  "createdBy": "admin",
  "createdByName": "Admin"
}
```

#### PUT /api/tasks/:taskId
Update an existing task (Admin only).

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Updated Task",
  "description": "Updated description",
  "priority": "high",
  "dueDate": "2024-12-25",
  "dueTime": "16:00",
  "status": "in-progress"
}
```

#### DELETE /api/tasks/:taskId
Delete a task (Admin only).

**Headers:**
```
Authorization: Bearer <token>
```

#### PUT /api/tasks/:taskId/complete
Mark task as completed (Students).

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "completedBy": "student_id"
}
```

## WebSocket Events

### Client to Server
- `task_created` - Notify about new task creation
- `task_updated` - Notify about task updates
- `task_deleted` - Notify about task deletion
- `sync_request` - Request data synchronization

### Server to Client
- `task_created` - New task created
- `task_updated` - Task updated
- `task_deleted` - Task deleted
- `sync_response` - Data synchronization response
- `error` - Error messages

## File Structure

```
cpanel/
├── server/
│   ├── websocket-server.js    # Main server file
│   ├── package.json           # Server dependencies
│   └── start.bat             # Windows startup script
├── js/
│   ├── auth.js               # Authentication module
│   ├── tasks.js              # Task management
│   ├── config.js             # Configuration
│   ├── main.js               # Main application logic
│   └── ...                   # Other modules
├── css/
│   └── style.css             # Custom styles
├── Admin-dashboard.html      # Admin interface
├── student-dashboard.html    # Student interface
├── index.html               # Main page
└── README.md                # This file
```

## Usage Guide

### For Administrators

1. **Login**: Use admin credentials (`admin@school.com` / `admin123`)
2. **Create Tasks**: Click "Add Task" button and fill in the form
3. **Manage Tasks**: Edit, delete, or view task details
4. **Monitor Progress**: View task completion status and student progress
5. **Analytics**: Check dashboard statistics and reports

### For Students

1. **Login**: Use student credentials (`student@school.com` / `student123`)
2. **View Tasks**: See all assigned tasks in the dashboard
3. **Complete Tasks**: Mark tasks as completed when finished
4. **Track Progress**: Monitor your task completion status

## Configuration

### Server Configuration
Edit `js/config.js` to modify:
- Server host and port
- API endpoints
- WebSocket settings
- Authentication settings
- UI preferences

### Environment Variables
Create a `.env` file in the server directory:
```env
PORT=5000
NODE_ENV=development
```

## Development

### Adding New Features

1. **Backend API**: Add new endpoints in `websocket-server.js`
2. **Frontend Logic**: Create new JavaScript modules in `js/` directory
3. **UI Components**: Add HTML/CSS for new interface elements
4. **WebSocket Events**: Implement real-time updates for new features

### Database Integration

To integrate with a real database (MongoDB, PostgreSQL, etc.):

1. Install database driver: `npm install mongoose` (for MongoDB)
2. Replace in-memory storage with database operations
3. Update API endpoints to use database queries
4. Add data validation and error handling

### Production Deployment

1. **Environment Setup**: Configure production environment variables
2. **Database**: Set up production database
3. **Security**: Implement proper JWT authentication
4. **HTTPS**: Configure SSL certificates
5. **Monitoring**: Add logging and error tracking

## Troubleshooting

### Common Issues

1. **Port Already in Use**
   ```bash
   # Kill process using port 5000
   netstat -ano | findstr :5000
   taskkill /PID <PID> /F
   ```

2. **WebSocket Connection Failed**
   - Check if server is running
   - Verify WebSocket URL in config
   - Check firewall settings

3. **Authentication Errors**
   - Clear browser session storage
   - Verify login credentials
   - Check server logs for errors

### Debug Mode

Enable debug logging by setting `NODE_ENV=development` in your environment.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue in the repository
- Check the troubleshooting section
- Review the API documentation

---

**Note**: This is a demo application. For production use, implement proper security measures, database integration, and error handling. 