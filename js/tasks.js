/**
 * Tasks Management System
 * Handles task creation, display, and management for both admin and student views
 * Now uses backend API instead of localStorage
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
        this.setupWebSocket();
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

    setupWebSocket() {
        // Connect to WebSocket for real-time updates
        if (window.ws) {
            window.ws.addEventListener('message', (event) => {
                try {
                    const data = JSON.parse(event.data);
                    if (data.type === 'task_created' || data.type === 'task_updated' || data.type === 'task_deleted') {
                        this.loadTasks();
                        this.showNotification(`Task ${data.type.replace('task_', '')}`, 'info');
                    }
                } catch (error) {
                    console.error('Error parsing WebSocket message:', error);
                }
            });
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
                this.displayTasks();
                this.updateTaskStats();
            } else if (response.status === 401) {
                console.error('Authentication failed');
                this.showNotification('Please login again', 'error');
                // Redirect to login
                window.location.href = 'index.html';
            } else {
                console.error('Failed to load tasks from server:', response.status);
                this.showNotification('Failed to load tasks', 'error');
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

            // Log the request data for debugging
            console.log('Sending task data:', taskData);

            const response = await fetch(this.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(taskData)
            });

            // Log response details for debugging
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');

            if (response.ok) {
                if (isJson) {
                    const newTask = await response.json();
                    console.log('Received task data:', newTask);
                    
                    // Add to local array
                    this.tasks.push(newTask);
                    this.displayTasks();
                    this.updateTaskStats();
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Reset form
                    event.target.reset();
                    
                    // Show success message
                    this.showNotification('Task created successfully!', 'success');
                    
                    // Send WebSocket notification
                    if (window.ws) {
                        window.ws.send(JSON.stringify({
                            type: 'task_created',
                            data: newTask
                        }));
                    }
                } else {
                    // Handle non-JSON success response
                    const responseText = await response.text();
                    console.warn('Non-JSON success response:', responseText);
                    this.showNotification('Task created successfully!', 'success');
                    
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
                    if (modal) {
                        modal.hide();
                    }
                    event.target.reset();
                }
            } else if (response.status === 401) {
                this.showNotification('Please login again', 'error');
                window.location.href = 'index.html';
            } else if (response.status === 403) {
                this.showNotification('Admin access required', 'error');
            } else {
                // Handle error responses
                if (isJson) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to create task');
                } else {
                    const errorText = await response.text();
                    console.error('Error response text:', errorText);
                    throw new Error(`Server error: ${response.status} - ${errorText || 'Unknown error'}`);
                }
            }
        } catch (error) {
            console.error('Error creating task:', error);
            this.showNotification(error.message || 'Failed to create task', 'error');
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

            // Log the request data for debugging
            console.log('Updating task data:', { taskId, taskData });

            const response = await fetch(`${this.apiBaseUrl}/${taskId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(taskData)
            });

            // Log response details for debugging
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');

            if (response.ok) {
                if (isJson) {
                    const updatedTask = await response.json();
                    console.log('Received updated task data:', updatedTask);
                    
                    // Update task in local array
                    const taskIndex = this.tasks.findIndex(t => t.id === taskId);
                    if (taskIndex !== -1) {
                        this.tasks[taskIndex] = updatedTask;
                    }
                    
                    this.displayTasks();
                    this.updateTaskStats();
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Show success message
                    this.showNotification('Task updated successfully!', 'success');
                    
                    // Send WebSocket notification
                    if (window.ws) {
                        window.ws.send(JSON.stringify({
                            type: 'task_updated',
                            data: updatedTask
                        }));
                    }
                } else {
                    // Handle non-JSON success response
                    const responseText = await response.text();
                    console.warn('Non-JSON success response:', responseText);
                    this.showNotification('Task updated successfully!', 'success');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
                    if (modal) {
                        modal.hide();
                    }
                }
            } else if (response.status === 401) {
                this.showNotification('Please login again', 'error');
                window.location.href = 'index.html';
            } else if (response.status === 403) {
                this.showNotification('Admin access required', 'error');
            } else if (response.status === 404) {
                this.showNotification('Task not found', 'error');
            } else {
                // Handle error responses
                if (isJson) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to update task');
                } else {
                    const errorText = await response.text();
                    console.error('Error response text:', errorText);
                    throw new Error(`Server error: ${response.status} - ${errorText || 'Unknown error'}`);
                }
            }
        } catch (error) {
            console.error('Error updating task:', error);
            this.showNotification(error.message || 'Failed to update task', 'error');
        }
    }

    displayTasks() {
        const tableBody = document.getElementById('tasksTableBody');
        const studentTasksTableBody = document.getElementById('studentTasksTableBody');
        
        if (tableBody) {
            this.displayAdminTasks(tableBody);
        }
        
        if (studentTasksTableBody) {
            this.displayStudentTasks(studentTasksTableBody);
        }
    }

    displayAdminTasks(tableBody) {
        const filteredTasks = this.getFilteredTasks();
        
        if (filteredTasks.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <br>No tasks found
                        <br><small>Create your first task using the "Add Task" button</small>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = filteredTasks.map(task => `
            <tr class="table-row">
                <td>
                    <div class="task-info">
                        <div class="task-title">${this.escapeHtml(task.title)}</div>
                        <div class="task-id">ID: ${task.id}</div>
                        ${task.createdByName ? `<div class="task-creator">Created by: ${task.createdByName}</div>` : ''}
                    </div>
                </td>
                <td>
                    <div class="task-description">
                        ${this.escapeHtml(task.description || 'No description')}
                    </div>
                </td>
                <td>
                    <div class="task-date">
                        <div class="date">${this.formatDate(task.dueDate)}</div>
                        ${task.dueTime ? `<div class="time">${task.dueTime}</div>` : ''}
                        ${this.isOverdue(task) ? '<div class="overdue-warning text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Overdue</div>' : ''}
                    </div>
                </td>
                <td>
                    <div class="priority-indicator priority-${task.priority}">
                        <i class="fas fa-${this.getPriorityIcon(task.priority)} me-1"></i>${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                    </div>
                </td>
                <td>
                    <span class="badge status-badge ${task.status}">
                        <i class="fas fa-circle me-1"></i>${task.status.replace('-', ' ').toUpperCase()}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-primary" onclick="tasksManager.editTask('${task.id}')" title="Edit Task">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info" onclick="tasksManager.viewTaskDetails('${task.id}')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="tasksManager.deleteTask('${task.id}')" title="Delete Task">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    displayStudentTasks(tableBody) {
        const studentTasks = this.tasks.filter(task => 
            !task.assignedTo || task.assignedTo === this.currentUser?.id
        );

        if (studentTasks.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <br>No tasks assigned yet
                        <br><small>Your teacher will assign tasks here</small>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = studentTasks.map(task => `
            <tr class="table-row">
                <td>
                    <div class="task-info">
                        <div class="task-title">${this.escapeHtml(task.title)}</div>
                        <div class="task-description">${this.escapeHtml(task.description || 'No description')}</div>
                    </div>
                </td>
                <td>
                    <div class="task-date">
                        <div class="date">${this.formatDate(task.dueDate)}</div>
                        ${task.dueTime ? `<div class="time">${task.dueTime}</div>` : ''}
                        ${this.isOverdue(task) ? '<div class="overdue-warning text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Overdue</div>' : ''}
                    </div>
                </td>
                <td>
                    <div class="priority-indicator priority-${task.priority}">
                        <i class="fas fa-${this.getPriorityIcon(task.priority)} me-1"></i>${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                    </div>
                </td>
                <td>
                    <span class="badge status-badge ${task.status}">
                        <i class="fas fa-circle me-1"></i>${task.status.replace('-', ' ').toUpperCase()}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        ${task.status !== 'completed' ? `
                            <button class="btn btn-sm btn-outline-success" onclick="tasksManager.markTaskComplete('${task.id}')" title="Mark Complete">
                                <i class="fas fa-check"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-sm btn-outline-info" onclick="tasksManager.viewTaskDetails('${task.id}')" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    getFilteredTasks() {
        let filtered = [...this.tasks];
        
        const searchTerm = document.getElementById('taskSearch')?.value?.toLowerCase();
        const statusFilter = document.getElementById('taskStatusFilter')?.value;
        const priorityFilter = document.getElementById('taskPriorityFilter')?.value;

        if (searchTerm) {
            filtered = filtered.filter(task => 
                task.title.toLowerCase().includes(searchTerm) ||
                task.description?.toLowerCase().includes(searchTerm)
            );
        }

        if (statusFilter && statusFilter !== 'all') {
            filtered = filtered.filter(task => task.status === statusFilter);
        }

        if (priorityFilter && priorityFilter !== 'all') {
            filtered = filtered.filter(task => task.priority === priorityFilter);
        }

        // Sort by due date (earliest first)
        filtered.sort((a, b) => new Date(a.dueDate) - new Date(b.dueDate));

        return filtered;
    }

    filterTasks() {
        this.displayTasks();
    }

    updateTaskStats() {
        const totalTasks = this.tasks.length;
        const activeTasks = this.tasks.filter(task => task.status === 'pending' || task.status === 'in-progress').length;
        const completedTasks = this.tasks.filter(task => task.status === 'completed').length;
        const overdueTasks = this.tasks.filter(task => this.isOverdue(task)).length;

        // Update admin dashboard stats
        const activeTasksElement = document.getElementById('activeTasks');
        if (activeTasksElement) {
            activeTasksElement.textContent = activeTasks;
        }

        // Update student dashboard stats
        const completedTasksElement = document.getElementById('completedTasks');
        const totalTasksElement = document.getElementById('totalTasks');
        if (completedTasksElement && totalTasksElement) {
            completedTasksElement.textContent = completedTasks;
            totalTasksElement.textContent = totalTasks;
        }

        // Update upcoming tasks count
        const upcomingTasksElement = document.getElementById('upcomingTasks');
        if (upcomingTasksElement) {
            const upcomingTasks = this.tasks.filter(task => 
                task.status === 'pending' && !this.isOverdue(task)
            ).length;
            upcomingTasksElement.textContent = upcomingTasks;
        }
    }

    isOverdue(task) {
        if (task.status === 'completed') return false;
        const dueDate = new Date(task.dueDate);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return dueDate < today;
    }

    formatDate(dateString) {
        if (!dateString) return 'No date';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    getPriorityIcon(priority) {
        const icons = {
            high: 'exclamation-triangle',
            medium: 'minus',
            low: 'arrow-down'
        };
        return icons[priority] || 'minus';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    editTask(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (task) {
            // Populate edit form
            const editForm = document.getElementById('editTaskForm');
            if (editForm) {
                document.getElementById('editTaskId').value = task.id;
                document.getElementById('editTitle').value = task.title;
                document.getElementById('editDescription').value = task.description || '';
                document.getElementById('editPriority').value = task.priority;
                document.getElementById('editDueDate').value = task.dueDate;
                document.getElementById('editDueTime').value = task.dueTime || '';
                document.getElementById('editStatus').value = task.status;
                
                // Show edit modal
                const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
                modal.show();
            } else {
                this.showNotification('Edit form not found', 'error');
            }
        }
    }

    async deleteTask(taskId) {
        if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
            try {
                const token = sessionStorage.getItem('token');
                if (!token) {
                    this.showNotification('Please login again', 'error');
                    return;
                }

                const response = await fetch(`${this.apiBaseUrl}/${taskId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (response.ok) {
                    // Remove from local array
                    this.tasks = this.tasks.filter(t => t.id !== taskId);
                    this.displayTasks();
                    this.updateTaskStats();
                    this.showNotification('Task deleted successfully!', 'success');
                } else if (response.status === 401) {
                    this.showNotification('Please login again', 'error');
                    window.location.href = 'index.html';
                } else if (response.status === 403) {
                    this.showNotification('Admin access required', 'error');
                } else if (response.status === 404) {
                    this.showNotification('Task not found', 'error');
                } else {
                    throw new Error('Failed to delete task');
                }
            } catch (error) {
                console.error('Error deleting task:', error);
                this.showNotification('Failed to delete task', 'error');
            }
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
                
                // Update task in local array
                const taskIndex = this.tasks.findIndex(t => t.id === taskId);
                if (taskIndex !== -1) {
                    this.tasks[taskIndex] = updatedTask;
                }
                
                this.displayTasks();
                this.updateTaskStats();
                this.showNotification('Task marked as completed!', 'success');
            } else if (response.status === 401) {
                this.showNotification('Please login again', 'error');
                window.location.href = 'index.html';
            } else if (response.status === 404) {
                this.showNotification('Task not found', 'error');
            } else {
                throw new Error('Failed to complete task');
            }
        } catch (error) {
            console.error('Error completing task:', error);
            this.showNotification('Failed to complete task', 'error');
        }
    }

    viewTaskDetails(taskId) {
        const task = this.tasks.find(t => t.id === taskId);
        if (task) {
            const detailsHtml = `
                <div class="task-details">
                    <h5><i class="fas fa-tasks me-2"></i>${this.escapeHtml(task.title)}</h5>
                    <hr>
                    <p><strong><i class="fas fa-align-left me-2"></i>Description:</strong></p>
                    <p class="text-muted">${this.escapeHtml(task.description || 'No description provided')}</p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-exclamation-triangle me-2"></i>Priority:</strong> 
                                <span class="badge bg-${this.getPriorityColor(task.priority)}">${task.priority.toUpperCase()}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-info-circle me-2"></i>Status:</strong> 
                                <span class="badge bg-${this.getStatusColor(task.status)}">${task.status.replace('-', ' ').toUpperCase()}</span>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-calendar me-2"></i>Due Date:</strong> ${this.formatDate(task.dueDate)}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-clock me-2"></i>Due Time:</strong> ${task.dueTime || 'No specific time'}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-user me-2"></i>Created by:</strong> ${task.createdByName || 'Unknown'}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="fas fa-calendar-plus me-2"></i>Created:</strong> ${this.formatDate(task.createdAt)}</p>
                        </div>
                    </div>
                    ${task.completedAt ? `
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-check-circle me-2"></i>Completed:</strong> ${this.formatDate(task.completedAt)}</p>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
            
            // Create a modal to show task details
            this.showTaskDetailsModal(detailsHtml);
        }
    }

    showTaskDetailsModal(content) {
        // Remove existing modal if any
        const existingModal = document.getElementById('taskDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal
        const modalHtml = `
            <div class="modal fade" id="taskDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Task Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        const modal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
        modal.show();

        // Clean up modal after it's hidden
        document.getElementById('taskDetailsModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    getPriorityColor(priority) {
        const colors = {
            high: 'danger',
            medium: 'warning',
            low: 'success'
        };
        return colors[priority] || 'secondary';
    }

    getStatusColor(status) {
        const colors = {
            pending: 'warning',
            'in-progress': 'info',
            completed: 'success',
            overdue: 'danger'
        };
        return colors[status] || 'secondary';
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Initialize tasks manager
let tasksManager;
document.addEventListener('DOMContentLoaded', () => {
    tasksManager = new TasksManager();
});

// Global functions for HTML onclick handlers
window.tasksManager = {
    editTask: (taskId) => tasksManager?.editTask(taskId),
    deleteTask: (taskId) => tasksManager?.deleteTask(taskId),
    markTaskComplete: (taskId) => tasksManager?.markTaskComplete(taskId),
    viewTaskDetails: (taskId) => tasksManager?.viewTaskDetails(taskId)
};
