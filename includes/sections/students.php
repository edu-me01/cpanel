<?php
// Students Section
?>
<div id="studentsSection" class="section">
    <div class="section-header">
        <div class="header-content">
            <h1>Students</h1>
            <p>Manage student records, add, edit, or remove students.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fas fa-user-plus me-2"></i>Add Student
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table" id="studentsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="studentsTableBody">
                <!-- Students will be loaded here -->
            </tbody>
        </table>
    </div>
</div> 