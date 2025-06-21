// Student Registration Management module
class Registration {
    constructor() {
        this.init();
    }

    init() {
        this.loadRegistrationRequests();
    }

    loadRegistrationRequests() {
        const tbody = document.getElementById('registrationTableBody');
        if (!tbody) return;

        const requests = JSON.parse(localStorage.getItem('registrationRequests') || '[]');
        
        tbody.innerHTML = '';

        requests.forEach(request => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${request.fullName}</td>
                <td>${request.email}</td>
                <td>${request.phone}</td>
                <td>${request.track}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(request.status)}">
                        ${request.status}
                    </span>
                </td>
                <td>
                    ${request.status === 'pending' ? `
                        <button class="btn btn-sm btn-success" onclick="registration.approveRequest('${request.id}')">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="registration.rejectRequest('${request.id}')">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-info" onclick="registration.viewDetails('${request.id}')">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    approveRequest(requestId) {
        if (!auth.checkAdminAuth()) return;

        const requests = JSON.parse(localStorage.getItem('registrationRequests') || '[]');
        const request = requests.find(r => r.id === requestId);
        
        if (!request) return;

        // Update request status
        request.status = 'approved';
        localStorage.setItem('registrationRequests', JSON.stringify(requests));

        // Create student account
        const students = JSON.parse(localStorage.getItem('students') || '[]');
        const newStudent = {
            id: 'STU' + Date.now(),
            fullName: request.fullName,
            email: request.email,
            phone: request.phone,
            track: request.track,
            password: request.password,
            status: 'active',
            registrationDate: new Date().toISOString()
        };
        students.push(newStudent);
        localStorage.setItem('students', JSON.stringify(students));

        this.loadRegistrationRequests();
        auth.showNotification('Registration request approved', 'success');
    }

    rejectRequest(requestId) {
        if (!auth.checkAdminAuth()) return;

        const requests = JSON.parse(localStorage.getItem('registrationRequests') || '[]');
        const request = requests.find(r => r.id === requestId);
        
        if (!request) return;

        request.status = 'rejected';
        localStorage.setItem('registrationRequests', JSON.stringify(requests));

        this.loadRegistrationRequests();
        auth.showNotification('Registration request rejected', 'success');
    }

    viewDetails(requestId) {
        if (!auth.checkAdminAuth()) return;

        const requests = JSON.parse(localStorage.getItem('registrationRequests') || '[]');
        const request = requests.find(r => r.id === requestId);
        
        if (!request) return;

        // Show request details in a modal
        const modal = new bootstrap.Modal(document.getElementById('registrationDetailsModal'));
        document.getElementById('requestName').textContent = request.fullName;
        document.getElementById('requestEmail').textContent = request.email;
        document.getElementById('requestPhone').textContent = request.phone;
        document.getElementById('requestTrack').textContent = request.track;
        document.getElementById('requestStatus').textContent = request.status;
        document.getElementById('requestDate').textContent = new Date(request.date).toLocaleDateString();
        document.getElementById('requestNotes').textContent = request.notes || 'No additional notes';
        
        modal.show();
    }

    getStatusColor(status) {
        switch (status.toLowerCase()) {
            case 'approved':
                return 'success';
            case 'rejected':
                return 'danger';
            case 'pending':
                return 'warning';
            default:
                return 'secondary';
        }
    }
}

// Initialize registration
const registration = new Registration(); 