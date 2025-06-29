<?php
// Dashboard Overview Section
?>
<div id="dashboardSection" class="section active">
    <div class="section-header">
        <div class="header-content">
            <h1>Dashboard Overview</h1>
            <p>Welcome back! Here's what's happening today.</p>
        </div>
    </div>
    <!-- Stats Cards, etc. -->
</div>

<?php
// Dashboard section content
$user = getCurrentUser();

// Get statistics based on user type
if (isAdmin()) {
    $students = Database::findBy('users', 'userType', 'student');
    $tasks = Database::getAll('tasks');
    $submissions = Database::getAll('submissions');
    $attendanceRecords = Database::getAll('attendance');
    
    $totalStudents = count($students);
    $totalTasks = count($tasks);
    $totalSubmissions = count($submissions);
    $totalAttendance = count($attendanceRecords);
} else {
    // Student dashboard
    $submissions = Database::findBy('submissions', 'studentId', $user['id']);
    $attendanceRecords = Database::findBy('attendance', 'studentId', $user['id']);
    $tasks = Database::getAll('tasks');
    
    $totalSubmissions = count($submissions);
    $totalAttendance = count($attendanceRecords);
    $totalTasks = count($tasks);
    
    // Calculate grades
    $gradedSubmissions = 0;
    $totalPoints = 0;
    $earnedPoints = 0;
    foreach ($submissions as $submission) {
        if (isset($submission['grade'])) {
            $gradedSubmissions++;
            $task = null;
            foreach ($tasks as $t) {
                if ($t['id'] === $submission['taskId']) {
                    $task = $t;
                    break;
                }
            }
            if ($task) {
                $totalPoints += $task['points'];
                $earnedPoints += $submission['grade'];
            }
        }
    }
    $averageGrade = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;
}
?>

<!-- Dashboard Content -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="card-title fw-bold mb-0">
                            <i class="fas fa-tachometer-alt me-2 text-primary"></i>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!
                        </h2>
                        <p class="text-muted mb-0">Here's what's happening in your <?php echo isAdmin() ? 'class' : 'course'; ?> today.</p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted"><?php echo date('l, F d, Y'); ?></div>
                        <div class="text-muted"><?php echo date('H:i'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <?php if (isAdmin()): ?>
        <!-- Admin Stats -->
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo $totalStudents; ?></div>
                <div class="stats-label">Total Students</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card success">
                <div class="stats-number"><?php echo $totalTasks; ?></div>
                <div class="stats-label">Active Tasks</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card info">
                <div class="stats-number"><?php echo $totalSubmissions; ?></div>
                <div class="stats-label">Submissions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card warning">
                <div class="stats-number"><?php echo $totalAttendance; ?></div>
                <div class="stats-label">Attendance Records</div>
            </div>
        </div>
    <?php else: ?>
        <!-- Student Stats -->
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?php echo $totalTasks; ?></div>
                <div class="stats-label">Available Tasks</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card success">
                <div class="stats-number"><?php echo $totalSubmissions; ?></div>
                <div class="stats-label">My Submissions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card info">
                <div class="stats-number"><?php echo $totalAttendance; ?></div>
                <div class="stats-label">Attendance Records</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card warning">
                <div class="stats-number"><?php echo $averageGrade; ?>%</div>
                <div class="stats-label">Average Grade</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (isAdmin()): ?>
                        <div class="col-md-3 mb-3">
                            <a href="admin/students.php" class="btn btn-modern btn-primary w-100">
                                <i class="fas fa-users me-2"></i>Manage Students
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="admin/tasks.php" class="btn btn-modern btn-success w-100">
                                <i class="fas fa-tasks me-2"></i>Create Task
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="admin/attendance.php" class="btn btn-modern btn-info w-100">
                                <i class="fas fa-calendar-check me-2"></i>Generate Token
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="admin/submissions.php" class="btn btn-modern btn-warning w-100">
                                <i class="fas fa-file-alt me-2"></i>Grade Submissions
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="col-md-3 mb-3">
                            <a href="student/tasks.php" class="btn btn-modern btn-primary w-100">
                                <i class="fas fa-tasks me-2"></i>View Tasks
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="student/attendance.php" class="btn btn-modern btn-success w-100">
                                <i class="fas fa-calendar-check me-2"></i>Mark Attendance
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="student/submissions.php" class="btn btn-modern btn-info w-100">
                                <i class="fas fa-file-alt me-2"></i>My Submissions
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="student/lectures.php" class="btn btn-modern btn-warning w-100">
                                <i class="fas fa-chalkboard-teacher me-2"></i>View Lectures
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <?php if (isAdmin()): ?>
                    <!-- Admin Recent Activity -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Recent Submissions</h6>
                            <?php 
                            $recentSubmissions = array_slice($submissions, -3);
                            if (empty($recentSubmissions)): 
                            ?>
                                <p class="text-muted">No recent submissions</p>
                            <?php else: ?>
                                <?php foreach (array_reverse($recentSubmissions) as $submission): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">New submission received</div>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($submission['submittedAt'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Recent Attendance</h6>
                            <?php 
                            $recentAttendance = array_slice($attendanceRecords, -3);
                            if (empty($recentAttendance)): 
                            ?>
                                <p class="text-muted">No recent attendance records</p>
                            <?php else: ?>
                                <?php foreach (array_reverse($recentAttendance) as $record): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">Attendance marked</div>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($record['timestamp'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Student Recent Activity -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">My Recent Submissions</h6>
                            <?php 
                            $recentSubmissions = array_slice($submissions, -3);
                            if (empty($recentSubmissions)): 
                            ?>
                                <p class="text-muted">No submissions yet</p>
                            <?php else: ?>
                                <?php foreach (array_reverse($recentSubmissions) as $submission): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">Task submitted</div>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($submission['submittedAt'])); ?></small>
                                        </div>
                                        <?php if (isset($submission['grade'])): ?>
                                            <span class="badge bg-success"><?php echo $submission['grade']; ?> pts</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">My Recent Attendance</h6>
                            <?php 
                            $recentAttendance = array_slice($attendanceRecords, -3);
                            if (empty($recentAttendance)): 
                            ?>
                                <p class="text-muted">No attendance records yet</p>
                            <?php else: ?>
                                <?php foreach (array_reverse($recentAttendance) as $record): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">Attendance marked</div>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($record['timestamp'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 