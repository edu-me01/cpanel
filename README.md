# Task Manager cPanel

A comprehensive control panel for managing students, tasks, attendance, and submissions.

## Features

- **Student Management**
  - Add, edit, and delete students
  - Track student status (active/inactive)
  - Search and filter students

- **Task Management**
  - Create and manage tasks
  - Set priorities and due dates
  - Track task status

- **Attendance System**
  - Real-time attendance tracking
  - Status determination (present/late/absent)
  - Date-based filtering

- **Submission Management**
  - View student submissions
  - Grade submissions
  - Track submission status

- **Admin Features**
  - Secure login system
  - System settings management
  - Theme customization
  - Notification system

## Project Structure

```
cpanel/
├── index.html          # Main dashboard
├── css/
│   └── style.css      # Custom styles
├── js/
│   ├── auth.js        # Authentication module
│   ├── students.js    # Students management
│   ├── tasks.js       # Tasks management
│   ├── attendance.js  # Attendance system
│   ├── submissions.js # Submissions management
│   ├── settings.js    # Settings management
│   └── main.js        # Main initialization
└── server/
    ├── websocket-server.js  # WebSocket server
    ├── package.json         # Server dependencies
    └── README.md           # Server documentation
```

## Setup Instructions

1. **Install Dependencies**
   ```bash
   cd server
   npm install
   ```

2. **Start WebSocket Server**
   ```bash
   npm run dev
   ```

3. **Access cPanel**
   - Open `index.html` in a web browser
   - Default login credentials:
     - Username: `admin`
     - Password: `admin123`

## Security Features

- Secure authentication system
- Session management
- Password protection
- Offline data handling
- Real-time synchronization

## Technical Requirements

- Modern web browser
- Node.js (v14 or higher)
- npm (v6 or higher)

## Development

- Built with vanilla JavaScript (ES6+)
- Uses Bootstrap 5 for UI
- WebSocket for real-time features
- LocalStorage for data persistence

## Security Considerations

For production use, implement:
- Proper password hashing
- JWT authentication
- HTTPS
- Input validation
- Rate limiting
- Error logging 