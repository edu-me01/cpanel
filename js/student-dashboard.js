// Student Dashboard module
class StudentDashboard {
    constructor() {
        // Don't auto-initialize, will be called manually
    }

    init() {
        // Check if user is authenticated and is a student
        const userType = localStorage.getItem('userType');
        const userEmail = localStorage.getItem('userEmail');
        
        if (!userType || !userEmail || userType !== 'student') {
            window.location.href = 'login.html';
            return;
        }

        // Load student data
        this.loadStudentData();
        
        // Add event listeners
        this.addEventListeners();
    }

    loadStudentData() {
        const userEmail = localStorage.getItem('userEmail');
        const userType = localStorage.getItem('userType');
        
        if (!userEmail || userType !== 'student') return;

        // Create student data from localStorage
        const student = {
            id: 'student-1',
            name: 'Student User',
            email: userEmail
        };

        // Update UI with student info
        const studentNameElement = document.getElementById('studentName');
        const studentEmailElement = document.getElementById('studentEmail');
        
        if (studentNameElement) {
            studentNameElement.textContent = student.name;
        }
        if (studentEmailElement) {
            studentEmailElement.textContent = student.email;
        }

        // Load tasks
        this.loadTasks();
        
        // Load submissions
        this.loadSubmissions();
        
        // Load progress data
        this.loadProgressData();
    }

    loadTasks() {
        // Create sample tasks for demonstration
        const sampleTasks = [
            {
                id: 'task-1',
                title: 'Complete Assignment 1',
                dueDate: '2024-01-15',
                priority: 'high',
                completed: false,
                assignedTo: 'student-1'
            },
            {
                id: 'task-2',
                title: 'Submit Project Report',
                dueDate: '2024-01-20',
                priority: 'medium',
                completed: true,
                assignedTo: 'student-1'
            },
            {
                id: 'task-3',
                title: 'Review Course Materials',
                dueDate: '2024-01-25',
                priority: 'low',
                completed: false,
                assignedTo: 'student-1'
            }
        ];
        
        // Store sample tasks in localStorage if not exists
        if (!localStorage.getItem('tasks')) {
            localStorage.setItem('tasks', JSON.stringify(sampleTasks));
        }
        
        const tasks = JSON.parse(localStorage.getItem('tasks') || '[]');
        const studentTasks = tasks.filter(task => task.assignedTo === 'student-1');
        
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
        const completedTasksElement = document.getElementById('completedTasks');
        const totalTasksElement = document.getElementById('totalTasks');
        const upcomingTasksElement = document.getElementById('upcomingTasks');
        
        if (completedTasksElement) {
            completedTasksElement.textContent = studentTasks.filter(t => t.completed).length;
        }
        if (totalTasksElement) {
            totalTasksElement.textContent = studentTasks.length;
        }
        if (upcomingTasksElement) {
            upcomingTasksElement.textContent = studentTasks.filter(t => !t.completed && this.isUpcoming(t.dueDate)).length;
        }
    }

    loadSubmissions() {
        // Create sample submissions for demonstration
        const sampleSubmissions = [
            {
                id: 'sub-1',
                taskTitle: 'Assignment 1',
                submittedAt: '2024-01-10',
                status: 'graded',
                grade: 85,
                feedback: 'Good work! Well structured.',
                studentId: 'student-1'
            },
            {
                id: 'sub-2',
                taskTitle: 'Project Report',
                submittedAt: '2024-01-15',
                status: 'pending',
                grade: null,
                feedback: null,
                studentId: 'student-1'
            }
        ];
        
        // Store sample submissions in localStorage if not exists
        if (!localStorage.getItem('submissions')) {
            localStorage.setItem('submissions', JSON.stringify(sampleSubmissions));
        }
        
        const submissions = JSON.parse(localStorage.getItem('submissions') || '[]');
        const studentSubmissions = submissions.filter(sub => sub.studentId === 'student-1');
        
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
        // Get tasks and submissions from localStorage
        const tasks = JSON.parse(localStorage.getItem('tasks') || '[]');
        const studentTasks = tasks.filter(task => task.assignedTo === 'student-1');
        const submissions = JSON.parse(localStorage.getItem('submissions') || '[]');
        const studentSubmissions = submissions.filter(sub => sub.studentId === 'student-1');

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
        
        const averageGradeElement = document.getElementById('averageGrade');
        if (averageGradeElement) {
            averageGradeElement.textContent = `${Math.round(averageGrade)}%`;
        }

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

        // Create sample attendance data
        const sampleAttendance = [
            { date: '2024-01-10', status: 'present', studentId: 'student-1' },
            { date: '2024-01-11', status: 'present', studentId: 'student-1' },
            { date: '2024-01-12', status: 'late', studentId: 'student-1' },
            { date: '2024-01-13', status: 'present', studentId: 'student-1' },
            { date: '2024-01-14', status: 'absent', studentId: 'student-1' }
        ];
        
        // Store sample attendance in localStorage if not exists
        if (!localStorage.getItem('attendance')) {
            localStorage.setItem('attendance', JSON.stringify(sampleAttendance));
        }
        
        const attendance = JSON.parse(localStorage.getItem('attendance') || '[]');
        const studentAttendance = attendance.filter(a => a.studentId === 'student-1');
        const attendanceRate = studentAttendance.length > 0
            ? (studentAttendance.filter(a => a.status === 'present').length / studentAttendance.length) * 100
            : 0;
        
        const attendanceRateElement = document.getElementById('attendanceRate');
        if (attendanceRateElement) {
            attendanceRateElement.textContent = `${Math.round(attendanceRate)}%`;
        }

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
        const studentAttendance = attendance.filter(a => a.studentId === 'student-1');

        tbody.innerHTML = '';

        studentAttendance.forEach(record => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${record.date}</td>
                <td>${record.session || 'morning'}</td>
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
        const userEmail = localStorage.getItem('userEmail');
        const userType = localStorage.getItem('userType');
        
        if (!userEmail || userType !== 'student') {
            alert('Please log in to mark attendance');
            return;
        }

        // Check if attendance is open
        const attendanceStatus = localStorage.getItem('attendanceOpen') === 'true';
        if (!attendanceStatus) {
            alert('Attendance is currently closed');
            return;
        }

        const currentDate = new Date().toISOString().split('T')[0];
        const currentSession = localStorage.getItem('currentSession') || 'morning';

        // Check if already marked attendance for today
        const attendance = JSON.parse(localStorage.getItem('attendance') || '[]');
        const existingRecord = attendance.find(a => 
            a.studentId === 'student-1' && 
            a.date === currentDate && 
            a.session === currentSession
        );

        if (existingRecord) {
            alert('You have already marked attendance for this session');
            return;
        }

        // Add new attendance record
        const newRecord = {
            studentId: 'student-1',
            date: currentDate,
            session: currentSession,
            status: status,
            time: new Date().toLocaleTimeString()
        };

        attendance.push(newRecord);
        localStorage.setItem('attendance', JSON.stringify(attendance));

        // Show success message
        alert(`Attendance marked as ${status} successfully!`);

        // Reload attendance data
        this.loadAttendanceData();
        this.loadProgressData();
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

        // Update task in localStorage
        const tasks = JSON.parse(localStorage.getItem('tasks') || '[]');
        const task = tasks.find(t => t.id === taskId);
        if (task) {
            task.completed = true;
            localStorage.setItem('tasks', JSON.stringify(tasks));
            this.loadTasks();
            this.loadProgressData();
            alert('Task marked as completed');
        }
    }

    editProfile() {
        const userEmail = localStorage.getItem('userEmail');
        const userType = localStorage.getItem('userType');
        
        if (!userEmail || userType !== 'student') return;

        document.getElementById('editName').value = 'Student User';
        document.getElementById('editEmail').value = userEmail;
        
        const modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
        modal.show();
    }

    saveProfile() {
        const name = document.getElementById('editName').value;
        const email = document.getElementById('editEmail').value;
        const password = document.getElementById('editPassword').value;
        const confirmPassword = document.getElementById('editConfirmPassword').value;

        if (password && password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }

        // Update localStorage
        localStorage.setItem('userEmail', email);
        
        // Update UI
        const studentNameElement = document.getElementById('studentName');
        const studentEmailElement = document.getElementById('studentEmail');
        
        if (studentNameElement) {
            studentNameElement.textContent = name;
        }
        if (studentEmailElement) {
            studentEmailElement.textContent = email;
        }
        
        alert('Profile updated successfully');
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
        modal.hide();
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
        alert(`Attendance has been ${status}`);
        
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