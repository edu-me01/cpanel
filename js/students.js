// Students management module
class StudentsManager {
  constructor() {
    this.students = [];
    this.init();
  }

  init() {
    // Load students from API
    this.loadStudents();

    // Add event listeners
    const addStudentForm = document.getElementById("addStudentForm");
    if (addStudentForm) {
      addStudentForm.addEventListener("submit", (e) => this.handleAddStudent(e));
    }

    // Initialize search functionality
    const searchInput = document.querySelector(
      '#studentsSection input[type="text"]'
    );
    if (searchInput) {
      searchInput.addEventListener("input", (e) =>
        this.handleSearch(e.target.value)
      );
    }
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
        this.renderStudents();
      } else {
        console.error("Failed to load students");
      }
    } catch (error) {
      console.error("Error loading students:", error);
    }
  }

  renderStudents(students = this.students) {
    const tbody = document.getElementById("studentsTableBody");
    if (!tbody) return;
    
    tbody.innerHTML = "";

    students.forEach((student) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
                <td>${student.id}</td>
                <td>${student.fullName}</td>
                <td>${student.email}</td>
                <td>
                    <span class="badge bg-${
                      student.active ? "success" : "danger"
                    }">
                        ${student.active ? "Active" : "Inactive"}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="studentsManager.editStudent('${
                      student.id
                    }')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="studentsManager.deleteStudent('${
                      student.id
                    }')">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn btn-sm btn-${
                      student.active ? "warning" : "success"
                    }" 
                            onclick="studentsManager.toggleStudentStatus('${
                              student.id
                            }')">
                        <i class="fas fa-${
                          student.active ? "ban" : "check"
                        }"></i>
                    </button>
                </td>
            `;
      tbody.appendChild(tr);
    });
  }

  async handleAddStudent(event) {
    event.preventDefault();

    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    const form = event.target;
    const formData = new FormData(form);

    // Validate passwords match
    if (formData.get("password") !== formData.get("confirmPassword")) {
      this.showNotification("Passwords do not match", "error");
      return;
    }

    try {
      const studentData = {
        fullName: formData.get("fullName"),
        email: formData.get("email"),
        password: formData.get("password"),
      };

      const response = await fetch("/api/students", {
        method: "POST",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify(studentData),
      });

      if (response.ok) {
        const newStudent = await response.json();
        this.students.push(newStudent);
        this.renderStudents();

        // Close modal and reset form
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("addStudentModal")
        );
        if (modal) {
          modal.hide();
        }
        form.reset();

        this.showNotification("Student added successfully", "success");
      } else {
        const errorData = await response.json();
        this.showNotification(errorData.message || "Failed to add student", "error");
      }
    } catch (error) {
      console.error("Error adding student:", error);
      this.showNotification("Network error while adding student", "error");
    }
  }

  editStudent(id) {
    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    const student = this.students.find((s) => s.id === id);
    if (!student) {
      this.showNotification("Student not found", "error");
      return;
    }

    // In production, this would open an edit modal
    console.log("Edit student:", student);
  }

  async deleteStudent(id) {
    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    if (!confirm("Are you sure you want to delete this student?")) {
      return;
    }

    try {
      const response = await fetch(`/api/students/${id}`, {
        method: "DELETE",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
      });

      if (response.ok) {
        this.students = this.students.filter((s) => s.id !== id);
        this.renderStudents();
        this.showNotification("Student deleted successfully", "success");
      } else {
        const errorData = await response.json();
        this.showNotification(errorData.message || "Failed to delete student", "error");
      }
    } catch (error) {
      console.error("Error deleting student:", error);
      this.showNotification("Network error while deleting student", "error");
    }
  }

  async toggleStudentStatus(id) {
    const token = sessionStorage.getItem("token");
    if (!token) {
      console.error("No authentication token found");
      return;
    }

    try {
      const student = this.students.find((s) => s.id === id);
      if (!student) {
        throw new Error("Student not found");
      }

      const response = await fetch(`/api/students/${id}`, {
        method: "PUT",
        headers: {
          "Authorization": `Bearer ${token}`,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          active: !student.active,
        }),
      });

      if (response.ok) {
        student.active = !student.active;
        this.renderStudents();
        this.showNotification(
          `Student ${student.active ? "activated" : "deactivated"} successfully`,
          "success"
        );
      } else {
        const errorData = await response.json();
        this.showNotification(errorData.message || "Failed to update student status", "error");
      }
    } catch (error) {
      console.error("Error updating student status:", error);
      this.showNotification("Network error while updating student status", "error");
    }
  }

  handleSearch(query) {
    const filteredStudents = this.students.filter(
      (student) =>
        student.fullName.toLowerCase().includes(query.toLowerCase()) ||
        student.email.toLowerCase().includes(query.toLowerCase()) ||
        student.id.toLowerCase().includes(query.toLowerCase())
    );
    this.renderStudents(filteredStudents);
  }

  generateStudentId() {
    return "STU" + Date.now() + "-" + Math.random().toString(36).substr(2, 5).toUpperCase();
  }

  getStudent(id) {
    return this.students.find((s) => s.id === id);
  }

  getAllStudents() {
    return this.students;
  }

  getActiveStudents() {
    return this.students.filter((s) => s.active);
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

// Initialize students manager
const studentsManager = new StudentsManager();
