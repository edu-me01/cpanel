# Quick Start Guide - Task Management System

## 🚀 Get Started in 5 Minutes

### 1. Start the Server
```bash
cd server
npm start
```

### 2. Access the Application
Open your browser and go to: **http://localhost:5000**

### 3. Login Credentials

#### Admin Access
- **Email**: `admin@school.com`
- **Password**: `admin123`
- **Dashboard**: http://localhost:5000/Admin-dashboard.html

#### Student Access
- **Email**: `student@school.com`
- **Password**: `student123`
- **Dashboard**: http://localhost:5000/student-dashboard.html

## 📋 Quick Tasks

### For Administrators

1. **Create a Task**
   - Click "Add Task" button
   - Fill in title, description, priority, due date
   - Click "Create Task"

2. **Manage Tasks**
   - View all tasks in the Tasks section
   - Edit task details using the edit button
   - Delete tasks using the delete button
   - Filter and search tasks

3. **Monitor Progress**
   - Check dashboard statistics
   - View task completion status
   - Monitor student activity

### For Students

1. **View Tasks**
   - Login with student credentials
   - See all assigned tasks
   - Check due dates and priorities

2. **Complete Tasks**
   - Click "Mark Complete" on finished tasks
   - Track your progress
   - View task details

## 🔧 Troubleshooting

### Server Won't Start
```bash
# Check if port 5000 is in use
netstat -ano | findstr :5000

# Kill process if needed
taskkill /PID <PID> /F
```

### Can't Login
- Clear browser cache and cookies
- Check server is running on port 5000
- Verify credentials are correct

### Tasks Not Loading
- Check browser console for errors
- Verify WebSocket connection
- Refresh the page

## 📞 Support

- **Test API**: http://localhost:5000/test-api.html
- **Server Health**: http://localhost:5000/health
- **Documentation**: README.md

---

**Happy Task Managing! 🎉** 