# Task Manager cPanel Server

WebSocket server for handling real-time updates in the Task Manager cPanel.

## Features

- Real-time student management
- Task updates synchronization
- Attendance tracking
- Submission handling
- Offline data support

## Setup

1. Install dependencies:
   ```bash
   npm install
   ```

2. Create `.env` file:
   ```
   PORT=8080
   NODE_ENV=development
   ```

3. Start server:
   ```bash
   # Development mode
   npm run dev

   # Production mode
   npm start
   ```

## WebSocket Events

### Client to Server
- `task_created`: New task creation
- `task_updated`: Task update
- `task_deleted`: Task deletion
- `sync_request`: Client sync request
- `sync_response`: Client sync response

### Server to Client
- `task_created`: Notify new task
- `task_updated`: Notify task update
- `task_deleted`: Notify task deletion
- `sync_request`: Request client sync
- `sync_updates`: Send updates to client

## Security

- Client authentication
- Message validation
- Error handling
- Rate limiting

## Error Handling

- Connection errors
- Message parsing errors
- Unknown message types
- Client disconnections

## Development

- Uses Node.js
- WebSocket for real-time communication
- Express for HTTP endpoints
- CORS enabled
- Environment configuration

## Production Considerations

- Use HTTPS
- Implement proper authentication
- Add rate limiting
- Set up monitoring
- Configure logging
- Use PM2 for process management 