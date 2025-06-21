// Authentication module
class Auth {
    constructor() {
        this.token = localStorage.getItem('token');
        this.init();
    }

    init() {
        // Add event listener for login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Check if user is already logged in
        if (this.token) {
            this.showDashboard();
        } else {
            this.showLoginModal();
        }
    }

    showLoginModal() {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
    }

    async handleLogin(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);

        const username = formData.get('username');
        const password = formData.get('password');

        // In production, this would be an API call
        if (username === 'admin' && password === 'admin123') {
            this.token = 'dummy-token-' + Date.now();
            localStorage.setItem('token', this.token);
            
            const loginModal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            loginModal.hide();
            
            this.showDashboard();
            this.showNotification('Login successful', 'success');
        } else {
            this.showNotification('Invalid credentials', 'error');
        }
    }

    logout() {
        this.token = null;
        localStorage.removeItem('token');
        this.showLoginModal();
        this.showNotification('Logged out successfully', 'success');
        return true;
    }

    showDashboard() {
        document.getElementById('dashboard').style.display = 'flex';
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    checkAuth() {
        if (!this.token) {
            this.showLoginModal();
            this.showNotification('Please log in to continue', 'error');
            return false;
        }
        return true;
    }

    getCurrentUser() {
        // In production, this would decode the JWT token
        return {
            username: 'admin',
            role: 'administrator'
        };
    }
}

// Initialize auth
const auth = new Auth(); 