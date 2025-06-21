// Main application module
class App {
    constructor() {
        this.init();
    }

    init() {
        // Initialize WebSocket connection
        this.initWebSocket();

        // Add event listeners
        this.addEventListeners();

        // Check authentication status
        this.checkAuth();
    }

    initWebSocket() {
        // In production, this would connect to a real WebSocket server
        console.log('WebSocket connection initialized');
    }

    addEventListeners() {
        // Add event listeners for navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => this.handleNavigation(e));
        });

        // Add event listener for logout
        document.getElementById('logoutBtn').addEventListener('click', () => this.handleLogout());

        // Add event listener for window resize
        window.addEventListener('resize', () => this.handleResize());
    }

    handleNavigation(event) {
        event.preventDefault();
        
        // Get the target section
        const targetId = event.target.getAttribute('href').substring(1);
        
        // Hide all sections
        document.querySelectorAll('main > div').forEach(section => {
            section.classList.add('d-none');
        });
        
        // Show target section
        document.getElementById(targetId).classList.remove('d-none');
        
        // Update active state in navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        event.target.classList.add('active');
    }

    handleLogout() {
        if (auth.logout()) {
            // Redirect to login page
            window.location.reload();
        }
    }

    handleResize() {
        // Handle responsive layout changes
        const sidebar = document.querySelector('.sidebar');
        const main = document.querySelector('main');
        
        if (window.innerWidth < 768) {
            sidebar.classList.add('collapsed');
            main.classList.add('expanded');
        } else {
            sidebar.classList.remove('collapsed');
            main.classList.remove('expanded');
        }
    }

    checkAuth() {
        if (!auth.checkAuth()) {
            // Show login modal
            auth.showLoginModal();
        }
    }
}

// Initialize application
const app = new App(); 