<?php
// Tasks Section
?>
<div id="tasksSection" class="section">
    <div class="section-header">
        <div class="header-content">
            <h1>Tasks</h1>
            <p>Manage tasks, assign, edit, or remove tasks.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                <i class="fas fa-plus me-2"></i>Add Task
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table" id="tasksTable">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tasksTableBody">
                <!-- Tasks will be loaded here -->
            </tbody>
        </table>
    </div>
</div> 