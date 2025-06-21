const WebSocket = require('ws');
const http = require('http');
const express = require('express');
const cors = require('cors');
const path = require('path');
const { v4: uuidv4 } = require('uuid');
require('dotenv').config();

// Create Express app
const app = express();

// Enable CORS
app.use(cors());

// Parse JSON bodies
app.use(express.json());

// Serve static files from the parent directory
app.use(express.static(path.join(__dirname, '..')));

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
wss.on('connection', (ws) => {
    const clientId = uuidv4();
    clients.set(clientId, ws);

    console.log(`Client connected: ${clientId}`);

    // Send initial data
    ws.send(JSON.stringify({
        type: 'init',
        data: {
            students: Array.from(students.values()),
            tasks: Array.from(tasks.values()),
            attendance: Array.from(attendance.values()),
            submissions: Array.from(submissions.values())
        }
    }));

    // Handle messages
    ws.on('message', (message) => {
        try {
            const data = JSON.parse(message);
            handleMessage(clientId, data);
        } catch (error) {
            console.error('Error handling message:', error);
            sendError(ws, 'Invalid message format');
        }
    });

    // Handle disconnection
    ws.on('close', () => {
        console.log(`Client disconnected: ${clientId}`);
        clients.delete(clientId);
    });

    // Handle errors
    ws.on('error', (error) => {
        console.error(`WebSocket error for client ${clientId}:`, error);
        clients.delete(clientId);
    });
});

// Handle different message types
function handleMessage(clientId, data) {
    const ws = clients.get(clientId);
    if (!ws) return;

    switch (data.type) {
        case 'student_created':
        case 'student_updated':
        case 'student_deleted':
            handleStudentMessage(data);
            break;

        case 'task_created':
        case 'task_updated':
        case 'task_deleted':
            handleTaskMessage(data);
            break;

        case 'attendance_created':
        case 'attendance_updated':
        case 'attendance_deleted':
            handleAttendanceMessage(data);
            break;

        case 'submission_created':
        case 'submission_updated':
        case 'submission_deleted':
            handleSubmissionMessage(data);
            break;

        case 'sync_request':
            handleSyncRequest(clientId, data);
            break;

        default:
            sendError(ws, 'Unknown message type');
    }
}

// Handle student-related messages
function handleStudentMessage(data) {
    const student = data.data;
    
    switch (data.type) {
        case 'student_created':
            students.set(student.id, student);
            break;
        case 'student_updated':
            if (students.has(student.id)) {
                students.set(student.id, student);
            }
            break;
        case 'student_deleted':
            students.delete(student.id);
            break;
    }

    broadcastUpdate(data);
}

// Handle task-related messages
function handleTaskMessage(data) {
    const task = data.data;
    
    switch (data.type) {
        case 'task_created':
            tasks.set(task.id, task);
            break;
        case 'task_updated':
            if (tasks.has(task.id)) {
                tasks.set(task.id, task);
            }
            break;
        case 'task_deleted':
            tasks.delete(task.id);
            break;
    }

    broadcastUpdate(data);
}

// Handle attendance-related messages
function handleAttendanceMessage(data) {
    const record = data.data;
    
    switch (data.type) {
        case 'attendance_created':
            attendance.set(record.id, record);
            break;
        case 'attendance_updated':
            if (attendance.has(record.id)) {
                attendance.set(record.id, record);
            }
            break;
        case 'attendance_deleted':
            attendance.delete(record.id);
            break;
    }

    broadcastUpdate(data);
}

// Handle submission-related messages
function handleSubmissionMessage(data) {
    const submission = data.data;
    
    switch (data.type) {
        case 'submission_created':
            submissions.set(submission.id, submission);
            break;
        case 'submission_updated':
            if (submissions.has(submission.id)) {
                submissions.set(submission.id, submission);
            }
            break;
        case 'submission_deleted':
            submissions.delete(submission.id);
            break;
    }

    broadcastUpdate(data);
}

// Handle sync requests
function handleSyncRequest(clientId, data) {
    const ws = clients.get(clientId);
    if (!ws) return;

    ws.send(JSON.stringify({
        type: 'sync_response',
        data: {
            students: Array.from(students.values()),
            tasks: Array.from(tasks.values()),
            attendance: Array.from(attendance.values()),
            submissions: Array.from(submissions.values())
        }
    }));
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
    ws.send(JSON.stringify({
        type: 'error',
        message
    }));
}

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ status: 'ok' });
});

// Start server
const PORT = process.env.PORT || 8080;
server.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
}); 