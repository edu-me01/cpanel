// Attendance management module
class AttendanceManager {
    constructor() {
        this.attendance = [];
        this.init();
    }

    init() {
        // Load attendance records from storage
        this.loadAttendance();

        // Add event listeners
        document.getElementById('markAttendanceForm').addEventListener('submit', (e) => this.handleMarkAttendance(e));
        
        // Initialize search functionality
        const searchInput = document.querySelector('#attendanceSection input[type="text"]');
        searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));

        // Initialize date filter
        const dateFilter = document.querySelector('#attendanceSection input[type="date"]');
        dateFilter.addEventListener('change', (e) => this.handleDateFilter(e.target.value));
    }

    loadAttendance() {
        // In production, this would be an API call
        const storedAttendance = localStorage.getItem('attendance');
        this.attendance = storedAttendance ? JSON.parse(storedAttendance) : [];
        this.renderAttendance();
    }

    saveAttendance() {
        // In production, this would be an API call
        localStorage.setItem('attendance', JSON.stringify(this.attendance));
    }

    renderAttendance(records = this.attendance) {
        const tbody = document.getElementById('attendanceTableBody');
        tbody.innerHTML = '';

        records.forEach(record => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${record.id}</td>
                <td>${record.studentId}</td>
                <td>${record.studentName}</td>
                <td>${record.date}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(record.status)}">
                        ${record.status}
                    </span>
                </td>
                <td>${record.notes || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="attendanceManager.editAttendance('${record.id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="attendanceManager.deleteAttendance('${record.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async handleMarkAttendance(event) {
        event.preventDefault();
        
        if (!auth.checkAuth()) return;

        const form = event.target;
        const formData = new FormData(form);

        try {
            const studentId = formData.get('studentId');
            const student = studentsManager.getStudent(studentId);
            
            if (!student) {
                throw new Error('Student not found');
            }

            const record = {
                id: this.generateAttendanceId(),
                studentId: studentId,
                studentName: student.fullName,
                date: formData.get('date'),
                status: formData.get('status'),
                notes: formData.get('notes'),
                createdAt: new Date().toISOString()
            };

            // In production, this would be an API call
            this.attendance.push(record);
            this.saveAttendance();
            this.renderAttendance();

            // Close modal and reset form
            const modal = bootstrap.Modal.getInstance(document.getElementById('markAttendanceModal'));
            modal.hide();
            form.reset();

            auth.showNotification('Attendance marked successfully', 'success');
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    editAttendance(id) {
        if (!auth.checkAuth()) return;

        const record = this.attendance.find(r => r.id === id);
        if (!record) {
            auth.showNotification('Attendance record not found', 'error');
            return;
        }

        // In production, this would open an edit modal
        console.log('Edit attendance:', record);
    }

    async deleteAttendance(id) {
        if (!auth.checkAuth()) return;

        if (!confirm('Are you sure you want to delete this attendance record?')) {
            return;
        }

        try {
            // In production, this would be an API call
            this.attendance = this.attendance.filter(r => r.id !== id);
            this.saveAttendance();
            this.renderAttendance();

            auth.showNotification('Attendance record deleted successfully', 'success');
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    handleSearch(query) {
        const filteredRecords = this.attendance.filter(record => 
            record.studentName.toLowerCase().includes(query.toLowerCase()) ||
            record.studentId.toLowerCase().includes(query.toLowerCase()) ||
            record.status.toLowerCase().includes(query.toLowerCase())
        );
        this.renderAttendance(filteredRecords);
    }

    handleDateFilter(date) {
        if (!date) {
            this.renderAttendance();
            return;
        }

        const filteredRecords = this.attendance.filter(record => 
            record.date === date
        );
        this.renderAttendance(filteredRecords);
    }

    getStatusColor(status) {
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

    generateAttendanceId() {
        // Generate a unique attendance ID
        const timestamp = Date.now().toString(36);
        const random = Math.random().toString(36).substr(2, 5);
        return `ATT-${timestamp}-${random}`.toUpperCase();
    }

    // Get attendance record by ID
    getAttendanceRecord(id) {
        return this.attendance.find(r => r.id === id);
    }

    // Get all attendance records
    getAllAttendanceRecords() {
        return this.attendance;
    }

    // Get attendance records for a specific student
    getStudentAttendance(studentId) {
        return this.attendance.filter(r => r.studentId === studentId);
    }

    // Get attendance records for a specific date
    getDateAttendance(date) {
        return this.attendance.filter(r => r.date === date);
    }
}

// Initialize attendance manager
const attendanceManager = new AttendanceManager(); 