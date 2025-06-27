/**
 * Tasks Management System
 * Handles task creation, display, and management for both admin and student views
 * Now uses PHP backend API instead of Node.js
 */
class TasksManager {
    constructor() {
        this.tasks = [];
        this.currentUser = null;
        this.apiBaseUrl = '/api/tasks';
        this.init();
    }

    init() {
        this.loadCurrentUser();
        this.loadTasks();
        this.setupEventListeners();
        this.setupRealTimeUpdates();
    }

    loadCurrentUser() {
        const userData = sessionStorage.getItem('userData');
        if (userData) {
            this.currentUser = JSON.parse(userData);
        }
    }

    setupEventListeners() {
        // Admin task form
        const addTaskForm = document.getElementById('addTaskForm');
        if (addTaskForm) {
            addTaskForm.addEventListener('submit', (e) => this.handleAddTask(e));
        }

        // Edit task form
        const editTaskForm = document.getElementById('editTaskForm');
        if (editTaskForm) {
            editTaskForm.addEventListener('submit', (e) => this.handleEditTask(e));
        }

        // Task search and filters
        const taskSearch = document.getElementById('taskSearch');
        if (taskSearch) {
            taskSearch.addEventListener('input', (e) => this.filterTasks(e.target.value));
        }

        const taskStatusFilter = document.getElementById('taskStatusFilter');
        if (taskStatusFilter) {
            taskStatusFilter.addEventListener('change', (e) => this.filterTasks());
        }

        const taskPriorityFilter = document.getElementById('taskPriorityFilter');
        if (taskPriorityFilter) {
            taskPriorityFilter.addEventListener('change', (e) => this.filterTasks());
        }

        // Set default due date to today
        const dueDateInput = document.getElementById('dueDate');
        if (dueDateInput) {
            const today = new Date().toISOString().split('T')[0];
            dueDateInput.min = today;
        }
    }

    setupRealTimeUpdates() {
        // Refresh tasks every 30 seconds for real-time updates
        setInterval(() => {
            this.loadTasks();
        }, 30000);
    }

    async loadTasks() {
        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                console.error('No authentication token found');
                return;
            }

            const response = await fetch(this.apiBaseUrl, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                this.tasks = await response.json();
                console.log('Loaded tasks:', this.tasks);
                this.displayTasks();
                this.updateTaskStats();
            } else if (response.status === 401) {
                console.error('Authentication failed');
                this.showNotification('Please login again', 'error');
                // Redirect to login
                window.location.href = 'index.html';
            } else {
                const errorData = await response.json();
                console.error('Failed to load tasks from server:', errorData);
                this.showNotification(errorData.message || 'Failed to load tasks', 'error');
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
            this.showNotification('Network error while loading tasks', 'error');
        }
    }

    async handleAddTask(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const taskData = {
            title: formData.get('title').trim(),
            description: formData.get('description').trim(),
            priority: formData.get('priority'),
            dueDate: formData.get('dueDate'),
            dueTime: formData.get('dueTime'),
            createdBy: this.currentUser.id,
            createdByName: this.currentUser.name
        };

        // Validation
        if (!taskData.title) {
            this.showNotification('Task title is required', 'error');
            return;
        }

        if (!taskData.dueDate) {
            this.showNotification('Due date is required', 'error');
            return;
        }

        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                this.showNotification('Please login again', 'error');
                return;
            }

            console.log('Sending task data:', taskData);

            const response = await fetch(this.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(taskData)
            });

            if (response.ok) {
                const newTask = await response.json();
                console.log('Task created successfully:', newTask);
                
                // Add to local array and refresh display
                this.tasks.push(newTask);
                this.displayTasks();
                this.updateTaskStats();
                
                // Reset form
                event.target.reset();
                
                // Close modal if exists
                const modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
                if (modal) {
                    modal.hide();
                }
                
                this.showNotification('Task created successfully', 'success');
            } else {
                const errorData = await response.json();
                console.error('Failed to create task:', errorData);
                this.showNotification(errorData.message || 'Failed to create task', 'error');
            }
        } catch (error) {
            console.error('Error creating task:', error);
            this.showNotification('Network error while creating task', 'error');
        }
    }

    async handleEditTask(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const taskId = formData.get('taskId');
        const taskData = {
            title: formData.get('title').trim(),
            description: formData.get('description').trim(),
            priority: formData.get('priority'),
            dueDate: formData.get('dueDate'),
            dueTime: formData.get('dueTime'),
            status: formData.get('status')
        };

        // Validation
        if (!taskData.title) {
            this.showNotification('Task title is required', 'error');
            return;
        }

        if (!taskData.dueDate) {
            this.showNotification('Due date is required', 'error');
            return;
        }

        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                this.showNotification('Please login again', 'error');
                return;
            }

            const response = await fetch(`${this.apiBaseUrl}/${taskId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(taskData)
            });

            if (response.ok) {
                const updatedTask = await response.json();
                console.log('Task updated successfully:', updatedTask);
                
                // Update in local array
                const index = this.tasks.findIndex(task => task.id === taskId);
                if (index !== -1) {
                    this.tasks[index] = updatedTask;
                }
                
                this.displayTasks();
                this.updateTaskStats();
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
                if (modal) {
                    modal.hide();
                }
                
                this.showNotification('Task updated successfully', 'success');
            } else {
                const errorData = await response.json();
                console.error('Failed to update task:', errorData);
                this.showNotification(errorData.message || 'Failed to update task', 'error');
            }
        } catch (error) {
            console.error('Error updating task:', error);
            this.showNotification('Network error while updating task', 'error');
        }
    }

    displayTasks() {
        const tableBody = document.getElementById('tasksTableBody');
        if (!tableBody) return;

        tableBody.innerHTML = '';

        if (this.currentUser && this.currentUser.type === 'admin') {
            this.displayAdminTasks(tableBody);
        } else {
            this.displayStudentTasks(tableBody);
        }
    }

    displayAdminTasks(tableBody) {
        const filteredTasks = this.getFilteredTasks();
        
        if (filteredTasks.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center">No tasks found</td></tr>';
            return;
        }

        filteredTasks.forEach(task => {
            const row = document.createElement('tr');
            row.className = this.isOverdue(task) ? 'table-warning' : '';
            
            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-${this.getPriorityColor(task.priority)} me-2">${task.priority}</span>
                        <strong>${this.escapeHtml(task.title)}</strong>
                    </div>
                </td>
                <td>${this.escapeHtml(task.description || '')}</td>
                <td>${this.formatDate(task.dueDate)}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(task.status)}">${task.status}</span>
                </td>
                <td>${this.escapeHtml(task.createdByName || 'Admin')}</td>
                <td>${task.assignedTo ? 'Assigned' : 'Unassigned'}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary" onclick="tasksManager.viewTaskDetails('${task.id}')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="tasksManager.editTask('${task.id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="tasksManager.deleteTask('${task.id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }

    displayStudentTasks(tableBody) {
        const filteredTasks = this.getFilteredTasks();
        
        if (filteredTasks.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No tasks assigned</td></tr>';
            return;
        }

        filteredTasks.forEach(task => {
            const row = document.createElement('tr');
            row.className = this.isOverdue(task) ? 'table-warning' : '';
            
            row.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-${this.getPriorityColor(task.priority)} me-2">${task.priority}</span>
                        <strong>${this.escapeHtml(task.title)}</strong>
                    </div>
                </td>
                <td>${this.escapeHtml(task.description || '')}</td>
                <td>${this.formatDate(task.dueDate)}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(task.status)}">${task.status}</span>
                </td>
                <td>${this.escapeHtml(task.createdByName || 'Admin')}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary" onclick="tasksManager.viewTaskDetails('${task.id}')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${task.status !== 'completed' ? `
                            <button class="btn btn-outline-success" onclick="tasksManager.markTaskComplete('${task.id}')" title="Mark Complete">
                                <i class="fas fa-check"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }

    getFilteredTasks() {
        let filtered = [...this.tasks];

        // Apply search filter
        const searchTerm = document.getElementById('taskSearch')?.value?.toLowerCase();
        if (searchTerm) {
            filtered = filtered.filter(task => 
                task.title.toLowerCase().includes(searchTerm) ||
                task.description.toLowerCase().includes(searchTerm)
            );
        }

        // Apply status filter
        const statusFilter = document.getElementById('taskStatusFilter')?.value;
        if (statusFilter && statusFilter !== 'all') {
            filtered = filtered.filter(task => task.status === statusFilter);
        }

        // Apply priority filter
        const priorityFilter = document.getElementById('taskPriorityFilter')?.value;
        if (priorityFilter && priorityFilter !== 'all') {
            filtered = filtered.filter(task => task.priority === priorityFilter);
        }

        return filtered;
    }

    filterTasks() {
        this.displayTasks();
        this.updateTaskStats();
    }

    updateTaskStats() {
        const totalTasks = this.tasks.length;
        const completedTasks = this.tasks.filter(task => task.status === 'completed').length;
        const pendingTasks = this.tasks.filter(task => task.status === 'pending').length;
        const overdueTasks = this.tasks.filter(task => this.isOverdue(task)).length;

        // Update stats display
        const statsContainer = document.getElementById('taskStats');
        if (statsContainer) {
            statsContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Tasks</h5>
                                <h3>${totalTasks}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Completed</h5>
                                <h3>${completedTasks}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Pending</h5>
                                <h3>${pendingTasks}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Overdue</h5>
                                <h3>${overdueTasks}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    isOverdue(task) {
        const dueDate = new Date(task.dueDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return dueDate < today && task.status !== 'completed';
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString();
    }

    getPriorityIcon(priority) {
        const icons = {
            'low': 'fa-arrow-down',
            'medium': 'fa-minus',
            'high': 'fa-arrow-up'
        };
        return icons[priority] || 'fa-minus';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    editTask(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (!task) {
            this.showNotification('Task not found', 'error');
            return;
        }

        // Populate edit form
        const form = document.getElementById('editTaskForm');
        if (form) {
            form.querySelector('[name="taskId"]').value = task.id;
            form.querySelector('[name="title"]').value = task.title;
            form.querySelector('[name="description"]').value = task.description || '';
            form.querySelector('[name="priority"]').value = task.priority;
            form.querySelector('[name="dueDate"]').value = task.dueDate;
            form.querySelector('[name="dueTime"]').value = task.dueTime || '';
            form.querySelector('[name="status"]').value = task.status;
        }

        // Show edit modal
        const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
        modal.show();
    }

    async deleteTask(taskId) {
        if (!confirm('Are you sure you want to delete this task?')) {
            return;
        }

        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                this.showNotification('Please login again', 'error');
                return;
            }

            const response = await fetch(`${this.apiBaseUrl}/${taskId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                // Remove from local array
                this.tasks = this.tasks.filter(task => task.id !== taskId);
                this.displayTasks();
                this.updateTaskStats();
                this.showNotification('Task deleted successfully', 'success');
            } else {
                const errorData = await response.json();
                this.showNotification(errorData.message || 'Failed to delete task', 'error');
            }
        } catch (error) {
            console.error('Error deleting task:', error);
            this.showNotification('Network error while deleting task', 'error');
        }
    }

    async markTaskComplete(taskId) {
        try {
            const token = sessionStorage.getItem('token');
            if (!token) {
                this.showNotification('Please login again', 'error');
                return;
            }

            const response = await fetch(`${this.apiBaseUrl}/${taskId}/complete`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    completedBy: this.currentUser.id
                })
            });

            if (response.ok) {
                const updatedTask = await response.json();
                
                // Update in local array
                const index = this.tasks.findIndex(task => task.id === taskId);
                if (index !== -1) {
                    this.tasks[index] = updatedTask;
                }
                
                this.displayTasks();
                this.updateTaskStats();
                this.showNotification('Task marked as complete', 'success');
            } else {
                const errorData = await response.json();
                this.showNotification(errorData.message || 'Failed to complete task', 'error');
            }
        } catch (error) {
            console.error('Error completing task:', error);
            this.showNotification('Network error while completing task', 'error');
        }
    }

    viewTaskDetails(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (!task) {
            this.showNotification('Task not found', 'error');
            return;
        }

        const content = `
            <div class="task-details">
                <h5 class="mb-3">${this.escapeHtml(task.title)}</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Description:</strong></p>
                        <p>${this.escapeHtml(task.description || 'No description provided')}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Priority:</strong> <span class="badge bg-${this.getPriorityColor(task.priority)}">${task.priority}</span></p>
                        <p><strong>Status:</strong> <span class="badge bg-${this.getStatusColor(task.status)}">${task.status}</span></p>
                        <p><strong>Due Date:</strong> ${this.formatDate(task.dueDate)}</p>
                        ${task.dueTime ? `<p><strong>Due Time:</strong> ${task.dueTime}</p>` : ''}
                        <p><strong>Created By:</strong> ${this.escapeHtml(task.createdByName || 'Admin')}</p>
                        <p><strong>Created:</strong> ${this.formatDate(task.createdAt)}</p>
                        ${task.assignedTo ? `<p><strong>Assigned To:</strong> ${task.assignedTo}</p>` : ''}
                        ${task.completedAt ? `<p><strong>Completed:</strong> ${this.formatDate(task.completedAt)}</p>` : ''}
                    </div>
                </div>
            </div>
        `;

        this.showTaskDetailsModal(content);
    }

    showTaskDetailsModal(content) {
        const modal = document.getElementById('taskDetailsModal');
        if (modal) {
            const modalBody = modal.querySelector('.modal-body');
            modalBody.innerHTML = content;
            
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }
    }

    getPriorityColor(priority) {
        const colors = {
            'low': 'success',
            'medium': 'warning',
            'high': 'danger'
        };
        return colors[priority] || 'secondary';
    }

    getStatusColor(status) {
        const colors = {
            'pending': 'warning',
            'in-progress': 'info',
            'completed': 'success',
            'overdue': 'danger'
        };
        return colors[status] || 'secondary';
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

// Initialize tasks manager
const tasksManager = new TasksManager();
window.tasksManager = tasksManager;
