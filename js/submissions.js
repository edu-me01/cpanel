// Submissions management module
class SubmissionsManager {
  constructor() {
    this.submissions = [];
    this.init();
  }

  init() {
    // Load submissions from storage
    this.loadSubmissions();

    // Add event listeners
    document
      .getElementById("gradeSubmissionForm")
      .addEventListener("submit", (e) => this.handleGradeSubmission(e));

    // Initialize search functionality
    const searchInput = document.querySelector(
      '#submissionsSection input[type="text"]'
    );
    searchInput.addEventListener("input", (e) =>
      this.handleSearch(e.target.value)
    );

    // Initialize filter functionality
    const filterSelect = document.querySelector("#submissionsSection select");
    filterSelect.addEventListener("change", (e) =>
      this.handleFilter(e.target.value)
    );
  }

  loadSubmissions() {
    // In production, this would be an API call
    const storedSubmissions = session.getItem("submissions");
    this.submissions = storedSubmissions ? JSON.parse(storedSubmissions) : [];
    this.renderSubmissions();
  }

  saveSubmissions() {
    // In production, this would be an API call
    session.setItem("submissions", JSON.stringify(this.submissions));
  }

  renderSubmissions(submissions = this.submissions) {
    const tbody = document.getElementById("submissionsTableBody");
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

    if (!auth.checkAuth()) return;

    const form = event.target;
    const formData = new FormData(form);

    try {
      const submissionId = formData.get("submissionId");
      const submission = this.submissions.find((s) => s.id === submissionId);

      if (!submission) {
        throw new Error("Submission not found");
      }

      submission.grade = formData.get("grade");
      submission.feedback = formData.get("feedback");
      submission.status = "graded";
      submission.gradedAt = new Date().toISOString();

      // In production, this would be an API call
      this.saveSubmissions();
      this.renderSubmissions();

      // Close modal and reset form
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("gradeSubmissionModal")
      );
      modal.hide();
      form.reset();

      auth.showNotification("Submission graded successfully", "success");
    } catch (error) {
      auth.showNotification(error.message, "error");
    }
  }

  viewSubmission(id) {
    if (!auth.checkAuth()) return;

    const submission = this.submissions.find((s) => s.id === id);
    if (!submission) {
      auth.showNotification("Submission not found", "error");
      return;
    }

    // In production, this would open a view modal
    console.log("View submission:", submission);
  }

  gradeSubmission(id) {
    if (!auth.checkAuth()) return;

    const submission = this.submissions.find((s) => s.id === id);
    if (!submission) {
      auth.showNotification("Submission not found", "error");
      return;
    }

    // In production, this would open a grade modal
    console.log("Grade submission:", submission);
  }

  async deleteSubmission(id) {
    if (!auth.checkAuth()) return;

    if (!confirm("Are you sure you want to delete this submission?")) {
      return;
    }

    try {
      // In production, this would be an API call
      this.submissions = this.submissions.filter((s) => s.id !== id);
      this.saveSubmissions();
      this.renderSubmissions();

      auth.showNotification("Submission deleted successfully", "success");
    } catch (error) {
      auth.showNotification(error.message, "error");
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
    // Generate a unique submission ID
    const timestamp = Date.now().toString(36);
    const random = Math.random().toString(36).substr(2, 5);
    return `SUB-${timestamp}-${random}`.toUpperCase();
  }

  // Get submission by ID
  getSubmission(id) {
    return this.submissions.find((s) => s.id === id);
  }

  // Get all submissions
  getAllSubmissions() {
    return this.submissions;
  }

  // Get submissions for a specific student
  getStudentSubmissions(studentId) {
    return this.submissions.filter((s) => s.studentId === studentId);
  }

  // Get submissions for a specific task
  getTaskSubmissions(taskId) {
    return this.submissions.filter((s) => s.taskId === taskId);
  }
}

// Initialize submissions manager
const submissionsManager = new SubmissionsManager();
