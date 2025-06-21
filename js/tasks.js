// Tasks management module
class TasksManager {
    constructor() {
        this.tasks = [];
        this.init();
    }

    init() {
        // Load tasks from storage
        this.loadTasks();

        // Add event listeners
        document.getElementById('addTaskForm').addEventListener('submit', (e) => this.handleAddTask(e));
        
        // Initialize search functionality
        const searchInput = document.querySelector('#tasksSection input[type="text"]');
        searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));

        // Initialize filter functionality
        const filterSelect = document.querySelector('#tasksSection select');
        filterSelect.addEventListener('change', (e) => this.handleFilter(e.target.value));
    }

    loadTasks() {
        // In production, this would be an API call
        const storedTasks = localStorage.getItem('tasks');
        this.tasks = storedTasks ? JSON.parse(storedTasks) : [];
        this.renderTasks();
    }

    saveTasks() {
        // In production, this would be an API call
        localStorage.setItem('tasks', JSON.stringify(this.tasks));
    }

    renderTasks(tasks = this.tasks) {
        const tbody = document.getElementById('tasksTableBody');
        tbody.innerHTML = '';

        tasks.forEach(task => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${task.id}</td>
                <td>${task.title}</td>
                <td>${task.description}</td>
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
                    <button class="btn btn-sm btn-primary" onclick="tasksManager.editTask('${task.id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="tasksManager.deleteTask('${task.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-${task.completed ? 'warning' : 'success'}" 
                            onclick="tasksManager.toggleTaskStatus('${task.id}')">
                        <i class="fas fa-${task.completed ? 'undo' : 'check'}"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async handleAddTask(event) {
        event.preventDefault();
        
        if (!auth.checkAuth()) return;

        const form = event.target;
        const formData = new FormData(form);

        try {
            const task = {
                id: this.generateTaskId(),
                title: formData.get('title'),
                description: formData.get('description'),
                dueDate: formData.get('dueDate'),
                priority: formData.get('priority'),
                completed: false,
                createdAt: new Date().toISOString()
            };

            // In production, this would be an API call
            this.tasks.push(task);
            this.saveTasks();
            this.renderTasks();

            // Close modal and reset form
            const modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
            modal.hide();
            form.reset();

            auth.showNotification('Task added successfully', 'success');
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    editTask(id) {
        if (!auth.checkAuth()) return;

        const task = this.tasks.find(t => t.id === id);
        if (!task) {
            auth.showNotification('Task not found', 'error');
            return;
        }

        // In production, this would open an edit modal
        console.log('Edit task:', task);
    }

    async deleteTask(id) {
        if (!auth.checkAuth()) return;

        if (!confirm('Are you sure you want to delete this task?')) {
            return;
        }

        try {
            // In production, this would be an API call
            this.tasks = this.tasks.filter(t => t.id !== id);
            this.saveTasks();
            this.renderTasks();

            auth.showNotification('Task deleted successfully', 'success');
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    async toggleTaskStatus(id) {
        if (!auth.checkAuth()) return;

        try {
            const task = this.tasks.find(t => t.id === id);
            if (!task) {
                throw new Error('Task not found');
            }

            // In production, this would be an API call
            task.completed = !task.completed;
            this.saveTasks();
            this.renderTasks();

            auth.showNotification(
                `Task marked as ${task.completed ? 'completed' : 'pending'}`,
                'success'
            );
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    handleSearch(query) {
        const filteredTasks = this.tasks.filter(task => 
            task.title.toLowerCase().includes(query.toLowerCase()) ||
            task.description.toLowerCase().includes(query.toLowerCase()) ||
            task.id.toLowerCase().includes(query.toLowerCase())
        );
        this.renderTasks(filteredTasks);
    }

    handleFilter(filter) {
        let filteredTasks = this.tasks;

        switch (filter) {
            case 'all':
                break;
            case 'completed':
                filteredTasks = this.tasks.filter(t => t.completed);
                break;
            case 'pending':
                filteredTasks = this.tasks.filter(t => !t.completed);
                break;
            case 'high':
                filteredTasks = this.tasks.filter(t => t.priority === 'high');
                break;
            case 'medium':
                filteredTasks = this.tasks.filter(t => t.priority === 'medium');
                break;
            case 'low':
                filteredTasks = this.tasks.filter(t => t.priority === 'low');
                break;
        }

        this.renderTasks(filteredTasks);
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

    generateTaskId() {
        // Generate a unique task ID
        const timestamp = Date.now().toString(36);
        const random = Math.random().toString(36).substr(2, 5);
        return `TASK-${timestamp}-${random}`.toUpperCase();
    }

    // Get task by ID
    getTask(id) {
        return this.tasks.find(t => t.id === id);
    }

    // Get all tasks
    getAllTasks() {
        return this.tasks;
    }

    // Get completed tasks
    getCompletedTasks() {
        return this.tasks.filter(t => t.completed);
    }

    // Get pending tasks
    getPendingTasks() {
        return this.tasks.filter(t => !t.completed);
    }
}

// Initialize tasks manager
const tasksManager = new TasksManager(); 