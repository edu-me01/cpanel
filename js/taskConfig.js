// Task configuration module
class TaskConfig {
    constructor() {
        this.tasks = {
            day1: { title: 'Day 1 Task', enabled: true },
            day2: { title: 'Day 2 Task', enabled: true },
            day3: { title: 'Day 3 Task', enabled: true },
            day4: { title: 'Day 4 Task', enabled: true },
            day5: { title: 'Day 5 Task', enabled: true }
        };
        this.loadConfig();
    }

    loadConfig() {
        const savedConfig = localStorage.getItem('taskConfig');
        if (savedConfig) {
            this.tasks = JSON.parse(savedConfig);
        }
    }

    saveConfig() {
        localStorage.setItem('taskConfig', JSON.stringify(this.tasks));
    }

    toggleTask(day) {
        if (this.tasks[day]) {
            this.tasks[day].enabled = !this.tasks[day].enabled;
            return true;
        }
        return false;
    }

    isTaskEnabled(day) {
        return this.tasks[day]?.enabled || false;
    }

    getTaskTitle(day) {
        return this.tasks[day]?.title || 'Unknown Task';
    }

    getAllTasks() {
        return this.tasks;
    }
}

// Initialize task configuration
const taskConfig = new TaskConfig(); 