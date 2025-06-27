// Attendance Management module
class Attendance {
  constructor() {
    this.isOpen = sessionStorage.getItem("attendanceOpen") === "true";
    this.currentDate = new Date().toISOString().split("T")[0];
    this.currentSession = sessionStorage.getItem("currentSession") || "morning";
    this.attendance = [];
    this.students = [];
    this.init();
  }

  init() {
    // Set current date in the date input
    const dateInput = document.getElementById("attendanceDate");
    if (dateInput) {
      dateInput.value = this.currentDate;
      dateInput.addEventListener("change", (e) =>
        this.handleDateChange(e.target.value)
      );
    }

    // Set up session change handler
    const sessionSelect = document.getElementById("attendanceSession");
    if (sessionSelect) {
      sessionSelect.addEventListener("change", (e) =>
        this.handleSessionChange(e.target.value)
      );
    }

    // Add event listeners for attendance buttons
    this.setupEventListeners();

    // Update status display
    this.updateStatus();

    // Load initial attendance data
    this.loadAttendance();
    this.loadStudents();
  }

  setupEventListeners() {
    // Open attendance button
    const openAttendanceBtn = document.getElementById("openAttendanceBtn");
    if (openAttendanceBtn) {
      openAttendanceBtn.addEventListener("click", () => this.openAttendance());
    }

    // Open attendance session button
    const openAttendanceSessionBtn = document.getElementById("openAttendanceSessionBtn");
    if (openAttendanceSessionBtn) {
      openAttendanceSessionBtn.addEventListener("click", () => this.openAttendance());
    }
  }

  openAttendance() {
    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    this.isOpen = true;
    this.updateStatus();
    this.loadAttendance();
    sessionStorage.setItem("attendanceOpen", "true");
    sessionStorage.setItem("currentSession", this.currentSession);

    // Dispatch custom event for real-time updates
    window.dispatchEvent(
      new CustomEvent("attendanceStatusChanged", {
        detail: { isOpen: true, session: this.currentSession },
      })
    );

    this.showNotification("Attendance is now open", "success");
  }

  closeAttendance() {
    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    this.isOpen = false;
    this.updateStatus();
    this.loadAttendance();
    sessionStorage.setItem("attendanceOpen", "false");

    // Dispatch custom event for real-time updates
    window.dispatchEvent(
      new CustomEvent("attendanceStatusChanged", {
        detail: { isOpen: false, session: this.currentSession },
      })
    );

    this.showNotification("Attendance is now closed", "success");
  }

  updateStatus() {
    const statusElement = document.getElementById("attendanceStatus");
    if (statusElement) {
      statusElement.className = `badge bg-${
        this.isOpen ? "success" : "danger"
      }`;
      statusElement.textContent = this.isOpen ? "Open" : "Closed";
    }
  }

  handleDateChange(date) {
    this.currentDate = date;
    this.loadAttendance();
  }

  handleSessionChange(session) {
    this.currentSession = session;
    this.loadAttendance();
    sessionStorage.setItem("currentSession", session);
  }

  async loadStudents() {
    try {
      const token = sessionStorage.getItem("token");
      if (!token) {
        console.error("No authentication token found");
        return;
      }

      const response = await fetch("/api/students", {
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      if (response.ok) {
        this.students = await response.json();
        this.loadAttendance();
      } else {
        console.error("Failed to load students");
      }
    } catch (error) {
      console.error("Error loading students:", error);
    }
  }

  async loadAttendance() {
    try {
      const token = sessionStorage.getItem("token");
      if (!token) {
        console.error("No authentication token found");
        return;
      }

      const response = await fetch(`/api/attendance?date=${this.currentDate}`, {
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      if (response.ok) {
        this.attendance = await response.json();
        this.renderAttendance();
      } else {
        console.error("Failed to load attendance");
      }
    } catch (error) {
      console.error("Error loading attendance:", error);
    }
  }

  renderAttendance() {
    const tbody = document.getElementById("attendanceTableBody");
    if (!tbody) return;

    tbody.innerHTML = "";

    this.students.forEach((student) => {
      const studentAttendance = this.attendance.find(
        (a) => a.studentId === student.id
      );
      const tr = document.createElement("tr");
      tr.innerHTML = `
                <td>${student.fullName}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(
                      studentAttendance?.status || "absent"
                    )}">
                        ${studentAttendance?.status || "Absent"}
                    </span>
                </td>
                <td>${studentAttendance?.time || "-"}</td>
                <td>
                    ${
                      this.isOpen
                        ? `
                        <button class="btn btn-sm btn-success" onclick="attendance.markPresent('${student.id}')">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="attendance.markLate('${student.id}')">
                            <i class="fas fa-clock"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="attendance.markAbsent('${student.id}')">
                            <i class="fas fa-times"></i>
                        </button>
                    `
                        : ""
                    }
                </td>
            `;
      tbody.appendChild(tr);
    });
  }

  markPresent(studentId) {
    if (!this.isOpen) return;
    this.updateAttendance(studentId, "present");
  }

  markLate(studentId) {
    if (!this.isOpen) return;
    this.updateAttendance(studentId, "late");
  }

  markAbsent(studentId) {
    if (!this.isOpen) return;
    this.updateAttendance(studentId, "absent");
  }

  async updateAttendance(studentId, status) {
    try {
      const token = sessionStorage.getItem("token");
      if (!token) {
        console.error("No authentication token found");
        return;
      }

      const student = this.students.find(s => s.id === studentId);
      if (!student) {
        this.showNotification("Student not found", "error");
        return;
      }

      const attendanceData = {
        studentId: studentId,
        studentName: student.fullName,
        date: this.currentDate,
        status: status,
        timeIn: new Date().toLocaleTimeString(),
        notes: `Marked as ${status} by admin`
      };

      const response = await fetch("/api/attendance", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(attendanceData),
      });

      if (response.ok) {
        await this.loadAttendance();
        this.showNotification("Attendance updated", "success");
      } else {
        const errorData = await response.json();
        this.showNotification(errorData.message || "Failed to update attendance", "error");
      }
    } catch (error) {
      console.error("Error updating attendance:", error);
      this.showNotification("Network error while updating attendance", "error");
    }
  }

  getStatusColor(status) {
    switch (status.toLowerCase()) {
      case "present":
        return "success";
      case "late":
        return "warning";
      case "absent":
        return "danger";
      default:
        return "secondary";
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

// Initialize attendance manager
const attendance = new Attendance();
