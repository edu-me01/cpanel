# Migration Guide: Node.js to PHP Backend

This guide explains how to migrate from the Node.js backend to the new PHP backend for the Training Control Panel.

## Overview

The PHP backend is a complete replacement for the Node.js server, providing the same functionality with a simpler setup and no external dependencies.

## What's Changed

### ✅ What's New in PHP Version

- **Simpler Setup**: No Node.js installation required
- **File-based Storage**: JSON files instead of in-memory storage
- **JWT Authentication**: Secure token-based authentication
- **RESTful API**: Standard HTTP methods for all operations
- **Better Error Handling**: Consistent error responses
- **CORS Support**: Built-in cross-origin request handling

### ❌ What's Removed

- **WebSocket Support**: Real-time updates now use polling
- **Node.js Dependencies**: No more package.json or npm
- **In-Memory Storage**: Data now persists in JSON files
- **Session-based Auth**: Replaced with JWT tokens

## Migration Steps

### 1. Stop Node.js Server

```bash
# If running the Node.js server, stop it
# Ctrl+C in the terminal where it's running
```

### 2. Install PHP Backend

1. **Copy PHP files** to your web server directory
2. **Set permissions** for the data directory:
   ```bash
   chmod 755 api/data/
   ```
3. **Ensure mod_rewrite is enabled** (for Apache)

### 3. Update Frontend Configuration

The frontend configuration has been updated in `js/config.js`:

```javascript
// Old Node.js configuration
server: {
  host: 'localhost',
  port: 5000,
  protocol: 'http'
}

// New PHP configuration
server: {
  host: 'localhost',
  port: 80, // or your web server port
  protocol: 'http'
}

// WebSocket disabled
websocket: {
  enabled: false
}
```

### 4. Test the Migration

1. **Open** `test-php-api.html` in your browser
2. **Run the tests** to verify everything works
3. **Check the main application** functionality

## API Endpoint Changes

### Authentication

| Node.js | PHP | Notes |
|---------|-----|-------|
| `POST /login` | `POST /api/login` | Same functionality, JWT tokens |
| `GET /health` | `GET /api/health` | Health check endpoint |

### Tasks

| Node.js | PHP | Notes |
|---------|-----|-------|
| `GET /api/tasks` | `GET /api/tasks` | Same functionality |
| `POST /api/tasks` | `POST /api/tasks` | Admin only |
| `PUT /api/tasks/:id` | `PUT /api/tasks/:id` | Admin only |
| `DELETE /api/tasks/:id` | `DELETE /api/tasks/:id` | Admin only |
| `PUT /api/tasks/:id/complete` | `PUT /api/tasks/:id/complete` | Mark as complete |

### Students, Attendance, Submissions

All endpoints follow the same pattern with `/api/` prefix.

## Authentication Changes

### Node.js (Old)
```javascript
// Simple token validation
if (token.includes('admin') || token.includes('student')) {
  // Grant access
}
```

### PHP (New)
```javascript
// JWT token authentication
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

## Data Storage Changes

### Node.js (Old)
- In-memory storage using Maps
- Data lost on server restart
- No persistence

### PHP (New)
- JSON file storage in `api/data/`
- Persistent data across restarts
- Files: `users.json`, `tasks.json`, `students.json`, etc.

## Real-time Updates

### Node.js (Old)
- WebSocket connections for real-time updates
- Instant notifications of changes

### PHP (New)
- Polling every 30 seconds for updates
- Simpler but less real-time

## Error Handling

### Node.js (Old)
```javascript
// Inconsistent error responses
res.status(500).json({ message: 'Error' });
```

### PHP (New)
```javascript
// Consistent error format
{
  "success": false,
  "message": "Error description",
  "error": "Internal server error" // Only in 500 errors
}
```

## Testing the Migration

### 1. Health Check
```bash
curl http://localhost/api/health
```

### 2. Login Test
```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@school.com","password":"admin123","userType":"admin"}'
```

### 3. Tasks API Test
```bash
# Get tasks (requires auth token)
curl http://localhost/api/tasks \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Troubleshooting

### Common Issues

1. **404 Errors**
   - Check that mod_rewrite is enabled
   - Verify .htaccess file is present
   - Ensure web server is configured correctly

2. **Permission Errors**
   - Set correct permissions on `api/data/` directory
   - Ensure web server can write to data files

3. **CORS Errors**
   - PHP API includes CORS headers
   - Check browser console for specific errors

4. **Authentication Failures**
   - Verify JWT tokens are being sent correctly
   - Check that Authorization header format is correct

### Debug Steps

1. **Check PHP error logs**
2. **Test individual endpoints** using the test page
3. **Verify file permissions** on data directory
4. **Check web server configuration**

## Performance Considerations

### PHP Advantages
- **No startup time** - instant response
- **Lower memory usage** - no Node.js runtime
- **Simpler deployment** - standard web hosting

### PHP Limitations
- **No real-time updates** - polling required
- **File I/O overhead** - JSON file operations
- **Single-threaded** - PHP execution model

## Rollback Plan

If you need to rollback to Node.js:

1. **Stop PHP web server**
2. **Restart Node.js server**: `node server/websocket-server.js`
3. **Revert config.js** to original settings
4. **Restore original JavaScript files**

## Support

For issues with the PHP migration:

1. **Check the test page** (`test-php-api.html`)
2. **Review error logs** in your web server
3. **Verify API endpoints** using browser dev tools
4. **Test with curl** for command-line debugging

## Conclusion

The PHP backend provides a simpler, more maintainable solution while preserving all core functionality. The migration should be seamless for most users, with the main change being the removal of real-time WebSocket updates in favor of polling-based updates. 