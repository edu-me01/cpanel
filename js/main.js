// Modern Task Manager cPanel - Main JavaScript

class TaskManager {
    constructor() {
        this.currentSection = 'dashboard';
        this.isSidebarCollapsed = false;
        this.isNotificationsOpen = false;
            this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeDashboard();
        this.loadUserInfo();
        this.setupRealTimeUpdates();
        this.hideLoadingScreen();
    }

    setupEventListeners() {
        // Sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            this.toggleSidebar();
        });

        // Navigation items
        document.querySelectorAll('.nav-item[data-section]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const section = item.getAttribute('data-section');
                this.navigateToSection(section);
            });
        });

        // Theme toggle
        document.getElementById('themeToggle')?.addEventListener('click', () => {
            this.toggleTheme();
        });

        // Notifications toggle
        document.getElementById('notificationsBtn')?.addEventListener('click', () => {
            this.toggleNotifications();
        });

        // Search functionality
        this.setupSearchListeners();

        // Modal functionality
        this.setupModalListeners();

        // Responsive behavior
        window.addEventListener('resize', () => {
            this.handleResize();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }

    setupSearchListeners() {
        // Student search
        const studentSearch = document.getElementById('studentSearch');
        if (studentSearch) {
            studentSearch.addEventListener('input', (e) => {
                this.filterStudents(e.target.value);
            });
        }

        // Task search
        const taskSearch = document.getElementById('taskSearch');
        if (taskSearch) {
            taskSearch.addEventListener('input', (e) => {
                this.filterTasks(e.target.value);
            });
        }

        // Submission search
        const submissionSearch = document.getElementById('submissionSearch');
        if (submissionSearch) {
            submissionSearch.addEventListener('input', (e) => {
                this.filterSubmissions(e.target.value);
            });
        }
    }

    setupModalListeners() {
        // Global modal open function
        window.openModal = (modalId) => {
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
        };

        // Form submissions
        this.setupFormSubmissions();
    }

    setupFormSubmissions() {
        // Add Student Form
        const addStudentForm = document.getElementById('addStudentForm');
        if (addStudentForm) {
            addStudentForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleAddStudent(e.target);
            });
        }

        // Add Task Form
        const addTaskForm = document.getElementById('addTaskForm');
        if (addTaskForm) {
            addTaskForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleAddTask(e.target);
            });
        }

        // Mark Attendance Form
        const markAttendanceForm = document.getElementById('markAttendanceForm');
        if (markAttendanceForm) {
            markAttendanceForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleMarkAttendance(e.target);
            });
        }

        // Grade Submission Form
        const gradeSubmissionForm = document.getElementById('gradeSubmissionForm');
        if (gradeSubmissionForm) {
            gradeSubmissionForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleGradeSubmission(e.target);
            });
        }
    }

    navigateToSection(section) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(s => {
            s.classList.remove('active');
        });

        // Remove active class from all nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Show target section
        const targetSection = document.getElementById(section + 'Section');
        if (targetSection) {
            targetSection.classList.add('active');
            this.currentSection = section;
        }

        // Add active class to nav item
        const activeNavItem = document.querySelector(`[data-section="${section}"]`);
        if (activeNavItem) {
            activeNavItem.classList.add('active');
        }

        // Update breadcrumb
        this.updateBreadcrumb(section);

        // Load section-specific data
        this.loadSectionData(section);

        // Close sidebar on mobile
        if (window.innerWidth <= 1024) {
            this.collapseSidebar();
        }
    }

    updateBreadcrumb(section) {
        const breadcrumb = document.getElementById('currentSection');
        if (breadcrumb) {
            const sectionNames = {
                'dashboard': 'Dashboard',
                'students': 'Students',
                'tasks': 'Tasks',
                'attendance': 'Attendance',
                'submissions': 'Submissions',
                'analytics': 'Analytics',
                'settings': 'Settings'
            };
            breadcrumb.textContent = sectionNames[section] || 'Dashboard';
        }
    }

    loadSectionData(section) {
        switch (section) {
            case 'dashboard':
                this.loadDashboardData();
                break;
            case 'students':
                this.loadStudentsData();
                break;
            case 'tasks':
                this.loadTasksData();
                break;
            case 'attendance':
                this.loadAttendanceData();
                break;
            case 'submissions':
                this.loadSubmissionsData();
                break;
            case 'analytics':
                this.loadAnalyticsData();
                break;
            case 'settings':
                this.loadSettingsData();
                break;
        }
    }

    toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (this.isSidebarCollapsed) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            this.isSidebarCollapsed = false;
        } else {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            this.isSidebarCollapsed = true;
        }
    }

    collapseSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
        this.isSidebarCollapsed = true;
    }

    toggleTheme() {
        const body = document.body;
        const themeIcon = document.querySelector('#themeToggle i');
        
        if (body.classList.contains('light-theme')) {
            body.classList.remove('light-theme');
            body.classList.add('modern-theme');
            themeIcon.className = 'fas fa-moon';
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.remove('modern-theme');
            body.classList.add('light-theme');
            themeIcon.className = 'fas fa-sun';
            localStorage.setItem('theme', 'light');
        }
    }

    toggleNotifications() {
        const panel = document.getElementById('notificationsPanel');
        const isOpen = panel.classList.contains('open');
        
        if (isOpen) {
            panel.classList.remove('open');
            this.isNotificationsOpen = false;
        } else {
            panel.classList.add('open');
            this.isNotificationsOpen = true;
            this.loadNotifications();
        }
    }

    closeNotifications() {
        const panel = document.getElementById('notificationsPanel');
        panel.classList.remove('open');
        this.isNotificationsOpen = false;
    }

    handleResize() {
        if (window.innerWidth <= 1024) {
            this.collapseSidebar();
        }
    }

    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            this.focusSearch();
        }

        // Escape to close modals/panels
        if (e.key === 'Escape') {
            this.closeModalsAndPanels();
        }

        // Ctrl/Cmd + B to toggle sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            this.toggleSidebar();
        }
    }

    focusSearch() {
        const currentSection = this.currentSection;
        let searchInput;
        
        switch (currentSection) {
            case 'students':
                searchInput = document.getElementById('studentSearch');
                break;
            case 'tasks':
                searchInput = document.getElementById('taskSearch');
                break;
            case 'submissions':
                searchInput = document.getElementById('submissionSearch');
                break;
        }
        
        if (searchInput) {
            searchInput.focus();
        }
    }

    closeModalsAndPanels() {
        // Close notifications panel
        if (this.isNotificationsOpen) {
            this.closeNotifications();
        }

        // Close any open modals
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    }

    initializeDashboard() {
        this.loadDashboardData();
        this.setupDashboardCharts();
    }

    loadDashboardData() {
        // Load stats
        this.loadStats();
        
        // Load recent activity
        this.loadRecentActivity();
        
        // Load quick actions
        this.setupQuickActions();
    }

    loadStats() {
        // Simulate loading stats data
        const stats = {
            totalStudents: 156,
            activeTasks: 23,
            todayAttendance: 142,
            pendingSubmissions: 8
        };

        // Update stats display
        Object.keys(stats).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                this.animateNumber(element, 0, stats[key], 1000);
            }
        });
    }

    animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        const difference = end - start;
        
        const updateNumber = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (difference * progress));
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        };
        
        requestAnimationFrame(updateNumber);
    }

    loadRecentActivity() {
        const activityList = document.getElementById('activityList');
        if (!activityList) return;

        const activities = [
            { type: 'task', message: 'New task "React Fundamentals" assigned', time: '2 minutes ago' },
            { type: 'attendance', message: 'John Doe marked present', time: '5 minutes ago' },
            { type: 'submission', message: 'Assignment submitted by Jane Smith', time: '10 minutes ago' },
            { type: 'student', message: 'New student registered: Mike Johnson', time: '15 minutes ago' }
        ];

        activityList.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon ${activity.type}">
                    <i class="fas fa-${this.getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-content">
                    <p>${activity.message}</p>
                    <span class="activity-time">${activity.time}</span>
                </div>
            </div>
        `).join('');
    }

    getActivityIcon(type) {
        const icons = {
            task: 'tasks',
            attendance: 'calendar-check',
            submission: 'file-alt',
            student: 'user-plus'
        };
        return icons[type] || 'info-circle';
    }

    setupQuickActions() {
        // Quick actions are already set up in HTML with onclick handlers
        // This function can be used to add dynamic quick actions if needed
    }

    setupDashboardCharts() {
        // Initialize charts if Chart.js is available
        if (typeof Chart !== 'undefined') {
            this.createAttendanceChart();
            this.createTaskChart();
            this.createPerformanceChart();
        }
    }

    createAttendanceChart() {
        const ctx = document.getElementById('attendanceChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                datasets: [{
                    label: 'Attendance Rate',
                    data: [95, 88, 92, 85, 90],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#f8fafc' }
                    }
                },
                scales: {
                    y: {
                        ticks: { color: '#cbd5e1' },
                        grid: { color: '#334155' }
                    },
                    x: {
                        ticks: { color: '#cbd5e1' },
                        grid: { color: '#334155' }
                    }
                }
            }
        });
    }

    createTaskChart() {
        const ctx = document.getElementById('taskChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending'],
                datasets: [{
                    data: [65, 20, 15],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#f8fafc' }
                    }
                }
            }
        });
    }

    createPerformanceChart() {
        const ctx = document.getElementById('performanceChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Student A', 'Student B', 'Student C', 'Student D', 'Student E'],
                datasets: [{
                    label: 'Average Score',
                    data: [85, 92, 78, 88, 95],
                    backgroundColor: '#06b6d4'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#f8fafc' }
                    }
                },
                scales: {
                    y: {
                        ticks: { color: '#cbd5e1' },
                        grid: { color: '#334155' }
                    },
                    x: {
                        ticks: { color: '#cbd5e1' },
                        grid: { color: '#334155' }
                    }
                }
            }
        });
    }

    loadStudentsData() {
        // This will be handled by students.js
        if (typeof loadStudents === 'function') {
            loadStudents();
        }
    }

    loadTasksData() {
        // This will be handled by tasks.js
        if (typeof loadTasks === 'function') {
            loadTasks();
        }
    }

    loadAttendanceData() {
        // This will be handled by attendance.js
        if (typeof loadAttendance === 'function') {
            loadAttendance();
        }
    }

    loadSubmissionsData() {
        // This will be handled by submissions.js
        if (typeof loadSubmissions === 'function') {
            loadSubmissions();
        }
    }

    loadAnalyticsData() {
        // Analytics charts are already set up in setupDashboardCharts
    }

    loadSettingsData() {
        // Load saved settings
        this.loadSavedSettings();
    }

    loadSavedSettings() {
        const settings = {
            theme: localStorage.getItem('theme') || 'dark',
            language: localStorage.getItem('language') || 'en',
            dateFormat: localStorage.getItem('dateFormat') || 'MM/DD/YYYY',
            timeFormat: localStorage.getItem('timeFormat') || '12h',
            notifications: localStorage.getItem('notifications') === 'true'
        };

        // Apply settings
        if (settings.theme === 'light') {
            document.body.classList.remove('modern-theme');
            document.body.classList.add('light-theme');
        }

        // Update form controls
        Object.keys(settings).forEach(key => {
            const element = document.getElementById(key + 'Select') || document.getElementById(key + 'Toggle');
            if (element) {
                if (element.type === 'checkbox') {
                    element.checked = settings[key];
                } else {
                    element.value = settings[key];
                }
            }
        });
    }

    loadNotifications() {
        const notificationsList = document.getElementById('notificationsList');
        if (!notificationsList) return;

        const notifications = [
            { type: 'info', message: 'New student registration request', time: '5 min ago' },
            { type: 'warning', message: 'Task deadline approaching', time: '1 hour ago' },
            { type: 'success', message: 'Attendance session opened', time: '2 hours ago' }
        ];

        notificationsList.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.type}">
                <div class="notification-icon">
                    <i class="fas fa-${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <p>${notification.message}</p>
                    <span class="notification-time">${notification.time}</span>
                </div>
            </div>
        `).join('');
    }

    getNotificationIcon(type) {
        const icons = {
            info: 'info-circle',
            warning: 'exclamation-triangle',
            success: 'check-circle',
            error: 'times-circle'
        };
        return icons[type] || 'info-circle';
    }

    filterStudents(query) {
        // This will be implemented in students.js
        if (typeof filterStudents === 'function') {
            filterStudents(query);
        }
    }

    filterTasks(query) {
        // This will be implemented in tasks.js
        if (typeof filterTasks === 'function') {
            filterTasks(query);
        }
    }

    filterSubmissions(query) {
        // This will be implemented in submissions.js
        if (typeof filterSubmissions === 'function') {
            filterSubmissions(query);
        }
    }

    handleAddStudent(form) {
        const formData = new FormData(form);
        const studentData = {
            fullName: formData.get('fullName'),
            email: formData.get('email'),
            password: formData.get('password')
        };

        // Validate passwords match
        if (formData.get('password') !== formData.get('confirmPassword')) {
            this.showNotification('Passwords do not match', 'error');
            return;
        }
        
        // This will be handled by students.js
        if (typeof addStudent === 'function') {
            addStudent(studentData);
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
        if (modal) {
            modal.hide();
        }

        form.reset();
    }

    handleAddTask(form) {
        const formData = new FormData(form);
        const taskData = {
            title: formData.get('title'),
            description: formData.get('description'),
            dueDate: formData.get('dueDate'),
            dueTime: formData.get('dueTime'),
            priority: formData.get('priority')
        };

        // This will be handled by tasks.js
        if (typeof addTask === 'function') {
            addTask(taskData);
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
        if (modal) {
            modal.hide();
        }

        form.reset();
    }

    handleMarkAttendance(form) {
        const formData = new FormData(form);
        const attendanceData = {
            studentId: formData.get('studentId'),
            date: formData.get('date'),
            status: formData.get('status'),
            notes: formData.get('notes')
        };

        // This will be handled by attendance.js
        if (typeof markAttendance === 'function') {
            markAttendance(attendanceData);
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
        if (modal) {
            modal.hide();
        }

        form.reset();
    }

    handleGradeSubmission(form) {
        const formData = new FormData(form);
        const gradeData = {
            submissionId: formData.get('submissionId'),
            grade: formData.get('grade'),
            feedback: formData.get('feedback')
        };

        // This will be handled by submissions.js
        if (typeof gradeSubmission === 'function') {
            gradeSubmission(gradeData);
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
        if (modal) {
            modal.hide();
        }

        form.reset();
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification-toast ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 5000);
    }

    loadUserInfo() {
        // This will be handled by auth.js
        if (typeof loadUserInfo === 'function') {
            loadUserInfo();
        }
    }

    setupRealTimeUpdates() {
        // This will be handled by realtime-updates.js
        if (typeof setupRealTimeUpdates === 'function') {
            setupRealTimeUpdates();
        }
    }

    hideLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            setTimeout(() => {
                loadingScreen.style.opacity = '0';
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                }, 500);
            }, 1000);
        }
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.taskManager = new TaskManager();
});

// Global utility functions
window.showNotification = (message, type) => {
    if (window.taskManager) {
        window.taskManager.showNotification(message, type);
    }
};

window.openModal = (modalId) => {
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
};

// Close notifications function for onclick handlers
window.closeNotifications = () => {
    if (window.taskManager) {
        window.taskManager.closeNotifications();
    }
}; 