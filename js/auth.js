// Authentication module
class Auth {
    constructor() {
        this.isAuthenticated = false;
        this.token = null;
        this.userType = null;
        this.userData = null;
        this.init();
    }

    init() {
        // Check for existing token
        const token = localStorage.getItem('token');
        const userType = localStorage.getItem('userType');
        const userData = localStorage.getItem('userData');
        
        if (token && userType && userData) {
            this.token = token;
            this.userType = userType;
            this.userData = JSON.parse(userData);
            this.isAuthenticated = true;
            this.showDashboard();
        } else {
            this.showLoginModal();
        }

        // Add event listeners
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.handleLogout());
        }
    }

    showLoginModal() {
        const loginModal = document.getElementById('loginModal');
        if (loginModal) {
            const modal = new bootstrap.Modal(loginModal);
            modal.show();
        }
    }

    showDashboard() {
        const loginModal = document.getElementById('loginModal');
        const dashboard = document.getElementById('dashboard');
        
        // Hide login modal if it exists and is shown
        if (loginModal) {
            const modal = bootstrap.Modal.getInstance(loginModal);
            if (modal) {
                modal.hide();
            }
        }
        
        // Show dashboard if it exists
        if (dashboard) {
            dashboard.style.display = 'block';
            this.updateUIForUserType();
        } else {
            // If no dashboard element, redirect to main page
            if (window.location.pathname.includes('login-test.html')) {
                window.location.href = 'index.html';
            }
        }

        // Redirect students to student dashboard
        if (this.userType === 'student' && window.location.pathname.includes('index.html')) {
            window.location.href = 'student-dashboard.html';
        }
    }

    updateUIForUserType() {
        // Update navigation based on user type
        const sidebar = document.querySelector('.sidebar-nav');
        if (sidebar) {
            if (this.userType === 'student') {
                // Show only student-relevant sections
                this.showStudentSections();
            } else if (this.userType === 'admin') {
                // Show all admin sections
                this.showAdminSections();
            }
        }

        // Update user info in the UI
        const userInfo = document.getElementById('userInfo');
        if (userInfo) {
            userInfo.innerHTML = `
                <span class="user-name">${this.userData.name}</span>
                <span class="user-role">${this.userType === 'admin' ? 'Administrator' : 'Student'}</span>
            `;
        }
    }

    showStudentSections() {
        // Hide admin-only sections
        const adminSections = document.querySelectorAll('.admin-only');
        adminSections.forEach(section => section.style.display = 'none');

        // Show student sections
        const studentSections = document.querySelectorAll('.student-section');
        studentSections.forEach(section => section.style.display = 'block');

        // Update navigation links
        const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
        navLinks.forEach(link => {
            const section = link.getAttribute('href').substring(1);
            if (section === 'studentsSection' || section === 'settingsSection') {
                link.style.display = 'none';
            }
        });
    }

    showAdminSections() {
        // Show all sections
        const sections = document.querySelectorAll('.section');
        sections.forEach(section => section.style.display = 'block');

        // Show all navigation links
        const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
        navLinks.forEach(link => link.style.display = 'block');
    }

    async handleLogin(event) {
        event.preventDefault();
        console.log('handleLogin triggered'); // Debug log
        
        const form = event.target;
        const formData = new FormData(form);
        
        try {
            const email = formData.get('email');
            const password = formData.get('password');
            console.log('Attempting login with:', { email, password }); // Debug log
            console.log('Email length:', email.length); // Debug log
            console.log('Email char codes:', Array.from(email).map(c => c.charCodeAt(0))); // Debug log
            
            if (!email || !password) {
                this.showNotification('Please fill in all fields', 'error');
                return;
            }

            // Check if it's an admin login
            console.log('Checking admin login with:', { email, password }); // Debug log
            console.log('Expected admin email:', 'admin@school.com'); // Debug log
            console.log('Expected admin password:', 'admin123'); // Debug log
            console.log('Email match:', email === 'admin@school.com'); // Debug log
            console.log('Password match:', password === 'admin123'); // Debug log
            if (email === 'admin@school.com' && password === 'admin123') {
                console.log('Admin login successful'); // Debug log
                this.userData = {
                    id: 'admin',
                    name: 'Admin',
                    email: 'admin@school.com',
                    type: 'admin'
                };
                this.userType = 'admin';
                this.isAuthenticated = true;
                this.token = this.generateToken();
                
                // Store in localStorage
                localStorage.setItem('token', this.token);
                localStorage.setItem('userType', this.userType);
                localStorage.setItem('userData', JSON.stringify(this.userData));
                
                this.showDashboard();
                this.showNotification('Welcome back, Admin!', 'success');
                return;
            }
            console.log('Not an admin, checking student login'); // Debug log

            // Check student login
            const students = JSON.parse(localStorage.getItem('students') || '[]');
            const student = students.find(s => s.email === email && s.password === password);

            if (student) {
                console.log('Student login successful for:', student.fullName); // Debug log
                this.userData = {
                    id: student.id,
                    name: student.fullName,
                    email: student.email,
                    type: 'student'
                };
                this.userType = 'student';
                this.isAuthenticated = true;
                this.token = this.generateToken();
                
                // Store in localStorage
                localStorage.setItem('token', this.token);
                localStorage.setItem('userType', this.userType);
                localStorage.setItem('userData', JSON.stringify(this.userData));
                
                this.showDashboard();
                this.showNotification('Welcome back, ' + student.fullName + '!', 'success');
            } else {
                console.log('Invalid email or password'); // Debug log
                throw new Error('Invalid email or password');
            }
        } catch (error) {
            console.error('Login error:', error); // Debug log
            this.showNotification(error.message, 'error');
        }
    }

    handleLogout() {
        this.token = null;
        this.userType = null;
        this.userData = null;
        this.isAuthenticated = false;
        localStorage.removeItem('token');
        localStorage.removeItem('userType');
        localStorage.removeItem('userData');
        this.showLoginModal();
        this.showNotification('Logged out successfully', 'success');
                        window.location.href = 'login.html';

    }

    checkAuth() {
        if (!this.isAuthenticated) {
            this.showNotification('Please login to continue', 'error');
            this.showLoginModal();
            return false;
        }
        return true;
    }

    checkAdminAuth() {
        if (!this.isAuthenticated || this.userType !== 'admin') {
            this.showNotification('Admin access required', 'error');
            return false;

        }
        return true;
    }

    getCurrentUser() {
        return this.userData;
    }

    getUserType() {
        return this.userType;
    }

    generateToken() {
        // In production, this would be a proper JWT token
        return 'dummy-token-' + Math.random().toString(36).substr(2);
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Try to find a suitable container
        let container = document.querySelector('.container');
        if (!container) {
            container = document.querySelector('.container-fluid');
        }
        if (!container) {
            container = document.body;
        }
        
        container.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize auth
const auth = new Auth(); 