/**
 * Attendance Token Management System
 * Handles token generation, validation, and usage tracking
 */
class AttendanceTokenManager {
    constructor() {
        this.currentToken = null;
        this.tokenStatus = null;
        this.init();
    }

    init() {
        this.loadTokenStatus();
        this.setupEventListeners();
        this.setupRealTimeUpdates();
    }

    setupEventListeners() {
        // Admin controls
        const generateTokenBtn = document.getElementById('generateTokenBtn');
        if (generateTokenBtn) {
            generateTokenBtn.addEventListener('click', () => this.generateToken());
        }

        const finishAttendanceBtn = document.getElementById('finishAttendanceBtn');
        if (finishAttendanceBtn) {
            finishAttendanceBtn.addEventListener('click', () => this.finishAttendance());
        }

        // Student attendance form
        const attendanceForm = document.getElementById('attendanceForm');
        if (attendanceForm) {
            attendanceForm.addEventListener('submit', (e) => this.submitAttendance(e));
        }

        // Token input validation
        const tokenInput = document.getElementById('attendanceToken');
        if (tokenInput) {
            tokenInput.addEventListener('input', (e) => this.validateTokenInput(e.target.value));
        }
    }

    setupRealTimeUpdates() {
        // Refresh token status every 30 seconds
        setInterval(() => {
            this.loadTokenStatus();
        }, 30000);
    }

    async loadTokenStatus() {
        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                console.error('No authentication token found');
                return;
            }

            const response = await fetch('/api/attendance-token/status', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                this.tokenStatus = await response.json();
                this.updateUI();
            } else {
                console.error('Failed to load token status');
            }
        } catch (error) {
            console.error('Error loading token status:', error);
        }
    }

    async generateToken() {
        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                this.showNotification('Please login again', 'error');
                return;
            }

            const response = await fetch('/api/attendance-token/generate', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.currentToken = data.token;
                this.showNotification('Attendance token generated successfully', 'success');
                this.loadTokenStatus();
                
                // Update token display
                const tokenDisplay = document.getElementById('currentToken');
                if (tokenDisplay) {
                    tokenDisplay.textContent = data.token;
                    tokenDisplay.style.display = 'block';
                }
            } else {
                const errorData = await response.json();
                this.showNotification(errorData.message || 'Failed to generate token', 'error');
            }
        } catch (error) {
            console.error('Error generating token:', error);
            this.showNotification('Network error while generating token', 'error');
        }
    }

    async finishAttendance() {
        if (!confirm('Are you sure you want to finish attendance? This will prevent students from marking attendance.')) {
            return;
        }

        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                this.showNotification('Please login again', 'error');
                return;
            }

            const response = await fetch('/api/attendance-token/finish', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.showNotification(data.message, 'success');
                this.loadTokenStatus();
                
                // Hide token display
                const tokenDisplay = document.getElementById('currentToken');
                if (tokenDisplay) {
                    tokenDisplay.style.display = 'none';
                }
            } else {
                const errorData = await response.json();
                this.showNotification(errorData.message || 'Failed to finish attendance', 'error');
            }
        } catch (error) {
            console.error('Error finishing attendance:', error);
            this.showNotification('Network error while finishing attendance', 'error');
        }
    }

    async validateTokenInput(tokenValue) {
        if (!tokenValue || tokenValue.length < 10) {
            return;
        }

        try {
            const response = await fetch('/api/attendance-token/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ token: tokenValue })
            });

            const data = await response.json();
            
            const tokenInput = document.getElementById('attendanceToken');
            const submitBtn = document.getElementById('submitAttendanceBtn');
            
            if (response.ok) {
                tokenInput.classList.remove('is-invalid');
                tokenInput.classList.add('is-valid');
                if (submitBtn) submitBtn.disabled = false;
            } else {
                tokenInput.classList.remove('is-valid');
                tokenInput.classList.add('is-invalid');
                if (submitBtn) submitBtn.disabled = true;
            }
        } catch (error) {
            console.error('Error validating token:', error);
        }
    }

    async submitAttendance(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const tokenValue = formData.get('attendanceToken');
        const studentName = formData.get('studentName');
        const studentId = formData.get('studentId');

        if (!tokenValue) {
            this.showNotification('Please enter the attendance token', 'error');
            return;
        }

        try {
            // First, validate the token
            const validateResponse = await fetch('/api/attendance-token/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ token: tokenValue })
            });

            if (!validateResponse.ok) {
                const errorData = await validateResponse.json();
                this.showNotification(errorData.message || 'Invalid attendance token', 'error');
                return;
            }

            // Mark token as used
            const useResponse = await fetch('/api/attendance-token/use', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    token: tokenValue,
                    studentId: studentId
                })
            });

            if (!useResponse.ok) {
                const errorData = await useResponse.json();
                this.showNotification(errorData.message || 'Failed to use token', 'error');
                return;
            }

            // Create attendance record
            const authToken = sessionStorage.getItem('token');
            const attendanceResponse = await fetch('/api/attendance', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${authToken}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    studentId: studentId,
                    studentName: studentName,
                    date: new Date().toISOString().split('T')[0],
                    status: 'present',
                    timeIn: new Date().toLocaleTimeString(),
                    notes: 'Marked attendance via token'
                })
            });

            if (attendanceResponse.ok) {
                this.showNotification('Attendance marked successfully!', 'success');
                event.target.reset();
                
                // Store token in cookie for future use
                this.setCookie('attendance_token', tokenValue, 1);
                
                // Redirect to lectures or show success message
                setTimeout(() => {
                    window.location.href = 'student-dashboard.html';
                }, 2000);
            } else {
                const errorData = await attendanceResponse.json();
                this.showNotification(errorData.message || 'Failed to mark attendance', 'error');
            }
        } catch (error) {
            console.error('Error submitting attendance:', error);
            this.showNotification('Network error while submitting attendance', 'error');
        }
    }

    updateUI() {
        if (!this.tokenStatus) return;

        const statusContainer = document.getElementById('tokenStatus');
        const generateBtn = document.getElementById('generateTokenBtn');
        const finishBtn = document.getElementById('finishAttendanceBtn');
        const tokenDisplay = document.getElementById('tokenDisplay');
        const currentTokenDiv = document.getElementById('currentToken');

        if (statusContainer) {
            let statusHtml = '';
            switch (this.tokenStatus.status) {
                case 'active':
                    statusHtml = `
                        <div class="alert alert-success">
                            <strong>Status:</strong> Attendance Active<br>
                            <strong>Date:</strong> ${this.tokenStatus.date}<br>
                            <strong>Students Attended:</strong> ${this.tokenStatus.usedCount}
                        </div>
                    `;
                    if (generateBtn) generateBtn.disabled = true;
                    if (finishBtn) finishBtn.disabled = false;
                    if (tokenDisplay && this.tokenStatus.token) {
                        tokenDisplay.textContent = this.tokenStatus.token;
                        if (currentTokenDiv) currentTokenDiv.style.display = 'block';
                    }
                    break;
                case 'finished':
                    statusHtml = `
                        <div class="alert alert-warning">
                            <strong>Status:</strong> Attendance Finished<br>
                            <strong>Date:</strong> ${this.tokenStatus.date}<br>
                            <strong>Total Students:</strong> ${this.tokenStatus.usedCount}
                        </div>
                    `;
                    if (generateBtn) generateBtn.disabled = false;
                    if (finishBtn) finishBtn.disabled = true;
                    if (currentTokenDiv) currentTokenDiv.style.display = 'none';
                    break;
                default:
                    statusHtml = `
                        <div class="alert alert-info">
                            <strong>Status:</strong> No Active Attendance<br>
                            <strong>Date:</strong> ${this.tokenStatus.date}
                        </div>
                    `;
                    if (generateBtn) generateBtn.disabled = false;
                    if (finishBtn) finishBtn.disabled = true;
                    if (currentTokenDiv) currentTokenDiv.style.display = 'none';
                    break;
            }
            statusContainer.innerHTML = statusHtml;
        }
    }

    setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
    }

    getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize attendance token manager
const attendanceTokenManager = new AttendanceTokenManager();
window.attendanceTokenManager = attendanceTokenManager; 