// Authentication module
class Auth {
  constructor() {
    this.isAuthenticated = false;
    this.token = null;
    this.userType = null;
    this.userData = null;
    this.apiBaseUrl = '/api/login'; // Updated to use PHP API
    this.init();
  }

  init() {
    // Check for existing token
    const token = sessionStorage.getItem("token");
    const userType = sessionStorage.getItem("userType");
    const userData = sessionStorage.getItem("userData");

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
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
      loginForm.addEventListener("submit", (e) => this.handleLogin(e));
    }

    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
      logoutBtn.addEventListener("click", () => this.handleLogout());
    }
  }

  showLoginModal() {
    const loginModal = document.getElementById("loginModal");
    if (loginModal) {
      const modal = new bootstrap.Modal(loginModal);
      modal.show();
    }
  }

  showDashboard() {
    const loginModal = document.getElementById("loginModal");
    const dashboard = document.getElementById("dashboard");

    // Hide login modal if it exists and is shown
    if (loginModal) {
      const modal = bootstrap.Modal.getInstance(loginModal);
      if (modal) {
        modal.hide();
      }
    }

    // Show dashboard if it exists
    if (dashboard) {
      dashboard.style.display = "block";
      this.updateUIForUserType();
    } else {
      // If no dashboard element, redirect to main page
      if (window.location.pathname.includes("login-test.html")) {
        window.location.href = "Admin-dashboard.html";
      }
    }

    // Redirect students to student dashboard
    if (
      this.userType === "student" &&
      window.location.pathname.includes("index.html")
    ) {
      window.location.href = "student-dashboard.html";
    }
  }

  updateUIForUserType() {
    // Update navigation based on user type
    const sidebar = document.querySelector(".sidebar-nav");
    if (sidebar) {
      if (this.userType === "student") {
        // Show only student-relevant sections
        this.showStudentSections();
      } else if (this.userType === "admin") {
        // Show all admin sections
        this.showAdminSections();
      }
    }

    // Update user info in the UI
    const userInfo = document.getElementById("userInfo");
    if (userInfo) {
      userInfo.innerHTML = `
                <span class="user-name">${this.userData.name}</span>
                <span class="user-role">${
                  this.userType === "admin" ? "Administrator" : "Student"
                }</span>
            `;
    }
  }

  showStudentSections() {
    // Hide admin-only sections
    const adminSections = document.querySelectorAll(".admin-only");
    adminSections.forEach((section) => (section.style.display = "none"));

    // Show student sections
    const studentSections = document.querySelectorAll(".student-section");
    studentSections.forEach((section) => (section.style.display = "block"));

    // Update navigation links
    const navLinks = document.querySelectorAll(".sidebar-nav .nav-link");
    navLinks.forEach((link) => {
      const section = link.getAttribute("href").substring(1);
      if (section === "studentsSection" || section === "settingsSection") {
        link.style.display = "none";
      }
    });
  }

  showAdminSections() {
    // Show all sections
    const sections = document.querySelectorAll(".section");
    sections.forEach((section) => (section.style.display = "block"));

    // Show all navigation links
    const navLinks = document.querySelectorAll(".sidebar-nav .nav-link");
    navLinks.forEach((link) => (link.style.display = "block"));
  }

  async handleLogin(event) {
    event.preventDefault();
    console.log("handleLogin triggered"); // Debug log

    const form = event.target;
    const formData = new FormData(form);

    try {
      const email = formData.get("email");
      const password = formData.get("password");
      
      console.log("Attempting login with:", { email, password }); // Debug log

      if (!email || !password) {
        this.showNotification("Please fill in all fields", "error");
        return;
      }

      // Determine user type based on email or add a hidden field
      let userType = "student"; // default
      if (email === "admin@school.com") {
        userType = "admin";
      }

      // Send login request to PHP backend
      const response = await fetch(this.apiBaseUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          email: email,
          password: password,
          userType: userType
        })
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        // Login successful
        this.token = data.token;
        this.userType = data.userType;
        
        // Use user data from PHP response
        this.userData = {
          id: data.user.id,
          name: data.user.name,
          email: data.user.email,
          type: data.user.userType
        };
        
        this.isAuthenticated = true;

        // Store in sessionStorage
        sessionStorage.setItem("token", this.token);
        sessionStorage.setItem("userType", this.userType);
        sessionStorage.setItem("userData", JSON.stringify(this.userData));

        this.showDashboard();
        this.showNotification(`Welcome back, ${this.userData.name}!`, "success");
      } else {
        this.showNotification(data.message || "Login failed", "error");
      }
    } catch (error) {
      console.error("Login error:", error);
      this.showNotification("Login failed. Please try again.", "error");
    }
  }

  handleLogout() {
    // Clear session data
    sessionStorage.removeItem("token");
    sessionStorage.removeItem("userType");
    sessionStorage.removeItem("userData");

    // Reset instance variables
    this.token = null;
    this.userType = null;
    this.userData = null;
    this.isAuthenticated = false;

    // Show login modal
    this.showLoginModal();

    // Show logout notification
    this.showNotification("You have been logged out", "info");
  }

  checkAuth() {
    if (!this.isAuthenticated) {
      this.showLoginModal();
      return false;
    }
    return true;
  }

  checkAdminAuth() {
    if (!this.checkAuth() || this.userType !== "admin") {
      this.showNotification("Admin access required", "error");
      return false;
    }
    return true;
  }

  reloadSession() {
    const token = sessionStorage.getItem("token");
    const userType = sessionStorage.getItem("userType");
    const userData = sessionStorage.getItem("userData");

    if (token && userType && userData) {
      this.token = token;
      this.userType = userType;
      this.userData = JSON.parse(userData);
      this.isAuthenticated = true;
    }
  }

  getCurrentUser() {
    return this.userData;
  }

  getUserType() {
    return this.userType;
  }

  generateToken() {
    return this.token;
  }

  showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement("div");
    notification.className = `alert alert-${type === "error" ? "danger" : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
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

// Initialize authentication
const auth = new Auth();
window.auth = auth;
