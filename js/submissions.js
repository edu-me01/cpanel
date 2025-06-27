// Submissions management module
class SubmissionsManager {
  constructor() {
    this.submissions = [];
    this.init();
  }

  init() {
    // Load submissions from API
    this.loadSubmissions();

    // Add event listeners
    const gradeSubmissionForm = document.getElementById("gradeSubmissionForm");
    if (gradeSubmissionForm) {
      gradeSubmissionForm.addEventListener("submit", (e) => this.handleGradeSubmission(e));
    }

    // Initialize search functionality
    const searchInput = document.querySelector(
      '#submissionsSection input[type="text"]'
    );
    if (searchInput) {
      searchInput.addEventListener("input", (e) =>
        this.handleSearch(e.target.value)
      );
    }

    // Initialize filter functionality
    const filterSelect = document.querySelector("#submissionsSection select");
    if (filterSelect) {
      filterSelect.addEventListener("change", (e) =>
        this.handleFilter(e.target.value)
      );
    }
  }

  async loadSubmissions() {
    try {
      const token = sessionStorage.getItem("token");
      if (!token) {
        console.error("No authentication token found");
        return;
      }

      const response = await fetch("/api/submissions", {
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      if (response.ok) {
        this.submissions = await response.json();
        this.renderSubmissions();
      } else {
        console.error("Failed to load submissions");
      }
    } catch (error) {
      console.error("Error loading submissions:", error);
    }
  }

  renderSubmissions(submissions = this.submissions) {
    const tbody = document.getElementById("submissionsTableBody");
    if (!tbody) return;
    
    tbody.innerHTML = "";

    submissions.forEach((submission) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
                <td>${submission.id}</td>
                <td>${submission.studentId}</td>
                <td>${submission.studentName}</td>
                <td>${submission.taskId}</td>
                <td>${submission.taskTitle}</td>
                <td>${submission.submittedAt}</td>
                <td>
                    <span class="badge bg-${this.getStatusColor(
                      submission.status
                    )}">
                        ${submission.status}
                    </span>
                </td>
                <td>${submission.grade || "-"}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="submissionsManager.viewSubmission('${
                      submission.id
                    }')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="submissionsManager.gradeSubmission('${
                      submission.id
                    }')">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="submissionsManager.deleteSubmission('${
                      submission.id
                    }')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
      tbody.appendChild(tr);
    });
  }

  async handleGradeSubmission(event) {
    event.preventDefault();

    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    const form = event.target;
    const formData = new FormData(form);

    try {
      const submissionId = formData.get("submissionId");
      const submission = this.submissions.find((s) => s.id === submissionId);

      if (!submission) {
        throw new Error("Submission not found");
      }

      const gradeData = {
        grade: formData.get("grade"),
        feedback: formData.get("feedback"),
        status: "graded"
      };

      const response = await fetch(`/api/submissions/${submissionId}`, {
        method: "PUT",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(gradeData),
      });

      if (response.ok) {
        await this.loadSubmissions();

        // Close modal and reset form
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("gradeSubmissionModal")
        );
        if (modal) {
          modal.hide();
        }
        form.reset();

        this.showNotification("Submission graded successfully", "success");
      } else {
        const errorData = await response.json();
        this.showNotification(errorData.message || "Failed to grade submission", "error");
      }
    } catch (error) {
      console.error("Error grading submission:", error);
      this.showNotification("Network error while grading submission", "error");
    }
  }

  viewSubmission(id) {
    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    const submission = this.submissions.find((s) => s.id === id);
    if (!submission) {
      this.showNotification("Submission not found", "error");
      return;
    }

    // In production, this would open a view modal
    console.log("View submission:", submission);
  }

  gradeSubmission(id) {
    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    const submission = this.submissions.find((s) => s.id === id);
    if (!submission) {
      this.showNotification("Submission not found", "error");
      return;
    }

    // In production, this would open a grade modal
    console.log("Grade submission:", submission);
  }

  async deleteSubmission(id) {
    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    if (!confirm("Are you sure you want to delete this submission?")) {
      return;
    }

    try {
      const response = await fetch(`/api/submissions/${id}`, {
        method: "DELETE",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      if (response.ok) {
        await this.loadSubmissions();
        this.showNotification("Submission deleted successfully", "success");
      } else {
        const errorData = await response.json();
        this.showNotification(errorData.message || "Failed to delete submission", "error");
      }
    } catch (error) {
      console.error("Error deleting submission:", error);
      this.showNotification("Network error while deleting submission", "error");
    }
  }

  handleSearch(query) {
    const filteredSubmissions = this.submissions.filter(
      (submission) =>
        submission.studentName.toLowerCase().includes(query.toLowerCase()) ||
        submission.taskTitle.toLowerCase().includes(query.toLowerCase()) ||
        submission.id.toLowerCase().includes(query.toLowerCase())
    );
    this.renderSubmissions(filteredSubmissions);
  }

  handleFilter(filter) {
    let filteredSubmissions = this.submissions;

    switch (filter) {
      case "all":
        break;
      case "pending":
        filteredSubmissions = this.submissions.filter(
          (s) => s.status === "pending"
        );
        break;
      case "graded":
        filteredSubmissions = this.submissions.filter(
          (s) => s.status === "graded"
        );
        break;
      case "late":
        filteredSubmissions = this.submissions.filter(
          (s) => s.status === "late"
        );
        break;
    }

    this.renderSubmissions(filteredSubmissions);
  }

  getStatusColor(status) {
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

  generateSubmissionId() {
    return "SUB" + Date.now() + "-" + Math.random().toString(36).substr(2, 5).toUpperCase();
  }

  getSubmission(id) {
    return this.submissions.find((s) => s.id === id);
  }

  getAllSubmissions() {
    return this.submissions;
  }

  getStudentSubmissions(studentId) {
    return this.submissions.filter((s) => s.studentId === studentId);
  }

  getTaskSubmissions(taskId) {
    return this.submissions.filter((s) => s.taskId === taskId);
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

// Initialize submissions manager
const submissionsManager = new SubmissionsManager();
