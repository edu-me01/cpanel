<?php
require_once 'includes/config.php';
requireAdmin();

$user = getCurrentUser();

// Get data for analytics
$students = Database::findBy('users', 'userType', 'student');
$tasks = Database::getAll('tasks');
$submissions = Database::getAll('submissions');
$attendanceRecords = Database::getAll('attendance');
$lectures = Database::getAll('lectures');

// Calculate statistics
$totalStudents = count($students);
$totalTasks = count($tasks);
$totalSubmissions = count($submissions);
$totalAttendance = count($attendanceRecords);
$totalLectures = count($lectures);

// Calculate submission statistics
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
$submissionRate = $totalStudents > 0 ? round(($totalSubmissions / ($totalStudents * $totalTasks)) * 100, 1) : 0;

// Prepare data for charts
$taskCompletionData = [];
foreach ($tasks as $task) {
    $taskSubmissions = 0;
    foreach ($submissions as $submission) {
        if ($submission['taskId'] === $task['id']) {
            $taskSubmissions++;
        }
    }
    $taskCompletionData[] = [
        'task' => $task['title'],
        'submissions' => $taskSubmissions,
        'completion_rate' => $totalStudents > 0 ? round(($taskSubmissions / $totalStudents) * 100, 1) : 0
    ];
}

// Attendance data by lecture
$attendanceByLecture = [];
foreach ($lectures as $lecture) {
    $lectureAttendance = 0;
    foreach ($attendanceRecords as $record) {
        // This is a simplified check - in a real system you'd have lecture-token mapping
        $lectureAttendance++;
    }
    $attendanceByLecture[] = [
        'lecture' => $lecture['title'],
        'attendance' => $lectureAttendance,
        'rate' => $totalStudents > 0 ? round(($lectureAttendance / $totalStudents) * 100, 1) : 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Task Manager</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --border-radius: 16px;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        * {
            font-family: "Inter", sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .navbar {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .nav-item {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 12px;
            margin: 4px 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
        }

        .nav-item.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-content {
            padding: 2rem;
            min-height: 100vh;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        .metric-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }

        .progress-bar {
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>Analytics
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user['name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="admin-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar p-3">
                    <div class="d-flex flex-column h-100">
                        <div class="flex-grow-1">
                            <a href="admin-dashboard.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                                <span>Dashboard</span>
                            </a>
                            <a href="admin-students.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-users"></i></div>
                                <span>Students</span>
                            </a>
                            <a href="admin-tasks.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-tasks"></i></div>
                                <span>Tasks</span>
                            </a>
                            <a href="admin-attendance.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
                                <span>Attendance</span>
                            </a>
                            <a href="admin-submissions.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
                                <span>Submissions</span>
                            </a>
                            <a href="admin-analytics.php" class="nav-item active">
                                <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
                                <span>Analytics</span>
                            </a>
                        </div>
                        
                        <div class="mt-auto">
                            <a href="logout.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="card-title fw-bold mb-0">
                                                <i class="fas fa-chart-line me-2 text-primary"></i>Analytics Dashboard
                                            </h2>
                                            <p class="text-muted mb-0">Comprehensive insights into student performance and engagement</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overview Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalStudents; ?></div>
                                <div class="stats-label">Total Students</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalTasks; ?></div>
                                <div class="stats-label">Total Tasks</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalSubmissions; ?></div>
                                <div class="stats-label">Total Submissions</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalAttendance; ?></div>
                                <div class="stats-label">Attendance Records</div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value"><?php echo $averageGrade; ?>%</div>
                                <div class="metric-label">Average Grade</div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-success" style="width: <?php echo $averageGrade; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value"><?php echo $submissionRate; ?>%</div>
                                <div class="metric-label">Submission Rate</div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-primary" style="width: <?php echo $submissionRate; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-value"><?php echo $gradedSubmissions; ?></div>
                                <div class="metric-label">Graded Submissions</div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-warning" style="width: <?php echo $totalSubmissions > 0 ? ($gradedSubmissions / $totalSubmissions) * 100 : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Task Completion Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tasks me-2"></i>Task Completion Rates
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="taskCompletionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Chart -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-calendar-check me-2"></i>Attendance Overview
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="attendanceChart"></canvas>
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
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="fw-bold mb-3">Recent Submissions</h6>
                                            <?php 
                                            $recentSubmissions = array_slice($submissions, -5);
                                            if (empty($recentSubmissions)): 
                                            ?>
                                                <p class="text-muted">No recent submissions</p>
                                            <?php else: ?>
                                                <?php foreach (array_reverse($recentSubmissions) as $submission): ?>
                                                    <?php
                                                    $student = null;
                                                    foreach ($students as $s) {
                                                        if ($s['id'] === $submission['studentId']) {
                                                            $student = $s;
                                                            break;
                                                        }
                                                    }
                                                    $task = null;
                                                    foreach ($tasks as $t) {
                                                        if ($t['id'] === $submission['taskId']) {
                                                            $task = $t;
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                                                            <i class="fas fa-file-alt"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold"><?php echo htmlspecialchars($student['name'] ?? 'Unknown'); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($task['title'] ?? 'Unknown Task'); ?></small>
                                                        </div>
                                                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($submission['submittedAt'])); ?></small>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h6 class="fw-bold mb-3">Recent Attendance</h6>
                                            <?php 
                                            $recentAttendance = array_slice($attendanceRecords, -5);
                                            if (empty($recentAttendance)): 
                                            ?>
                                                <p class="text-muted">No recent attendance records</p>
                                            <?php else: ?>
                                                <?php foreach (array_reverse($recentAttendance) as $record): ?>
                                                    <?php
                                                    $student = null;
                                                    foreach ($students as $s) {
                                                        if ($s['id'] === $record['studentId']) {
                                                            $student = $s;
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                                                            <i class="fas fa-check"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold"><?php echo htmlspecialchars($student['name'] ?? 'Unknown'); ?></div>
                                                            <small class="text-muted">Marked attendance</small>
                                                        </div>
                                                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($record['timestamp'])); ?></small>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Task Completion Chart
        const taskCompletionCtx = document.getElementById('taskCompletionChart').getContext('2d');
        new Chart(taskCompletionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($taskCompletionData, 'task')); ?>,
                datasets: [{
                    label: 'Completion Rate (%)',
                    data: <?php echo json_encode(array_column($taskCompletionData, 'completion_rate')); ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Attendance Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Attended', 'Missed'],
                datasets: [{
                    data: [<?php echo $totalAttendance; ?>, <?php echo ($totalStudents * $totalLectures) - $totalAttendance; ?>],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html> 