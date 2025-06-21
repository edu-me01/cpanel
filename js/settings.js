// Settings management module
class SettingsManager {
    constructor() {
        this.settings = {
            theme: 'light',
            notifications: true,
            autoSave: true,
            language: 'en',
            dateFormat: 'MM/DD/YYYY',
            timeFormat: '12h'
        };
        this.init();
    }

    init() {
        // Load settings from storage
        this.loadSettings();

        // Add event listeners
        document.getElementById('settingsForm').addEventListener('submit', (e) => this.handleSaveSettings(e));
        
        // Initialize theme toggle
        const themeToggle = document.getElementById('themeToggle');
        themeToggle.addEventListener('change', (e) => this.handleThemeChange(e.target.checked));

        // Initialize notification toggle
        const notificationToggle = document.getElementById('notificationToggle');
        notificationToggle.addEventListener('change', (e) => this.handleNotificationChange(e.target.checked));

        // Initialize auto-save toggle
        const autoSaveToggle = document.getElementById('autoSaveToggle');
        autoSaveToggle.addEventListener('change', (e) => this.handleAutoSaveChange(e.target.checked));

        // Initialize language select
        const languageSelect = document.getElementById('languageSelect');
        languageSelect.addEventListener('change', (e) => this.handleLanguageChange(e.target.value));

        // Initialize date format select
        const dateFormatSelect = document.getElementById('dateFormatSelect');
        dateFormatSelect.addEventListener('change', (e) => this.handleDateFormatChange(e.target.value));

        // Initialize time format select
        const timeFormatSelect = document.getElementById('timeFormatSelect');
        timeFormatSelect.addEventListener('change', (e) => this.handleTimeFormatChange(e.target.value));
    }

    loadSettings() {
        // Load settings from localStorage
        const storedSettings = localStorage.getItem('settings');
        if (storedSettings) {
            this.settings = JSON.parse(storedSettings);
        }

        // Apply settings
        this.applySettings();
    }

    saveSettings() {
        // Save settings to localStorage
        localStorage.setItem('settings', JSON.stringify(this.settings));
    }

    applySettings() {
        // Apply theme
        document.body.setAttribute('data-theme', this.settings.theme);

        // Apply language
        document.documentElement.setAttribute('lang', this.settings.language);

        // Update form values
        document.getElementById('themeToggle').checked = this.settings.theme === 'dark';
        document.getElementById('notificationToggle').checked = this.settings.notifications;
        document.getElementById('autoSaveToggle').checked = this.settings.autoSave;
        document.getElementById('languageSelect').value = this.settings.language;
        document.getElementById('dateFormatSelect').value = this.settings.dateFormat;
        document.getElementById('timeFormatSelect').value = this.settings.timeFormat;
    }

    async handleSaveSettings(event) {
        event.preventDefault();
        
        if (!auth.checkAuth()) return;

        try {
            // Save all settings
            this.saveSettings();
            this.applySettings();

            auth.showNotification('Settings saved successfully', 'success');
        } catch (error) {
            auth.showNotification(error.message, 'error');
        }
    }

    handleThemeChange(isDark) {
        this.settings.theme = isDark ? 'dark' : 'light';
        this.saveSettings();
        this.applySettings();
    }

    handleNotificationChange(enabled) {
        this.settings.notifications = enabled;
        this.saveSettings();
    }

    handleAutoSaveChange(enabled) {
        this.settings.autoSave = enabled;
        this.saveSettings();
    }

    handleLanguageChange(language) {
        this.settings.language = language;
        this.saveSettings();
        this.applySettings();
    }

    handleDateFormatChange(format) {
        this.settings.dateFormat = format;
        this.saveSettings();
    }

    handleTimeFormatChange(format) {
        this.settings.timeFormat = format;
        this.saveSettings();
    }

    // Get current theme
    getTheme() {
        return this.settings.theme;
    }

    // Get notification status
    getNotifications() {
        return this.settings.notifications;
    }

    // Get auto-save status
    getAutoSave() {
        return this.settings.autoSave;
    }

    // Get current language
    getLanguage() {
        return this.settings.language;
    }

    // Get date format
    getDateFormat() {
        return this.settings.dateFormat;
    }

    // Get time format
    getTimeFormat() {
        return this.settings.timeFormat;
    }

    // Format date according to settings
    formatDate(date) {
        const d = new Date(date);
        const format = this.settings.dateFormat;
        
        switch (format) {
            case 'MM/DD/YYYY':
                return `${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getDate().toString().padStart(2, '0')}/${d.getFullYear()}`;
            case 'DD/MM/YYYY':
                return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`;
            case 'YYYY-MM-DD':
                return `${d.getFullYear()}-${(d.getMonth() + 1).toString().padStart(2, '0')}-${d.getDate().toString().padStart(2, '0')}`;
            default:
                return d.toLocaleDateString();
        }
    }

    // Format time according to settings
    formatTime(date) {
        const d = new Date(date);
        const format = this.settings.timeFormat;
        
        if (format === '12h') {
            return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } else {
            return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
        }
    }
}

// Initialize settings manager
const settingsManager = new SettingsManager(); 