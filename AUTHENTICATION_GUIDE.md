# 🔐 Authentication System Guide

## Overview
This guide explains how the authentication system works in the Task Manager cPanel and how to use it properly.

## 🚀 Quick Start

### 1. Login Credentials
- **Admin:** `admin@school.com` / `admin123`
- **Student:** `student@school.com` / `student123`

### 2. How to Login
1. Open `index.html` in your browser
2. Choose your user type (Admin or Student)
3. Enter your credentials
4. Click "Login"

## 🔧 How It Works

### Authentication Flow
1. **Login Page** (`index.html`)
   - User selects type (Admin/Student)
   - Enters credentials
   - System validates and stores session data

2. **Session Storage**
   - `token`: Unique authentication token
   - `userType`: 'admin' or 'student'
   - `userEmail`: User's email address
   - `userData`: Complete user information (JSON)

3. **Dashboard Access**
   - Each dashboard checks for valid session
   - Redirects to login if no valid session
   - Shows appropriate dashboard based on user type

### Session Management
```javascript
// Check if user is authenticated
const token = sessionStorage.getItem('token');
const userType = sessionStorage.getItem('userType');
const userData = sessionStorage.getItem('userData');

if (!token || !userType || !userData) {
    // User is not authenticated
    window.location.href = 'index.html';
}
```

## 📁 File Structure

### Core Files
- `index.html` - Login page
- `Admin-dashboard.html` - Admin dashboard
- `student-dashboard.html` - Student dashboard
- `js/auth.js` - Authentication logic
- `test-login.html` - Testing page

### Authentication Functions
- `auth.checkAuth()` - Check if user is logged in
- `auth.checkAdminAuth()` - Check if user is admin
- `auth.reloadSession()` - Reload session from localStorage
- `window.logout()` - Logout function

## 🧪 Testing

### Using the Test Page
1. Open `test-login.html`
2. Use the test buttons to:
   - Test admin login
   - Test student login
   - Test logout
   - Check authentication status
   - Navigate to dashboards

### Manual Testing
```javascript
// Test admin login
sessionStorage.setItem('token', 'admin-token-123');
sessionStorage.setItem('userType', 'admin');
sessionStorage.setItem('userEmail', 'admin@school.com');
sessionStorage.setItem('userData', JSON.stringify({
    id: 'admin',
    name: 'Admin',
    email: 'admin@school.com',
    type: 'admin'
}));

// Test logout
sessionStorage.removeItem('token');
sessionStorage.removeItem('userType');
sessionStorage.removeItem('userEmail');
sessionStorage.removeItem('userData');
```

## 🔒 Security Features

### Session Validation
- All protected pages check for valid session
- Automatic redirect to login if session invalid
- User type validation (admin vs student)

### Data Protection
- Session data stored in localStorage
- Token-based authentication
- User type restrictions

## 🛠️ Troubleshooting

### Common Issues

#### "Please login to continue" Error
**Cause:** Missing or invalid session data
**Solution:**
1. Clear localStorage: `sessionStorage.clear()`
2. Go to `index.html` and login again
3. Check that all required data is stored

#### "Admin access required" Error
**Cause:** User is not logged in as admin
**Solution:**
1. Logout and login as admin
2. Check `userType` in localStorage
3. Ensure `userData.type` is 'admin'

#### Dashboard not showing
**Cause:** Session check failed
**Solution:**
1. Check browser console for errors
2. Verify all session data exists
3. Try refreshing the page

### Debug Steps
1. Open browser developer tools (F12)
2. Go to Application/Storage tab
3. Check localStorage contents
4. Verify all required keys exist:
   - `token`
   - `userType`
   - `userEmail`
   - `userData`

## 📝 API Reference

### Auth Class Methods
```javascript
// Check if user is authenticated
auth.checkAuth() // Returns boolean

// Check if user is admin
auth.checkAdminAuth() // Returns boolean

// Reload session from localStorage
auth.reloadSession() // Updates internal state

// Get current user data
auth.getCurrentUser() // Returns user object

// Get user type
auth.getUserType() // Returns 'admin' or 'student'
```

### Global Functions
```javascript
// Logout function
window.logout() // Clears session and redirects

// Open modal
window.openModal(modalId) // Opens Bootstrap modal

// Close notifications
window.closeNotifications() // Closes notification panel
```

## 🎯 Best Practices

### For Developers
1. Always check authentication before sensitive operations
2. Use `auth.checkAdminAuth()` for admin-only features
3. Use `auth.checkAuth()` for general authentication
4. Clear session data on logout
5. Validate user type before showing content

### For Users
1. Always logout when finished
2. Don't share login credentials
3. Use the correct user type for your role
4. Clear browser data if having issues

## 🔄 Session Lifecycle

1. **Login** → Store session data
2. **Page Load** → Check session validity
3. **Action** → Verify permissions
4. **Logout** → Clear session data

## 📞 Support

If you encounter issues:
1. Check this guide first
2. Use the test page (`test-login.html`)
3. Check browser console for errors
4. Verify localStorage contents
5. Try logging out and back in

---

**Note:** This is a frontend-only authentication system for demonstration purposes. In production, you would implement proper backend authentication with JWT tokens and secure session management. 