// Real-time Updates Utility
class RealtimeUpdates {
  constructor() {
    this.init();
  }

  init() {
    // Listen for session changes (cross-tab communication)
    window.addEventListener("storage", (event) => {
      this.handleStorageChange(event);
    });

    // Listen for custom events (same-tab communication)
    window.addEventListener("attendanceStatusChanged", (event) => {
      this.handleAttendanceChange(event.detail);
    });
  }

  handleStorageChange(event) {
    switch (event.key) {
      case "attendanceOpen":
        this.updateAttendanceStatus(event.newValue === "true");
        break;
      case "currentSession":
        this.updateSession(event.newValue);
        break;
      case "attendance":
        this.updateAttendanceRecords();
        break;
    }
  }

  handleAttendanceChange(detail) {
    this.updateAttendanceStatus(detail.isOpen);
    this.updateSession(detail.session);
    this.showNotification(detail.isOpen);
  }

  updateAttendanceStatus(isOpen) {
    // Update admin status
    const adminStatus = document.getElementById("attendanceStatus");
    if (adminStatus) {
      adminStatus.className = `badge bg-${isOpen ? "success" : "danger"}`;
      adminStatus.textContent = isOpen ? "Open" : "Closed";
    }

    // Update student status
    const studentStatus = document.getElementById("studentAttendanceStatus");
    if (studentStatus) {
      studentStatus.className = `badge bg-${isOpen ? "success" : "danger"}`;
      studentStatus.textContent = isOpen ? "Open" : "Closed";

      // Add animation
      studentStatus.classList.add("animate__animated", "animate__pulse");
      setTimeout(() => {
        studentStatus.classList.remove("animate__animated", "animate__pulse");
      }, 1000);
    }

    // Update attendance form visibility
    const attendanceForm = document.getElementById("attendanceForm");
    if (attendanceForm) {
      attendanceForm.style.display = isOpen ? "block" : "none";
    }

    // Update admin attendance table
    if (typeof attendance !== "undefined" && attendance.loadAttendance) {
      attendance.loadAttendance();
    }

    // Update student dashboard
    if (
      typeof studentDashboard !== "undefined" &&
      studentDashboard.loadAttendanceData
    ) {
      studentDashboard.loadAttendanceData();
    }
  }

  updateSession(session) {
    const sessionSelect = document.getElementById("attendanceSession");
    if (sessionSelect) {
      sessionSelect.value = session;
    }

    const studentSessionSelect = document.getElementById(
      "studentAttendanceSession"
    );
    if (studentSessionSelect) {
      studentSessionSelect.value = session;
    }
  }

  updateAttendanceRecords() {
    // Update attendance tables
    if (typeof attendance !== "undefined" && attendance.loadAttendance) {
      attendance.loadAttendance();
    }

    if (
      typeof studentDashboard !== "undefined" &&
      studentDashboard.loadAttendanceHistory
    ) {
      studentDashboard.loadAttendanceHistory();
    }
  }

  showNotification(isOpen) {
    const status = isOpen ? "opened" : "closed";
    const type = isOpen ? "success" : "warning";

    if (typeof auth !== "undefined" && auth.showNotification) {
      auth.showNotification(`Attendance has been ${status}`, type);
    } else {
      // Fallback notification
      this.showFallbackNotification(status, type);
    }
  }

  showFallbackNotification(message, type) {
    // Create a simple notification if auth.showNotification is not available
    const notification = document.createElement("div");
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText =
      "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
    notification.innerHTML = `
            <strong>Attendance Update:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 5000);
  }

  // Method to trigger updates manually (for testing)
  triggerUpdate(type, data) {
    switch (type) {
      case "attendance":
        session.setItem("attendanceOpen", data.isOpen.toString());
        session.setItem("currentSession", data.session || "morning");
        break;
    }
  }
}

// Initialize real-time updates
const realtimeUpdates = new RealtimeUpdates();
