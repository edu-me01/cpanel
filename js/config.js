// Task configuration
const taskConfig = {
    tasks: {
        day1: {
            enabled: true,
            title: "Day 1 Tasks",
            description: "Basic JavaScript and DOM manipulation tasks"
        },
        day2: {
            enabled: true,
            title: "Day 2 Tasks",
            description: "Event handling and form validation tasks"
        },
        day3: {
            enabled: true,
            title: "Day 3 Tasks",
            description: "Local storage and data persistence tasks"
        },
        day4: {
            enabled: true,
            title: "Day 4 Tasks",
            description: "API integration and async operations tasks"
        },
        day5: {
            enabled: true,
            title: "Day 5 Tasks",
            description: "Error handling and debugging tasks"
        },
        day6: {
            enabled: true,
            title: "Day 6 Tasks",
            description: "Object-oriented programming tasks"
        },
        day7: {
            enabled: true,
            title: "Day 7 Tasks",
            description: "ES6+ features and modern JavaScript tasks"
        },
        day8: {
            enabled: true,
            title: "Day 8 Tasks",
            description: "Testing and quality assurance tasks"
        },
        day9: {
            enabled: true,
            title: "Day 9 Tasks",
            description: "Performance optimization tasks"
        },
        day10: {
            enabled: true,
            title: "Day 10 Tasks",
            description: "Security and best practices tasks"
        },
        day11: {
            enabled: true,
            title: "Day 11 Tasks",
            description: "Advanced concepts and patterns tasks"
        },
        day12: {
            enabled: true,
            title: "Day 12 Tasks",
            description: "Final project and review tasks"
        }
    },

    // Get all enabled tasks
    getEnabledTasks() {
        return Object.entries(this.tasks)
            .filter(([_, config]) => config.enabled)
            .map(([day, config]) => ({
                day,
                ...config
            }));
    },

    // Toggle task visibility
    toggleTask(day) {
        if (this.tasks[day]) {
            this.tasks[day].enabled = !this.tasks[day].enabled;
            return true;
        }
        return false;
    },

    // Save configuration to localStorage
    saveConfig() {
        localStorage.setItem('taskConfig', JSON.stringify(this.tasks));
    },

    // Load configuration from localStorage
    loadConfig() {
        const savedConfig = localStorage.getItem('taskConfig');
        if (savedConfig) {
            this.tasks = JSON.parse(savedConfig);
        }
    }
};

// Load saved configuration
taskConfig.loadConfig(); 