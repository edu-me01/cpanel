<?php
require_once '../includes/config.php';
requireStudent();

$user = getCurrentUser();
$message = '';
$error = '';

// Get student's submissions with related data
$submissions = Database::findBy('submissions', 'studentId', $user['id']);
$tasks = Database::getAll('tasks');

// Create task lookup
$taskLookup = [];
foreach ($tasks as $task) {
    $taskLookup[$task['id']] = $task;
}

// Calculate statistics
$totalSubmissions = count($submissions);
$gradedSubmissions = 0;
$totalPoints = 0;
$earnedPoints = 0;

foreach ($submissions as $submission) {
    if (isset($submission['grade'])) {
        $gradedSubmissions++;
        $task = $taskLookup[$submission['taskId']] ?? null;
        if ($task) {
            $totalPoints += $task['points'];
            $earnedPoints += $submission['grade'];
        }
    }
}

$averageGrade = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - Task Manager</title>
    
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

        .btn-modern {
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-modern::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .submission-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .submission-card.graded {
            border-left-color: var(--success-color);
        }

        .submission-card.pending {
            border-left-color: var(--warning-color);
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

        .grade-badge {
            font-size: 1.2rem;
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 12px;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.2);
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
                <i class="fas fa-file-alt me-2"></i>My Submissions
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user['name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="student-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
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
                            <a href="student-dashboard.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                                <span>Dashboard</span>
                            </a>
                            <a href="student-tasks.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-tasks"></i></div>
                                <span>My Tasks</span>
                            </a>
                            <a href="student-attendance.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
                                <span>Attendance</span>
                            </a>
                            <a href="student-submissions.php" class="nav-item active">
                                <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
                                <span>My Submissions</span>
                            </a>
                            <a href="student-lectures.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                <span>Lectures</span>
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
                                                <i class="fas fa-file-alt me-2 text-primary"></i>My Submissions
                                            </h2>
                                            <p class="text-muted mb-0">View your submitted work and grades</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalSubmissions; ?></div>
                                <div class="stats-label">Total Submissions</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $gradedSubmissions; ?></div>
                                <div class="stats-label">Graded</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalSubmissions - $gradedSubmissions; ?></div>
                                <div class="stats-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $averageGrade; ?>%</div>
                                <div class="stats-label">Average Grade</div>
                            </div>
                        </div>
                    </div>

                    <!-- Grade Progress -->
                    <?php if ($gradedSubmissions > 0): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Grade Progress
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold">Overall Performance</span>
                                        <span class="text-muted"><?php echo $earnedPoints; ?> of <?php echo $totalPoints; ?> points</span>
                                    </div>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $averageGrade; ?>%" aria-valuenow="<?php echo $averageGrade; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php if ($averageGrade >= 90): ?>
                                            <i class="fas fa-star text-warning me-1"></i>Excellent work! Keep it up!
                                        <?php elseif ($averageGrade >= 80): ?>
                                            <i class="fas fa-thumbs-up text-success me-1"></i>Good performance
                                        <?php elseif ($averageGrade >= 70): ?>
                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>Room for improvement
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-circle text-danger me-1"></i>Consider seeking help from your instructor
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Submissions List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>Submission History
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($submissions)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No submissions yet</h5>
                                            <p class="text-muted">Submit your first task to see it here.</p>
                                            <a href="student-tasks.php" class="btn btn-primary btn-modern">
                                                <i class="fas fa-tasks me-2"></i>View Tasks
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($submissions as $submission): ?>
                                                <?php
                                                $task = $taskLookup[$submission['taskId']] ?? null;
                                                $isGraded = isset($submission['grade']);
                                                $submittedDate = new DateTime($submission['submittedAt']);
                                                $isLate = $task && $submittedDate > new DateTime($task['dueDate']);
                                                ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="card submission-card <?php echo $isGraded ? 'graded' : 'pending'; ?>">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <h6 class="card-title mb-0">
                                                                    <?php echo htmlspecialchars($task['title'] ?? 'Unknown Task'); ?>
                                                                </h6>
                                                                <span class="badge <?php echo $isGraded ? 'bg-success' : 'bg-warning'; ?>">
                                                                    <?php if ($isGraded): ?>
                                                                        <i class="fas fa-check me-1"></i>Graded
                                                                    <?php else: ?>
                                                                        <i class="fas fa-clock me-1"></i>Pending
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calendar me-1"></i>
                                                                    Submitted: <?php echo $submittedDate->format('M d, Y H:i'); ?>
                                                                </small>
                                                            </div>
                                                            
                                                            <?php if ($task): ?>
                                                                <div class="mb-3">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-calendar me-1"></i>
                                                                        Due: <?php echo date('M d, Y', strtotime($task['dueDate'])); ?>
                                                                    </small>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($isLate): ?>
                                                                <div class="mb-3">
                                                                    <span class="badge bg-danger">
                                                                        <i class="fas fa-exclamation-triangle me-1"></i>Late Submission
                                                                    </span>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($isGraded): ?>
                                                                <div class="mb-3">
                                                                    <div class="grade-badge bg-primary text-white text-center">
                                                                        <?php echo $submission['grade']; ?>/<?php echo $task['points'] ?? 100; ?>
                                                                    </div>
                                                                </div>
                                                                
                                                                <?php if (!empty($submission['feedback'])): ?>
                                                                    <div class="mb-3">
                                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewFeedback('<?php echo htmlspecialchars($submission['feedback']); ?>')">
                                                                            <i class="fas fa-comment me-1"></i>View Feedback
                                                                        </button>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            
                                                            <div class="d-flex justify-content-between">
                                                                <button class="btn btn-sm btn-outline-primary" onclick="viewSubmission('<?php echo htmlspecialchars($submission['content']); ?>')">
                                                                    <i class="fas fa-eye me-1"></i>View
                                                                </button>
                                                                
                                                                <?php if (!$isGraded): ?>
                                                                    <span class="text-muted small">
                                                                        <i class="fas fa-clock me-1"></i>Awaiting grade
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-success small">
                                                                        <i class="fas fa-check me-1"></i>Graded
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Submission Modal -->
    <div class="modal fade" id="viewSubmissionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2 text-primary"></i>Your Submission
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="submissionContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Feedback Modal -->
    <div class="modal fade" id="viewFeedbackModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-comment me-2 text-primary"></i>Instructor Feedback
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="feedbackContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function viewSubmission(content) {
            document.getElementById('submissionContent').innerHTML = content.replace(/\n/g, '<br>');
            new bootstrap.Modal(document.getElementById('viewSubmissionModal')).show();
        }

        function viewFeedback(feedback) {
            document.getElementById('feedbackContent').innerHTML = feedback.replace(/\n/g, '<br>');
            new bootstrap.Modal(document.getElementById('viewFeedbackModal')).show();
        }
    </script>
</body>
</html> 