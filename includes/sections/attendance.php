<?php
// Attendance Section
if (isAdmin()) {
    header('Location: admin/attendance.php');
} else {
    header('Location: student/attendance.php');
}
exit;
?>
<div id="attendanceSection" class="section">
    <div class="section-header">
        <div class="header-content">
            <h1>Attendance</h1>
            <p>Monitor and manage student attendance.</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-success" id="openAttendanceBtn">
                <i class="fas fa-door-open me-2"></i>Open Attendance
            </button>
            <button class="btn btn-warning" id="generateTokenBtn">
                <i class="fas fa-key me-2"></i>Generate Token
            </button>
            <button class="btn btn-danger" id="finishAttendanceBtn">
                <i class="fas fa-flag-checkered me-2"></i>Finish Training
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table" id="attendanceTable">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
                <!-- Attendance will be loaded here -->
            </tbody>
        </table>
    </div>
</div> 