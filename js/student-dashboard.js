// Student Dashboard module
class StudentDashboard {
    constructor() {
        this.init();
    }

    init() {
        // Check if user is authenticated and is a student
        if (!auth.checkAuth() || auth.getUserType() !== 'student') {
            window.location.href = 'index.html';
            return;
        }

        // Load student data
        this.loadStudentData();
        
        // Add event listeners
        this.addEventListeners();
    }

    loadStudentData() {
        const student = auth.getCurrentUser();
        if (!student) return;

        // Update UI with student info
        document.getElementById('studentName').textContent = student.name;
        document.getElementById('studentEmail').textContent = student.email;

        // Load tasks
        this.loadTasks();
        
        // Load submissions
        this.loadSubmissions();
        
        // Load progress data
        this.loadProgressData();
    }

    loadTasks() {
        // In production, this would be an API call
        const tasks = JSON.parse(localStorage.getItem('tasks') || '[]');
        const studentTasks = tasks.filter(task => task.assignedTo === auth.getCurrentUser().id);
        
        const tbody = document.getElementById('studentTasksTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        studentTasks.forEach(task => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${task.title}</td>
                <td>${task.dueDate}</td>
                <td>
                    <span class="badge bg-${this.getPriorityColor(task.priority)}">
                        ${task.priority}
                    </span>
                </td>
                <td>
                    <span class="badge bg-${task.completed ? 'success' : 'warning'}">
                        ${task.completed ? 'Completed' : 'Pending'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="studentDashboard.viewTask('${task.id}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${!task.completed ? `
                        <button class="btn btn-sm btn-success" onclick="studentDashboard.completeTask('${task.id}')">
                            <i class="fas fa-check"></i>
                        </button>
                    ` : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Update task counts
        document.getElementById('completedTasks').textContent = 
            studentTasks.filter(t => t.completed).length;
        document.getElementById('totalTasks').textContent = studentTasks.length;
        document.getElementById('upcomingTasks').textContent = 
            studentTasks.filter(t => !t.completed && this.isUpcoming(t.dueDate)).length;
    }

    loadSubmissions() {
        // In production, this would be an API call
        const submissions = JSON.parse(localStorage.getItem('submissions') || '[]');
        const studentSubmissions = submissions.filter(sub => sub.studentId === auth.getCurrentUser().id);
        
        const tbody = document.getElementById('studentSubmissionsTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        studentSubmissions.forEach(submission => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${submission.taskTitle}</td>
                <td>${submission.submittedAt}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(submission.status)}">
                        ${submission.status}
                    </span>
                </td>
                <td>${submission.grade || 'N/A'}</td>
                <td>${submission.feedback || 'No feedback yet'}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    loadProgressData() {
        // In production, this would be an API call
        const tasks = JSON.parse(localStorage.getItem('tasks') || '[]');
        const studentTasks = tasks.filter(task => task.assignedTo === auth.getCurrentUser().id);
        const submissions = JSON.parse(localStorage.getItem('submissions') || '[]');
        const studentSubmissions = submissions.filter(sub => sub.studentId === auth.getCurrentUser().id);

        // Calculate overall progress
        const completedTasks = studentTasks.filter(t => t.completed).length;
        const totalTasks = studentTasks.length;
        const progress = totalTasks > 0 ? (completedTasks / totalTasks) * 100 : 0;
        
        const progressBar = document.getElementById('overallProgress');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
            progressBar.textContent = `${Math.round(progress)}%`;
        }

        // Calculate average grade
        const gradedSubmissions = studentSubmissions.filter(s => s.grade);
        const averageGrade = gradedSubmissions.length > 0
            ? gradedSubmissions.reduce((sum, s) => sum + s.grade, 0) / gradedSubmissions.length
            : 0;
        
        document.getElementById('averageGrade').textContent = `${Math.round(averageGrade)}%`;

        // Update recent grades
        const recentGrades = document.getElementById('recentGrades');
        if (recentGrades) {
            recentGrades.innerHTML = gradedSubmissions
                .slice(-5)
                .map(s => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ${s.taskTitle}
                        <span class="badge bg-primary rounded-pill">${s.grade}%</span>
                    </li>
                `)
                .join('');
        }

        // Update attendance history
        const attendance = JSON.parse(localStorage.getItem('attendance') || '[]');
        const studentAttendance = attendance.filter(a => a.studentId === auth.getCurrentUser().id);
        const attendanceRate = studentAttendance.length > 0
            ? (studentAttendance.filter(a => a.status === 'present').length / studentAttendance.length) * 100
            : 0;
        
        document.getElementById('attendanceRate').textContent = `${Math.round(attendanceRate)}%`;

        const attendanceHistory = document.getElementById('attendanceHistory');
        if (attendanceHistory) {
            attendanceHistory.innerHTML = studentAttendance
                .slice(-5)
                .map(a => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        ${a.date}
                        <span class="badge bg-${this.getAttendanceColor(a.status)}">
                            ${a.status}
                        </span>
                    </li>
                `)
                .join('');
        }

        // Load attendance data for student dashboard
        this.loadAttendanceData();
    }

    loadAttendanceData() {
        // Check if attendance is open (this would come from admin settings)
        const attendanceStatus = localStorage.getItem('attendanceOpen') === 'true';
        const currentDate = new Date().toISOString().split('T')[0];
        const currentSession = localStorage.getItem('currentSession') || 'morning';

        // Update attendance status display
        const statusElement = document.getElementById('studentAttendanceStatus');
        if (statusElement) {
            statusElement.className = `badge bg-${attendanceStatus ? 'success' : 'danger'}`;
            statusElement.textContent = attendanceStatus ? 'Open' : 'Closed';
        }

        // Show/hide attendance form
        const attendanceForm = document.getElementById('attendanceForm');
        if (attendanceForm) {
            attendanceForm.style.display = attendanceStatus ? 'block' : 'none';
        }

        // Set current date and session in form
        const dateInput = document.getElementById('studentAttendanceDate');
        const sessionSelect = document.getElementById('studentAttendanceSession');
        if (dateInput) dateInput.value = currentDate;
        if (sessionSelect) sessionSelect.value = currentSession;

        // Load attendance history table
        this.loadAttendanceHistory();
    }

    loadAttendanceHistory() {
        const tbody = document.getElementById('studentAttendanceTableBody');
        if (!tbody) return;

        const attendance = JSON.parse(localStorage.getItem('attendance') || '[]');
        const studentAttendance = attendance.filter(a => a.studentId === auth.getCurrentUser().id);

        tbody.innerHTML = '';

        studentAttendance.forEach(record => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${record.date}</td>
                <td>${record.session}</td>
                <td>
                    <span class="badge bg-${this.getAttendanceColor(record.status)}">
                        ${record.status}
                    </span>
                </td>
                <td>${record.time || '-'}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    markAttendance(status) {
        const currentUser = auth.getCurrentUser();
        if (!currentUser) {
            auth.showNotification('Please log in to mark attendance', 'error');
            return;
        }

        // Check if attendance is open
        const attendanceStatus = localStorage.getItem('attendanceOpen') === 'true';
        if (!attendanceStatus) {
            auth.showNotification('Attendance is currently closed', 'error');
            return;
        }

        const currentDate = new Date().toISOString().split('T')[0];
        const currentSession = localStorage.getItem('currentSession') || 'morning';

        // Check if already marked attendance for today
        const attendance = JSON.parse(localStorage.getItem('attendance') || '[]');
        const existingRecord = attendance.find(a => 
            a.studentId === currentUser.id && 
            a.date === currentDate && 
            a.session === currentSession
        );

        if (existingRecord) {
            auth.showNotification('You have already marked attendance for this session', 'warning');
            return;
        }

        // Add new attendance record
        const newRecord = {
            studentId: currentUser.id,
            date: currentDate,
            session: currentSession,
            status: status,
            time: new Date().toLocaleTimeString()
        };

        attendance.push(newRecord);
        localStorage.setItem('attendance', JSON.stringify(attendance));

        // Update UI
        this.loadAttendanceHistory();
        this.loadProgressData();

        auth.showNotification(`Attendance marked as ${status}`, 'success');
    }

    addEventListeners() {
        // Search functionality
        const searchInput = document.querySelector('input[placeholder="Search tasks..."]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
        }

        // Filter functionality
        const filterSelect = document.querySelector('select');
        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => this.handleFilter(e.target.value));
        }

        // Listen for attendance status changes (real-time updates)
        window.addEventListener('attendanceStatusChanged', (event) => {
            this.handleAttendanceStatusChange(event.detail);
        });

        // Listen for localStorage changes (for cross-tab communication)
        window.addEventListener('storage', (event) => {
            if (event.key === 'attendanceOpen' || event.key === 'currentSession') {
                this.loadAttendanceData();
            }
        });
    }

    handleSearch(query) {
        const tbody = document.getElementById('studentTasksTableBody');
        if (!tbody) return;

        const rows = tbody.getElementsByTagName('tr');
        for (let row of rows) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query.toLowerCase()) ? '' : 'none';
        }
    }

    handleFilter(filter) {
        const tbody = document.getElementById('studentTasksTableBody');
        if (!tbody) return;

        const rows = tbody.getElementsByTagName('tr');
        for (let row of rows) {
            const status = row.querySelector('.badge').textContent.toLowerCase();
            switch (filter) {
                case 'completed':
                    row.style.display = status === 'completed' ? '' : 'none';
                    break;
                case 'pending':
                    row.style.display = status === 'pending' ? '' : 'none';
                    break;
                case 'upcoming':
                    const dueDate = row.cells[1].textContent;
                    row.style.display = this.isUpcoming(dueDate) ? '' : 'none';
                    break;
                default:
                    row.style.display = '';
            }
        }
    }

    viewTask(taskId) {
        // In production, this would open a modal with task details
        console.log('View task:', taskId);
    }

    completeTask(taskId) {
        if (!confirm('Mark this task as completed?')) return;

        // In production, this would be an API call
        const tasks = JSON.parse(localStorage.getItem('tasks') || '[]');
        const task = tasks.find(t => t.id === taskId);
        if (task) {
            task.completed = true;
            localStorage.setItem('tasks', JSON.stringify(tasks));
            this.loadTasks();
            this.loadProgressData();
            auth.showNotification('Task marked as completed', 'success');
        }
    }

    editProfile() {
        const student = auth.getCurrentUser();
        if (!student) return;

        document.getElementById('editName').value = student.name;
        document.getElementById('editEmail').value = student.email;
        
        const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
        modal.show();
    }

    saveProfile() {
        const name = document.getElementById('editName').value;
        const email = document.getElementById('editEmail').value;
        const password = document.getElementById('editPassword').value;
        const confirmPassword = document.getElementById('editConfirmPassword').value;

        if (password && password !== confirmPassword) {
            auth.showNotification('Passwords do not match', 'error');
            return;
        }

        // In production, this would be an API call
        const students = JSON.parse(localStorage.getItem('students') || '[]');
        const student = students.find(s => s.id === auth.getCurrentUser().id);
        if (student) {
            student.fullName = name;
            student.email = email;
            if (password) {
                student.password = password;
            }
            localStorage.setItem('students', JSON.stringify(students));
            
            // Update current user data
            auth.userData.name = name;
            auth.userData.email = email;
            localStorage.setItem('userData', JSON.stringify(auth.userData));
            
            this.loadStudentData();
            auth.showNotification('Profile updated successfully', 'success');
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
            modal.hide();
        }
    }

    getPriorityColor(priority) {
        switch (priority.toLowerCase()) {
            case 'high':
                return 'danger';
            case 'medium':
                return 'warning';
            case 'low':
                return 'success';
            default:
                return 'secondary';
        }
    }

    getStatusColor(status) {
        switch (status.toLowerCase()) {
            case 'submitted':
                return 'info';
            case 'graded':
                return 'success';
            case 'late':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    getAttendanceColor(status) {
        switch (status.toLowerCase()) {
            case 'present':
                return 'success';
            case 'absent':
                return 'danger';
            case 'late':
                return 'warning';
            case 'excused':
                return 'info';
            default:
                return 'secondary';
        }
    }

    isUpcoming(date) {
        const today = new Date();
        const dueDate = new Date(date);
        const diffTime = dueDate - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays >= 0 && diffDays <= 7;
    }

    handleAttendanceStatusChange(detail) {
        // Update attendance data immediately when status changes
        this.loadAttendanceData();
        
        // Show notification to user
        const status = detail.isOpen ? 'opened' : 'closed';
        auth.showNotification(`Attendance has been ${status}`, detail.isOpen ? 'success' : 'warning');
        
        // Add visual feedback
        this.showAttendanceStatusAnimation(detail.isOpen);
    }

    showAttendanceStatusAnimation(isOpen) {
        const statusElement = document.getElementById('studentAttendanceStatus');
        if (statusElement) {
            // Add animation class
            statusElement.classList.add('animate__animated', 'animate__pulse');
            
            // Remove animation class after animation completes
            setTimeout(() => {
                statusElement.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        }
    }
}

// Initialize student dashboard
const studentDashboard = new StudentDashboard(); 