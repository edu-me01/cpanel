// Attendance Management module
class Attendance {
    constructor() {
        this.isOpen = localStorage.getItem('attendanceOpen') === 'true';
        this.currentDate = new Date().toISOString().split('T')[0];
        this.currentSession = localStorage.getItem('currentSession') || 'morning';
        this.init();
    }

    init() {
        // Set current date in the date input
        const dateInput = document.getElementById('attendanceDate');
        if (dateInput) {
            dateInput.value = this.currentDate;
            dateInput.addEventListener('change', (e) => this.handleDateChange(e.target.value));
        }

        // Set up session change handler
        const sessionSelect = document.getElementById('attendanceSession');
        if (sessionSelect) {
            sessionSelect.addEventListener('change', (e) => this.handleSessionChange(e.target.value));
        }

        // Update status display
        this.updateStatus();

        // Load initial attendance data
        this.loadAttendance();
    }

    openAttendance() {
        if (!auth.checkAdminAuth()) return;

        this.isOpen = true;
        this.updateStatus();
        this.loadAttendance();
        localStorage.setItem('attendanceOpen', 'true');
        localStorage.setItem('currentSession', this.currentSession);
        
        // Dispatch custom event for real-time updates
        window.dispatchEvent(new CustomEvent('attendanceStatusChanged', {
            detail: { isOpen: true, session: this.currentSession }
        }));
        
        auth.showNotification('Attendance is now open', 'success');
    }

    closeAttendance() {
        if (!auth.checkAdminAuth()) return;

        this.isOpen = false;
        this.updateStatus();
        this.loadAttendance();
        localStorage.setItem('attendanceOpen', 'false');
        
        // Dispatch custom event for real-time updates
        window.dispatchEvent(new CustomEvent('attendanceStatusChanged', {
            detail: { isOpen: false, session: this.currentSession }
        }));
        
        auth.showNotification('Attendance is now closed', 'success');
    }

    updateStatus() {
        const statusElement = document.getElementById('attendanceStatus');
        if (statusElement) {
            statusElement.className = `badge bg-${this.isOpen ? 'success' : 'danger'}`;
            statusElement.textContent = this.isOpen ? 'Open' : 'Closed';
        }
    }

    handleDateChange(date) {
        this.currentDate = date;
        this.loadAttendance();
    }

    handleSessionChange(session) {
        this.currentSession = session;
        this.loadAttendance();
        localStorage.setItem('currentSession', session);
    }

    loadAttendance() {
        const tbody = document.getElementById('attendanceTableBody');
        if (!tbody) return;

        // Get all students
        const students = JSON.parse(localStorage.getItem('students') || '[]');
        
        // Get attendance records for current date and session
        const attendance = JSON.parse(localStorage.getItem('attendance') || '[]');
        const todayAttendance = attendance.filter(a => 
            a.date === this.currentDate && 
            a.session === this.currentSession
        );

        tbody.innerHTML = '';

        students.forEach(student => {
            const studentAttendance = todayAttendance.find(a => a.studentId === student.id);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${student.fullName}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(studentAttendance?.status || 'absent')}">
                        ${studentAttendance?.status || 'Absent'}
                    </span>
                </td>
                <td>${studentAttendance?.time || '-'}</td>
                <td>
                    ${this.isOpen ? `
                        <button class="btn btn-sm btn-success" onclick="attendance.markPresent('${student.id}')">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="attendance.markLate('${student.id}')">
                            <i class="fas fa-clock"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="attendance.markAbsent('${student.id}')">
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    markPresent(studentId) {
        if (!this.isOpen || !auth.checkAdminAuth()) return;

        this.updateAttendance(studentId, 'present');
    }

    markLate(studentId) {
        if (!this.isOpen || !auth.checkAdminAuth()) return;

        this.updateAttendance(studentId, 'late');
    }

    markAbsent(studentId) {
        if (!this.isOpen || !auth.checkAdminAuth()) return;

        this.updateAttendance(studentId, 'absent');
    }

    updateAttendance(studentId, status) {
        const attendance = JSON.parse(localStorage.getItem('attendance') || '[]');
        
        // Remove any existing record for this student on this date and session
        const filteredAttendance = attendance.filter(a => 
            !(a.studentId === studentId && 
              a.date === this.currentDate && 
              a.session === this.currentSession)
        );

        // Add new attendance record
        filteredAttendance.push({
            studentId,
            date: this.currentDate,
            session: this.currentSession,
            status,
            time: new Date().toLocaleTimeString()
        });

        localStorage.setItem('attendance', JSON.stringify(filteredAttendance));
        this.loadAttendance();
        auth.showNotification('Attendance updated', 'success');
    }

    getStatusColor(status) {
        switch (status.toLowerCase()) {
            case 'present':
                return 'success';
            case 'late':
                return 'warning';
            case 'absent':
                return 'danger';
            default:
                return 'secondary';
        }
    }
}

// Initialize attendance
const attendance = new Attendance(); 