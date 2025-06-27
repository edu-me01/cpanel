/**
 * Lecture Management System
 * Handles lecture display and access control based on attendance
 */
class LectureManager {
    constructor() {
        this.lectures = [];
        this.currentUser = null;
        this.init();
    }

    init() {
        this.loadCurrentUser();
        this.loadLectures();
        this.setupEventListeners();
        this.checkAccessRequirements();
    }

    loadCurrentUser() {
        const userData = sessionStorage.getItem('userData');
        if (userData) {
            this.currentUser = JSON.parse(userData);
        }
    }

    setupEventListeners() {
        // Admin lecture form
        const addLectureForm = document.getElementById('addLectureForm');
        if (addLectureForm) {
            addLectureForm.addEventListener('submit', (e) => this.handleAddLecture(e));
        }

        // Lecture filters
        const categoryFilter = document.getElementById('lectureCategoryFilter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => this.filterLectures());
        }

        const dateFilter = document.getElementById('lectureDateFilter');
        if (dateFilter) {
            dateFilter.addEventListener('change', () => this.filterLectures());
        }

        // Set default date to today
        const lectureDateInput = document.getElementById('lectureDate');
        if (lectureDateInput) {
            const today = new Date().toISOString().split('T')[0];
            lectureDateInput.value = today;
        }
    }

    async checkAccessRequirements() {
        if (this.currentUser && this.currentUser.type === 'student') {
            try {
                // Check if daily feedback is required
                const dailyFeedbackResponse = await fetch('/api/feedbacks/check-daily', {
                    headers: {
                        'Authorization': `Bearer ${sessionStorage.getItem('token')}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (dailyFeedbackResponse.ok) {
                    const data = await dailyFeedbackResponse.json();
                    if (data.required) {
                        this.showFeedbackModal('daily');
                        return;
                    }
                }

                // Check if final feedback is required
                const finalFeedbackResponse = await fetch('/api/feedbacks/check-final', {
                    headers: {
                        'Authorization': `Bearer ${sessionStorage.getItem('token')}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (finalFeedbackResponse.ok) {
                    const data = await finalFeedbackResponse.json();
                    if (data.required) {
                        this.showFeedbackModal('final');
                        return;
                    }
                }
            } catch (error) {
                console.error('Error checking feedback requirements:', error);
            }
        }
    }

    async loadLectures() {
        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                console.error('No authentication token found');
                return;
            }

            const response = await fetch('/api/lectures', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                this.lectures = await response.json();
                this.displayLectures();
                this.updateLectureStats();
            } else if (response.status === 403) {
                this.showAccessDeniedMessage();
            } else {
                const errorData = await response.json();
                this.showNotification(errorData.message || 'Failed to load lectures', 'error');
            }
        } catch (error) {
            console.error('Error loading lectures:', error);
            this.showNotification('Network error while loading lectures', 'error');
        }
    }

    async handleAddLecture(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const lectureData = {
            title: formData.get('title').trim(),
            content: formData.get('content').trim(),
            category: formData.get('category'),
            date: formData.get('date'),
            duration: parseInt(formData.get('duration')) || 60,
            videoUrl: formData.get('videoUrl') || null
        };

        // Validation
        if (!lectureData.title) {
            this.showNotification('Lecture title is required', 'error');
            return;
        }

        if (!lectureData.content) {
            this.showNotification('Lecture content is required', 'error');
            return;
        }

        if (!lectureData.date) {
            this.showNotification('Lecture date is required', 'error');
            return;
        }

        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                this.showNotification('Please login again', 'error');
                return;
            }

            const response = await fetch('/api/lectures', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(lectureData)
            });

            if (response.ok) {
                const newLecture = await response.json();
                this.lectures.push(newLecture);
                this.displayLectures();
                this.updateLectureStats();
                
                // Reset form
                event.target.reset();
                
                // Close modal if exists
                const modal = bootstrap.Modal.getInstance(document.getElementById('addLectureModal'));
                if (modal) {
                    modal.hide();
                }
                
                this.showNotification('Lecture created successfully', 'success');
            } else {
                const errorData = await response.json();
                this.showNotification(errorData.message || 'Failed to create lecture', 'error');
            }
        } catch (error) {
            console.error('Error creating lecture:', error);
            this.showNotification('Network error while creating lecture', 'error');
        }
    }

    displayLectures() {
        const container = document.getElementById('lecturesContainer');
        if (!container) return;

        const filteredLectures = this.getFilteredLectures();
        
        if (filteredLectures.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No lectures found</h5>
                    <p class="text-muted">${this.currentUser?.type === 'admin' ? 'Create your first lecture using the "Add Lecture" button' : 'No lectures available for today'}</p>
                </div>
            `;
            return;
        }

        container.innerHTML = filteredLectures.map(lecture => `
            <div class="card mb-3 lecture-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">${this.escapeHtml(lecture.title)}</h5>
                    <div>
                        <span class="badge bg-primary me-2">${lecture.category}</span>
                        <span class="badge bg-secondary">${lecture.duration} min</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">${this.escapeHtml(lecture.content.substring(0, 200))}${lecture.content.length > 200 ? '...' : ''}</p>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                ${this.formatDate(lecture.date)}
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            ${this.currentUser?.type === 'admin' ? `
                                <button class="btn btn-sm btn-outline-primary me-1" onclick="lectureManager.viewLecture('${lecture.id}')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="btn btn-sm btn-outline-secondary me-1" onclick="lectureManager.editLecture('${lecture.id}')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="lectureManager.deleteLecture('${lecture.id}')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            ` : `
                                <button class="btn btn-sm btn-primary" onclick="lectureManager.viewLecture('${lecture.id}')">
                                    <i class="fas fa-play"></i> Start Lecture
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    getFilteredLectures() {
        let filtered = [...this.lectures];

        // Apply category filter
        const categoryFilter = document.getElementById('lectureCategoryFilter')?.value;
        if (categoryFilter && categoryFilter !== 'all') {
            filtered = filtered.filter(lecture => lecture.category === categoryFilter);
        }

        // Apply date filter
        const dateFilter = document.getElementById('lectureDateFilter')?.value;
        if (dateFilter) {
            filtered = filtered.filter(lecture => lecture.date === dateFilter);
        }

        return filtered;
    }

    filterLectures() {
        this.displayLectures();
    }

    updateLectureStats() {
        const totalLectures = this.lectures.length;
        const todayLectures = this.lectures.filter(lecture => lecture.date === new Date().toISOString().split('T')[0]).length;
        const categories = [...new Set(this.lectures.map(lecture => lecture.category))];

        // Update stats display
        const statsContainer = document.getElementById('lectureStats');
        if (statsContainer) {
            statsContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Lectures</h5>
                                <h3>${totalLectures}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Today's Lectures</h5>
                                <h3>${todayLectures}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Categories</h5>
                                <h3>${categories.length}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    showAccessDeniedMessage() {
        const container = document.getElementById('lecturesContainer');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-lock fa-3x text-warning mb-3"></i>
                    <h5 class="text-warning">Access Denied</h5>
                    <p class="text-muted">You must attend today's session before accessing lectures.</p>
                    <a href="attendance.html" class="btn btn-primary">
                        <i class="fas fa-clock"></i> Mark Attendance
                    </a>
                </div>
            `;
        }
    }

    showFeedbackModal(type) {
        const modal = document.getElementById('feedbackModal');
        if (modal) {
            const modalTitle = modal.querySelector('.modal-title');
            const modalBody = modal.querySelector('.modal-body');
            
            modalTitle.textContent = type === 'daily' ? 'Daily Feedback Required' : 'Final Training Feedback';
            
            // Load feedback questions
            fetch(`/api/feedbacks/questions?type=${type}`, {
                headers: {
                    'Authorization': `Bearer ${sessionStorage.getItem('token')}`,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(questions => {
                modalBody.innerHTML = this.generateFeedbackForm(questions, type);
                
                const bootstrapModal = new bootstrap.Modal(modal);
                bootstrapModal.show();
            })
            .catch(error => {
                console.error('Error loading feedback questions:', error);
            });
        }
    }

    generateFeedbackForm(questions, type) {
        let formHtml = `<form id="feedbackForm" data-type="${type}">`;
        
        Object.entries(questions).forEach(([key, question]) => {
            formHtml += `
                <div class="mb-3">
                    <label class="form-label">${question}</label>
                    ${this.getInputField(key, question)}
                </div>
            `;
        });
        
        formHtml += `
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </div>
        </form>
        `;
        
        return formHtml;
    }

    getInputField(key, question) {
        if (question.includes('(1-5)')) {
            return `
                <select class="form-select" name="${key}" required>
                    <option value="">Select rating</option>
                    <option value="1">1 - Poor</option>
                    <option value="2">2 - Fair</option>
                    <option value="3">3 - Good</option>
                    <option value="4">4 - Very Good</option>
                    <option value="5">5 - Excellent</option>
                </select>
            `;
        } else if (question.includes('(Yes/No)')) {
            return `
                <select class="form-select" name="${key}" required>
                    <option value="">Select answer</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            `;
        } else {
            return `<textarea class="form-control" name="${key}" rows="3" required></textarea>`;
        }
    }

    async viewLecture(lectureId) {
        try {
            const token = sessionStorage.getItem('token');
            const response = await fetch(`/api/lectures/${lectureId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const lecture = await response.json();
                this.showLectureModal(lecture);
            } else {
                const errorData = await response.json();
                this.showNotification(errorData.message || 'Failed to load lecture', 'error');
            }
        } catch (error) {
            console.error('Error viewing lecture:', error);
            this.showNotification('Network error while loading lecture', 'error');
        }
    }

    showLectureModal(lecture) {
        const modal = document.getElementById('lectureModal');
        if (modal) {
            const modalTitle = modal.querySelector('.modal-title');
            const modalBody = modal.querySelector('.modal-body');
            
            modalTitle.textContent = lecture.title;
            
            modalBody.innerHTML = `
                <div class="lecture-content">
                    <div class="mb-3">
                        <strong>Category:</strong> ${lecture.category}<br>
                        <strong>Date:</strong> ${this.formatDate(lecture.date)}<br>
                        <strong>Duration:</strong> ${lecture.duration} minutes
                    </div>
                    <div class="mb-3">
                        <h6>Content:</h6>
                        <div class="border p-3 bg-light">
                            ${this.escapeHtml(lecture.content)}
                        </div>
                    </div>
                    ${lecture.videoUrl ? `
                        <div class="mb-3">
                            <h6>Video:</h6>
                            <div class="ratio ratio-16x9">
                                <iframe src="${lecture.videoUrl}" allowfullscreen></iframe>
                            </div>
                        </div>
                    ` : ''}
                    ${lecture.attachments && lecture.attachments.length > 0 ? `
                        <div class="mb-3">
                            <h6>Attachments:</h6>
                            <ul class="list-unstyled">
                                ${lecture.attachments.map(attachment => `
                                    <li><a href="${attachment.url}" target="_blank">${attachment.name}</a></li>
                                `).join('')}
                            </ul>
                        </div>
                    ` : ''}
                </div>
            `;
            
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
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

// Initialize lecture manager
const lectureManager = new LectureManager();
window.lectureManager = lectureManager; 