/**
 * Feedback Management System
 * Handles feedback form submission and validation
 */
class FeedbackManager {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    init() {
        this.loadCurrentUser();
        this.setupEventListeners();
    }

    loadCurrentUser() {
        const userData = sessionStorage.getItem('userData');
        if (userData) {
            this.currentUser = JSON.parse(userData);
        }
    }

    setupEventListeners() {
        // Handle feedback form submission
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'feedbackForm') {
                e.preventDefault();
                this.handleFeedbackSubmission(e);
            }
        });
    }

    async handleFeedbackSubmission(event) {
        const form = event.target;
        const formData = new FormData(form);
        const feedbackType = form.dataset.type;
        
        // Convert form data to answers object
        const answers = {};
        for (let [key, value] of formData.entries()) {
            answers[key] = value;
        }

        // Validation
        const requiredFields = Object.keys(answers);
        for (let field of requiredFields) {
            if (!answers[field] || answers[field].trim() === '') {
                this.showNotification(`Please fill in all required fields`, 'error');
                return;
            }
        }

        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                this.showNotification('Please login again', 'error');
                return;
            }

            const response = await fetch('/api/feedbacks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    type: feedbackType,
                    answers: answers,
                    studentName: this.currentUser?.name || ''
                })
            });

            if (response.ok) {
                const feedback = await response.json();
                this.showNotification('Feedback submitted successfully!', 'success');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('feedbackModal'));
                if (modal) {
                    modal.hide();
                }

                // Reset form
                form.reset();

                // If this was a final feedback, redirect to completion page
                if (feedbackType === 'final') {
                    setTimeout(() => {
                        window.location.href = 'training-complete.html';
                    }, 2000);
                }
            } else {
                const errorData = await response.json();
                this.showNotification(errorData.message || 'Failed to submit feedback', 'error');
            }
        } catch (error) {
            console.error('Error submitting feedback:', error);
            this.showNotification('Network error while submitting feedback', 'error');
        }
    }

    async loadFeedbackQuestions(type) {
        try {
            const token = sessionStorage.getItem('token');
            const response = await fetch(`/api/feedbacks/questions?type=${type}`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                return await response.json();
            } else {
                throw new Error('Failed to load feedback questions');
            }
        } catch (error) {
            console.error('Error loading feedback questions:', error);
            return {};
        }
    }

    generateFeedbackForm(questions, type) {
        let formHtml = `
            <form id="feedbackForm" data-type="${type}">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please provide your feedback to continue. This helps us improve the training experience.
                </div>
        `;
        
        Object.entries(questions).forEach(([key, question]) => {
            formHtml += `
                <div class="mb-3">
                    <label class="form-label fw-bold">${question}</label>
                    ${this.getInputField(key, question)}
                </div>
            `;
        });
        
        formHtml += `
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                </button>
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

    async showFeedbackModal(type) {
        const modal = document.getElementById('feedbackModal');
        if (!modal) return;

        const modalTitle = modal.querySelector('.modal-title');
        const modalBody = modal.querySelector('.modal-body');
        
        modalTitle.textContent = type === 'daily' ? 'Daily Feedback Required' : 'Final Training Feedback';
        
        // Show loading state
        modalBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading feedback form...</p>
            </div>
        `;
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        try {
            // Load feedback questions
            const questions = await this.loadFeedbackQuestions(type);
            if (Object.keys(questions).length > 0) {
                modalBody.innerHTML = this.generateFeedbackForm(questions, type);
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Unable to load feedback questions. Please try again later.
                    </div>
                `;
            }
        } catch (error) {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    Error loading feedback form. Please refresh the page and try again.
                </div>
            `;
        }
    }

    async checkFeedbackRequirements() {
        if (!this.currentUser) return;

        try {
            const token = sessionStorage.getItem('token');
            
            // Check daily feedback requirement
            const dailyResponse = await fetch('/api/feedbacks/check-daily', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (dailyResponse.ok) {
                const dailyData = await dailyResponse.json();
                if (dailyData.required) {
                    this.showFeedbackModal('daily');
                    return;
                }
            }

            // Check final feedback requirement
            const finalResponse = await fetch('/api/feedbacks/check-final', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (finalResponse.ok) {
                const finalData = await finalResponse.json();
                if (finalData.required) {
                    this.showFeedbackModal('final');
                    return;
                }
            }
        } catch (error) {
            console.error('Error checking feedback requirements:', error);
        }
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

// Initialize feedback manager
const feedbackManager = new FeedbackManager();
window.feedbackManager = feedbackManager; 