// Students management module
class StudentsManager {
    constructor() {
        this.students = [];
        this.init();
    }

    init() {
        // Load students from storage
        this.loadStudents();

        // Add event listeners
        document.getElementById('addStudentForm').addEventListener('submit', (e) => this.handleAddStudent(e));
        
        // Initialize search functionality
        const searchInput = document.querySelector('#studentsSection input[type="text"]');
        searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
    }

    loadStudents() {
        // In production, this would be an API call
        const storedStudents = localStorage.getItem('students');
        this.students = storedStudents ? JSON.parse(storedStudents) : [];
        this.renderStudents();
    }

    saveStudents() {
        // In production, this would be an API call
        localStorage.setItem('students', JSON.stringify(this.students));
    }

    renderStudents(students = this.students) {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '';

        students.forEach(student => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${student.id}</td>
                <td>${student.fullName}</td>
                <td>${student.email}</td>
                <td>
                    <span class="badge bg-${student.active ? 'success' : 'danger'}">
                        ${student.active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="studentsManager.editStudent('${student.id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="studentsManager.deleteStudent('${student.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-${student.active ? 'warning' : 'success'}" 
                            onclick="studentsManager.toggleStudentStatus('${student.id}')">
                        <i class="fas fa-${student.active ? 'ban' : 'check'}"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async handleAddStudent(event) {
        event.preventDefault();
        
        if (!auth.checkAuth()) return;

        const form = event.target;
        const formData = new FormData(form);

        // Validate passwords match
        if (formData.get('password') !== formData.get('confirmPassword')) {
            auth.showNotification('Passwords do not match', 'error');
            return;
        }

        try {
            const student = {
                id: this.generateStudentId(),
                fullName: formData.get('fullName'),
                email: formData.get('email'),
                password: formData.get('password'), // In production, use proper password hashing
                active: true,
                createdAt: new Date().toISOString()
            };

            // In production, this would be an API call
            this.students.push(student);
            this.saveStudents();
            this.renderStudents();

            // Close modal and reset form
            const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
            modal.hide();
            form.reset();

            auth.showNotification('Student added successfully', 'success');
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    editStudent(id) {
        if (!auth.checkAuth()) return;

        const student = this.students.find(s => s.id === id);
        if (!student) {
            auth.showNotification('Student not found', 'error');
            return;
        }

        // In production, this would open an edit modal
        console.log('Edit student:', student);
    }

    async deleteStudent(id) {
        if (!auth.checkAuth()) return;

        if (!confirm('Are you sure you want to delete this student?')) {
            return;
        }

        try {
            // In production, this would be an API call
            this.students = this.students.filter(s => s.id !== id);
            this.saveStudents();
            this.renderStudents();

            auth.showNotification('Student deleted successfully', 'success');
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    async toggleStudentStatus(id) {
        if (!auth.checkAuth()) return;

        try {
            const student = this.students.find(s => s.id === id);
            if (!student) {
                throw new Error('Student not found');
            }

            // In production, this would be an API call
            student.active = !student.active;
            this.saveStudents();
            this.renderStudents();

            auth.showNotification(
                `Student ${student.active ? 'activated' : 'deactivated'} successfully`,
                'success'
            );
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    handleSearch(query) {
        const filteredStudents = this.students.filter(student => 
            student.fullName.toLowerCase().includes(query.toLowerCase()) ||
            student.email.toLowerCase().includes(query.toLowerCase()) ||
            student.id.toLowerCase().includes(query.toLowerCase())
        );
        this.renderStudents(filteredStudents);
    }

    generateStudentId() {
        // Generate a unique student ID
        const timestamp = Date.now().toString(36);
        const random = Math.random().toString(36).substr(2, 5);
        return `STU-${timestamp}-${random}`.toUpperCase();
    }

    // Get student by ID
    getStudent(id) {
        return this.students.find(s => s.id === id);
    }

    // Get all students
    getAllStudents() {
        return this.students;
    }

    // Get active students
    getActiveStudents() {
        return this.students.filter(s => s.active);
    }
}

// Initialize students manager
const studentsManager = new StudentsManager(); 