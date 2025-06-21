// Main application module
class App {
    constructor() {
        // Wait for DOM to be fully loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        console.log('Initializing application...');
        
        // Initialize WebSocket connection
        this.initWebSocket();

        // Add event listeners
        this.addEventListeners();

        // Check authentication status
        this.checkAuth();
    }

    initWebSocket() {
        console.log('WebSocket connection initialized');
    }

    addEventListeners() {
        console.log('Adding event listeners...');
        
        // Add event listeners for navigation
        const navLinks = document.querySelectorAll('.nav-link:not(#logoutBtn)');
        console.log('Found navigation links:', navLinks.length);
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => this.handleNavigation(e));
        });

        // Add event listener for logout
        const logoutBtn = document.getElementById('logoutBtn');
        console.log('Logout button found:', !!logoutBtn);
        
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        }

        // Add event listener for window resize
        window.addEventListener('resize', () => this.handleResize());
    }

    handleNavigation(event) {
        event.preventDefault();
        
        // Get the target section
        const targetId = event.target.getAttribute('href').substring(1);
        const targetSection = document.getElementById(targetId);
        
        if (!targetSection) {
            console.warn(`Target section ${targetId} not found`);
            return;
        }
        
        // Hide all sections
        document.querySelectorAll('main > div').forEach(section => {
            section.classList.add('d-none');
        });
        
        // Show target section
        targetSection.classList.remove('d-none');
        
        // Update active state in navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    handleLogout() {
        if (auth && typeof auth.logout === 'function') {
            auth.logout();
        } else {
            console.warn('Auth module not properly initialized');
        }
    }

    handleResize() {
        // Handle responsive layout changes
        const sidebar = document.querySelector('.sidebar');
        const main = document.querySelector('main');
        
        if (!sidebar || !main) {
            console.warn('Sidebar or main element not found');
            return;
        }
        
        if (window.innerWidth < 768) {
            sidebar.classList.add('collapsed');
            main.classList.add('expanded');
        } else {
            sidebar.classList.remove('collapsed');
            main.classList.remove('expanded');
        }
    }

    checkAuth() {
        if (auth && typeof auth.checkAuth === 'function') {
            auth.checkAuth();
        } else {
            console.warn('Auth module not properly initialized');
        }
    }
}

// Initialize application
const app = new App(); 