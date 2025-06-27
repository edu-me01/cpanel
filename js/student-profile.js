// Student Profile module
class StudentProfile {
  constructor() {
    this.student = null;
    this.init();
  }

  init() {
    // Check authentication
    if (!auth.checkAuth()) {
      window.location.href = "index.html";
      return;
    }

    // Load student data
    this.loadStudentData();

    // Add event listeners
    this.addEventListeners();
  }

  loadStudentData() {
    // In production, this would be an API call
    const studentId = new URLSearchParams(window.location.search).get("id");
    if (!studentId) {
      this.showError("Student ID not provided");
      return;
    }

    // Get student data from session
    const students = JSON.parse(session.getItem("students") || "[]");
    this.student = students.find((s) => s.id === studentId);

    if (!this.student) {
      this.showError("Student not found");
      return;
    }

    this.updateUI();
  }

  updateUI() {
    // Update profile information
    document.getElementById("studentName").textContent = this.student.fullName;
    document.getElementById("studentId").textContent = `ID: ${this.student.id}`;

    // Update form fields
    document.getElementById("editFullName").value = this.student.fullName;
    document.getElementById("editEmail").value = this.student.email;

    // Load task progress
    this.loadTaskProgress();

    // Load recent submissions
    this.loadRecentSubmissions();

    // Update progress bars
    this.updateProgressBars();
  }

  loadTaskProgress() {
    const tbody = document.getElementById("taskProgressBody");
    tbody.innerHTML = "";

    // Get enabled tasks
    const enabledTasks = taskConfig.getEnabledTasks();

    enabledTasks.forEach((task) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
                <td>${task.day}</td>
                <td>${task.title}</td>
                <td>
                    <span class="badge bg-${this.getTaskStatusColor(task.day)}">
                        ${this.getTaskStatus(task.day)}
                    </span>
                </td>
                <td>${this.getTaskGrade(task.day) || "-"}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="studentProfile.viewTask('${
                      task.day
                    }')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="studentProfile.submitTask('${
                      task.day
                    }')">
                        <i class="fas fa-upload"></i>
                    </button>
                </td>
            `;
      tbody.appendChild(tr);
    });
  }

  loadRecentSubmissions() {
    const tbody = document.getElementById("recentSubmissionsBody");
    tbody.innerHTML = "";

    // Get submissions from session
    const submissions = JSON.parse(session.getItem("submissions") || "[]")
      .filter((s) => s.studentId === this.student.id)
      .sort((a, b) => new Date(b.submittedAt) - new Date(a.submittedAt))
      .slice(0, 5);

    submissions.forEach((submission) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
                <td>${submission.taskTitle}</td>
                <td>${new Date(
                  submission.submittedAt
                ).toLocaleDateString()}</td>
                <td>
                    <span class="badge bg-${this.getSubmissionStatusColor(
                      submission.status
                    )}">
                        ${submission.status}
                    </span>
                </td>
                <td>${submission.grade || "-"}</td>
            `;
      tbody.appendChild(tr);
    });
  }

  updateProgressBars() {
    // Calculate progress
    const enabledTasks = taskConfig.getEnabledTasks();
    const completedTasks = enabledTasks.filter((task) =>
      this.isTaskCompleted(task.day)
    ).length;
    const tasksProgress = (completedTasks / enabledTasks.length) * 100;

    // Update progress bars
    document.getElementById("tasksProgress").style.width = `${tasksProgress}%`;
    document.getElementById("tasksProgress").textContent = `${Math.round(
      tasksProgress
    )}%`;

    // In production, these would be calculated from actual data
    document.getElementById("attendanceProgress").style.width = "85%";
    document.getElementById("attendanceProgress").textContent = "85%";
    document.getElementById("gradeProgress").style.width = "92%";
    document.getElementById("gradeProgress").textContent = "92%";
  }

  addEventListeners() {
    // Edit profile form
    document
      .getElementById("editProfileForm")
      .addEventListener("submit", (e) => this.handleEditProfile(e));

    // Logout button
    document.getElementById("logoutBtn").addEventListener("click", (e) => {
      e.preventDefault();
      auth.logout();
    });
  }

  async handleEditProfile(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);

    // Validate passwords match
    if (formData.get("editPassword") !== formData.get("editConfirmPassword")) {
      this.showError("Passwords do not match");
      return;
    }

    try {
      // Update student data
      this.student.fullName = formData.get("editFullName");
      this.student.email = formData.get("editEmail");
      if (formData.get("editPassword")) {
        this.student.password = formData.get("editPassword");
      }

      // Save to session
      const students = JSON.parse(session.getItem("students") || "[]");
      const index = students.findIndex((s) => s.id === this.student.id);
      if (index !== -1) {
        students[index] = this.student;
        session.setItem("students", JSON.stringify(students));
      }

      // Update UI
      this.updateUI();

      // Close modal
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("editProfileModal")
      );
      modal.hide();

      this.showSuccess("Profile updated successfully");
    } catch (error) {
      this.showError(error.message);
    }
  }

  viewTask(day) {
    // In production, this would open the task details
    console.log("View task:", day);
  }

  submitTask(day) {
    // In production, this would open the submission form
    console.log("Submit task:", day);
  }

  getTaskStatus(day) {
    // In production, this would check actual task status
    return "Pending";
  }

  getTaskStatusColor(day) {
    // In production, this would be based on actual status
    return "warning";
  }

  getTaskGrade(day) {
    // In production, this would get actual grade
    return null;
  }

  isTaskCompleted(day) {
    // In production, this would check actual completion status
    return false;
  }

  getSubmissionStatusColor(status) {
    switch (status.toLowerCase()) {
      case "pending":
        return "warning";
      case "graded":
        return "success";
      case "late":
        return "danger";
      default:
        return "secondary";
    }
  }

  showError(message) {
    // In production, this would show a proper error notification
    console.error(message);
    alert(message);
  }

  showSuccess(message) {
    // In production, this would show a proper success notification
    console.log(message);
    alert(message);
  }
}

// Initialize student profile
const studentProfile = new StudentProfile();
