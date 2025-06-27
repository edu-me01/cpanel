const WebSocket = require("ws");
const http = require("http");
const express = require("express");
const cors = require("cors");
const path = require("path");
const { v4: uuidv4 } = require("uuid");
require("dotenv").config();

// Create Express app
const app = express();

// Enable CORS
app.use(cors());

// Parse JSON bodies
app.use(express.json());

// Serve static files from the parent directory
app.use(express.static(path.join(__dirname, "..")));

// Create HTTP server
const server = http.createServer(app);

// Create WebSocket server
const wss = new WebSocket.Server({ server });

// Store connected clients
const clients = new Map();

// Store data
const students = new Map();
const tasks = new Map();
const attendance = new Map();
const submissions = new Map();

// Handle WebSocket connections
wss.on("connection", (ws) => {
  const clientId = uuidv4();
  clients.set(clientId, ws);

  console.log(`Client connected: ${clientId}`);

  // Send initial data
  ws.send(
    JSON.stringify({
      type: "init",
      data: {
        students: Array.from(students.values()),
        tasks: Array.from(tasks.values()),
        attendance: Array.from(attendance.values()),
        submissions: Array.from(submissions.values()),
      },
    })
  );

  // Handle messages
  ws.on("message", (message) => {
    try {
      const data = JSON.parse(message);
      handleMessage(clientId, data);
    } catch (error) {
      console.error("Error handling message:", error);
      sendError(ws, "Invalid message format");
    }
  });

  // Handle disconnection
  ws.on("close", () => {
    console.log(`Client disconnected: ${clientId}`);
    clients.delete(clientId);
  });

  // Handle errors
  ws.on("error", (error) => {
    console.error(`WebSocket error for client ${clientId}:`, error);
    clients.delete(clientId);
  });
});

// Add this near the health check endpoint
app.post("/login", (req, res) => {
  const { email, password, userType } = req.body;

  // Simple validation - replace with your actual authentication logic
  if (
    userType === "student" &&
    email === "student@school.com" &&
    password === "student123"
  ) {
    // In a real app, generate a proper token (JWT)
    const token = uuidv4();
    res.json({ success: true, token, userType });
  } else if (
    userType === "admin" &&
    email === "admin@school.com" &&
    password === "admin123"
  ) {
    const token = uuidv4();
    res.json({ success: true, token, userType });
  } else {
    res.status(401).json({ success: false, message: "Invalid credentials" });
  }
});

// Authentication middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ message: 'Access token required' });
  }

  // Simple token validation (in production, use JWT)
  if (token.includes('admin') || token.includes('student')) {
    req.user = { 
      id: token.includes('admin') ? 'admin' : 'student',
      type: token.includes('admin') ? 'admin' : 'student'
    };
    next();
  } else {
    return res.status(403).json({ message: 'Invalid token' });
  }
};

// Admin-only middleware
const requireAdmin = (req, res, next) => {
  if (req.user.type !== 'admin') {
    return res.status(403).json({ message: 'Admin access required' });
  }
  next();
};

// Task Management API
app.get('/api/tasks', authenticateToken, (req, res) => {
  try {
    const studentId = req.query.studentId;
    let filteredTasks = Array.from(tasks.values());

    // If studentId is provided, filter tasks for that student
    if (studentId) {
      filteredTasks = filteredTasks.filter(task => 
        !task.assignedTo || task.assignedTo === studentId
      );
    }

    res.json(filteredTasks);
  } catch (error) {
    res.status(500).json({ message: 'Error fetching tasks', error: error.message });
  }
});

app.post('/api/tasks', authenticateToken, requireAdmin, (req, res) => {
  try {
    const { title, description, priority, dueDate, dueTime, createdBy, createdByName } = req.body;

    // Validation
    if (!title || !dueDate) {
      return res.status(400).json({ message: 'Title and due date are required' });
    }

    const newTask = {
      id: 'task_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
      title: title.trim(),
      description: description ? description.trim() : '',
      priority: priority || 'medium',
      dueDate,
      dueTime: dueTime || null,
      createdBy,
      createdByName,
      status: 'pending',
      createdAt: new Date().toISOString(),
      assignedTo: null
    };

    tasks.set(newTask.id, newTask);

    // Broadcast to all connected clients
    broadcastUpdate({
      type: 'task_created',
      data: newTask
    });

    res.status(201).json(newTask);
  } catch (error) {
    res.status(500).json({ message: 'Error creating task', error: error.message });
  }
});

app.put('/api/tasks/:taskId', authenticateToken, requireAdmin, (req, res) => {
  try {
    const { taskId } = req.params;
    const { title, description, priority, dueDate, dueTime, status } = req.body;

    const existingTask = tasks.get(taskId);
    if (!existingTask) {
      return res.status(404).json({ message: 'Task not found' });
    }

    // Update task
    const updatedTask = {
      ...existingTask,
      title: title ? title.trim() : existingTask.title,
      description: description ? description.trim() : existingTask.description,
      priority: priority || existingTask.priority,
      dueDate: dueDate || existingTask.dueDate,
      dueTime: dueTime || existingTask.dueTime,
      status: status || existingTask.status,
      updatedAt: new Date().toISOString()
    };

    tasks.set(taskId, updatedTask);

    // Broadcast to all connected clients
    broadcastUpdate({
      type: 'task_updated',
      data: updatedTask
    });

    res.json(updatedTask);
  } catch (error) {
    res.status(500).json({ message: 'Error updating task', error: error.message });
  }
});

app.delete('/api/tasks/:taskId', authenticateToken, requireAdmin, (req, res) => {
  try {
    const { taskId } = req.params;

    const existingTask = tasks.get(taskId);
    if (!existingTask) {
      return res.status(404).json({ message: 'Task not found' });
    }

    tasks.delete(taskId);

    // Broadcast to all connected clients
    broadcastUpdate({
      type: 'task_deleted',
      data: existingTask
    });

    res.json({ message: 'Task deleted successfully' });
  } catch (error) {
    res.status(500).json({ message: 'Error deleting task', error: error.message });
  }
});

// Student task completion endpoint
app.put('/api/tasks/:taskId/complete', authenticateToken, (req, res) => {
  try {
    const { taskId } = req.params;
    const { completedBy } = req.body;

    const existingTask = tasks.get(taskId);
    if (!existingTask) {
      return res.status(404).json({ message: 'Task not found' });
    }

    // Update task status
    const updatedTask = {
      ...existingTask,
      status: 'completed',
      completedAt: new Date().toISOString(),
      completedBy
    };

    tasks.set(taskId, updatedTask);

    // Broadcast to all connected clients
    broadcastUpdate({
      type: 'task_updated',
      data: updatedTask
    });

    res.json(updatedTask);
  } catch (error) {
    res.status(500).json({ message: 'Error completing task', error: error.message });
  }
});

// Handle different message types
function handleMessage(clientId, data) {
  const ws = clients.get(clientId);
  if (!ws) return;

  switch (data.type) {
    case "student_created":
    case "student_updated":
    case "student_deleted":
      handleStudentMessage(data);
      break;

    case "task_created":
    case "task_updated":
    case "task_deleted":
      handleTaskMessage(data);
      break;

    case "attendance_created":
    case "attendance_updated":
    case "attendance_deleted":
      handleAttendanceMessage(data);
      break;

    case "submission_created":
    case "submission_updated":
    case "submission_deleted":
      handleSubmissionMessage(data);
      break;

    case "sync_request":
      handleSyncRequest(clientId, data);
      break;

    default:
      sendError(ws, "Unknown message type");
  }
}

// Handle student-related messages
function handleStudentMessage(data) {
  const student = data.data;

  switch (data.type) {
    case "student_created":
      students.set(student.id, student);
      break;
    case "student_updated":
      if (students.has(student.id)) {
        students.set(student.id, student);
      }
      break;
    case "student_deleted":
      students.delete(student.id);
      break;
  }

  broadcastUpdate(data);
}

// Handle task-related messages
function handleTaskMessage(data) {
  const task = data.data;

  switch (data.type) {
    case "task_created":
      tasks.set(task.id, task);
      break;
    case "task_updated":
      if (tasks.has(task.id)) {
        tasks.set(task.id, task);
      }
      break;
    case "task_deleted":
      tasks.delete(task.id);
      break;
  }

  broadcastUpdate(data);
}

// Handle attendance-related messages
function handleAttendanceMessage(data) {
  const record = data.data;

  switch (data.type) {
    case "attendance_created":
      attendance.set(record.id, record);
      break;
    case "attendance_updated":
      if (attendance.has(record.id)) {
        attendance.set(record.id, record);
      }
      break;
    case "attendance_deleted":
      attendance.delete(record.id);
      break;
  }

  broadcastUpdate(data);
}

// Handle submission-related messages
function handleSubmissionMessage(data) {
  const submission = data.data;

  switch (data.type) {
    case "submission_created":
      submissions.set(submission.id, submission);
      break;
    case "submission_updated":
      if (submissions.has(submission.id)) {
        submissions.set(submission.id, submission);
      }
      break;
    case "submission_deleted":
      submissions.delete(submission.id);
      break;
  }

  broadcastUpdate(data);
}

// Handle sync requests
function handleSyncRequest(clientId, data) {
  const ws = clients.get(clientId);
  if (!ws) return;

  ws.send(
    JSON.stringify({
      type: "sync_response",
      data: {
        students: Array.from(students.values()),
        tasks: Array.from(tasks.values()),
        attendance: Array.from(attendance.values()),
        submissions: Array.from(submissions.values()),
      },
    })
  );
}

// Broadcast updates to all clients
function broadcastUpdate(data) {
  const message = JSON.stringify(data);
  clients.forEach((client) => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(message);
    }
  });
}

// Send error message to client
function sendError(ws, message) {
  ws.send(
    JSON.stringify({
      type: "error",
      message,
    })
  );
}

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'OK',
    timestamp: new Date().toISOString(),
    connections: clients.size,
    data: {
      tasks: tasks.size,
      students: students.size,
      attendance: attendance.size,
      submissions: submissions.size
    }
  });
});

// Error handling middleware
app.use((error, req, res, next) => {
  console.error('Server error:', error);
  res.status(500).json({ message: 'Internal server error', error: error.message });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({ message: 'Endpoint not found' });
});

// Start server
const PORT = process.env.PORT || 5000;
server.listen(PORT, () => {
  console.log(`🚀 Task Manager WebSocket Server running on port ${PORT}`);
 
});
