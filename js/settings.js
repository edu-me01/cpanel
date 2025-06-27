// Settings management module
class SettingsManager {
  constructor() {
    this.settings = {
      theme: "light",
      notifications: true,
      autoSave: true,
      language: "en",
      dateFormat: "MM/DD/YYYY",
      timeFormat: "12h",
    };
    this.init();
  }

  init() {
    // Load settings from storage
    this.loadSettings();

    // Add event listeners
    const settingsForm = document.getElementById("settingsForm");
    if (settingsForm) {
      settingsForm.addEventListener("submit", (e) => this.handleSaveSettings(e));
    }

    // Initialize theme toggle
    const themeToggle = document.getElementById("themeToggle");
    if (themeToggle) {
      themeToggle.addEventListener("change", (e) =>
        this.handleThemeChange(e.target.checked)
      );
    }

    // Initialize notification toggle
    const notificationToggle = document.getElementById("notificationToggle");
    if (notificationToggle) {
      notificationToggle.addEventListener("change", (e) =>
        this.handleNotificationChange(e.target.checked)
      );
    }

    // Initialize auto-save toggle
    const autoSaveToggle = document.getElementById("autoSaveToggle");
    if (autoSaveToggle) {
      autoSaveToggle.addEventListener("change", (e) =>
        this.handleAutoSaveChange(e.target.checked)
      );
    }

    // Initialize language select
    const languageSelect = document.getElementById("languageSelect");
    if (languageSelect) {
      languageSelect.addEventListener("change", (e) =>
        this.handleLanguageChange(e.target.value)
      );
    }

    // Initialize date format select
    const dateFormatSelect = document.getElementById("dateFormatSelect");
    if (dateFormatSelect) {
      dateFormatSelect.addEventListener("change", (e) =>
        this.handleDateFormatChange(e.target.value)
      );
    }

    // Initialize time format select
    const timeFormatSelect = document.getElementById("timeFormatSelect");
    if (timeFormatSelect) {
      timeFormatSelect.addEventListener("change", (e) =>
        this.handleTimeFormatChange(e.target.value)
      );
    }
  }

  loadSettings() {
    // Load settings from sessionStorage
    const storedSettings = sessionStorage.getItem("settings");
    if (storedSettings) {
      this.settings = JSON.parse(storedSettings);
    }

    // Apply settings
    this.applySettings();
  }

  saveSettings() {
    // Save settings to sessionStorage
    sessionStorage.setItem("settings", JSON.stringify(this.settings));
  }

  applySettings() {
    // Apply theme
    document.body.setAttribute("data-theme", this.settings.theme);

    // Apply language
    document.documentElement.setAttribute("lang", this.settings.language);

    // Update form values
    const themeToggle = document.getElementById("themeToggle");
    if (themeToggle) {
      themeToggle.checked = this.settings.theme === "dark";
    }

    const notificationToggle = document.getElementById("notificationToggle");
    if (notificationToggle) {
      notificationToggle.checked = this.settings.notifications;
    }

    const autoSaveToggle = document.getElementById("autoSaveToggle");
    if (autoSaveToggle) {
      autoSaveToggle.checked = this.settings.autoSave;
    }

    const languageSelect = document.getElementById("languageSelect");
    if (languageSelect) {
      languageSelect.value = this.settings.language;
    }

    const dateFormatSelect = document.getElementById("dateFormatSelect");
    if (dateFormatSelect) {
      dateFormatSelect.value = this.settings.dateFormat;
    }

    const timeFormatSelect = document.getElementById("timeFormatSelect");
    if (timeFormatSelect) {
      timeFormatSelect.value = this.settings.timeFormat;
    }
  }

  async handleSaveSettings(event) {
    event.preventDefault();

    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    try {
      // Save all settings
      this.saveSettings();
      this.applySettings();

      this.showNotification("Settings saved successfully", "success");
    } catch (error) {
      console.error("Error saving settings:", error);
      this.showNotification("Error saving settings", "error");
    }
  }

  handleThemeChange(isDark) {
    this.settings.theme = isDark ? "dark" : "light";
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
      case "MM/DD/YYYY":
        return `${(d.getMonth() + 1)
          .toString()
          .padStart(2, "0")}/${d
          .getDate()
          .toString()
          .padStart(2, "0")}/${d.getFullYear()}`;
      case "DD/MM/YYYY":
        return `${d.getDate().toString().padStart(2, "0")}/${(d.getMonth() + 1)
          .toString()
          .padStart(2, "0")}/${d.getFullYear()}`;
      case "YYYY-MM-DD":
        return `${d.getFullYear()}-${(d.getMonth() + 1)
          .toString()
          .padStart(2, "0")}-${d.getDate().toString().padStart(2, "0")}`;
      default:
        return d.toLocaleDateString();
    }
  }

  // Format time according to settings
  formatTime(date) {
    const d = new Date(date);
    const format = this.settings.timeFormat;

    if (format === "12h") {
      return d.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
      });
    } else {
      return d.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
      });
    }
  }

  showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement("div");
    notification.className = `alert alert-${type === "error" ? "danger" : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
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

// Initialize settings manager
const settingsManager = new SettingsManager();
