<?php
require_once '../includes/config.php';
requireAdmin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'grade') {
        $submissionId = $_POST['submission_id'] ?? '';
        $grade = $_POST['grade'] ?? '';
        $feedback = $_POST['feedback'] ?? '';
        
        if (empty($submissionId) || empty($grade)) {
            $error = 'Submission ID and grade are required.';
        } else {
            try {
                $submission = Database::find('submissions', $submissionId);
                if ($submission) {
                    $submission['grade'] = (int)$grade;
                    $submission['feedback'] = $feedback;
                    $submission['gradedBy'] = $user['id'];
                    $submission['gradedAt'] = date('c');
                    
                    Database::update('submissions', $submissionId, $submission);
                    $message = 'Submission graded successfully!';
                }
            } catch (Exception $e) {
                $error = 'Error grading submission: ' . $e->getMessage();
            }
        }
    }
}

// Get all submissions with related data
$submissions = Database::getAll('submissions');
$tasks = Database::getAll('tasks');
$students = Database::findBy('users', 'userType', 'student');

// Create lookup arrays
$taskLookup = [];
foreach ($tasks as $task) {
    $taskLookup[$task['id']] = $task;
}

$studentLookup = [];
foreach ($students as $student) {
    $studentLookup[$student['id']] = $student;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions Management - Task Manager</title>
    
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

        .submission-card.late {
            border-left-color: var(--danger-color);
        }

        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table th {
            background: var(--primary-color);
            color: white;
            border: none;
            font-weight: 600;
        }

        .table td {
            border-color: #e5e7eb;
            vertical-align: middle;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .badge-modern {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
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
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-file-alt me-2"></i>Submissions Management
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
                            <a href="admin-submissions.php" class="nav-item active">
                                <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
                                <span>Submissions</span>
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
                                                <i class="fas fa-file-alt me-2 text-primary"></i>Submissions Management
                                            </h2>
                                            <p class="text-muted mb-0">Review and grade student submissions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo count($submissions); ?></div>
                                <div class="stats-label">Total Submissions</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number">
                                    <?php 
                                    $gradedCount = 0;
                                    foreach ($submissions as $sub) {
                                        if (isset($sub['grade'])) $gradedCount++;
                                    }
                                    echo $gradedCount;
                                    ?>
                                </div>
                                <div class="stats-label">Graded</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number">
                                    <?php echo count($submissions) - $gradedCount; ?>
                                </div>
                                <div class="stats-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo count($tasks); ?></div>
                                <div class="stats-label">Total Tasks</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submissions Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>All Submissions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($submissions)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No submissions yet</h5>
                                            <p class="text-muted">Students will appear here once they submit their work.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Student</th>
                                                        <th>Task</th>
                                                        <th>Submitted</th>
                                                        <th>Status</th>
                                                        <th>Grade</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($submissions as $submission): ?>
                                                        <?php
                                                        $task = $taskLookup[$submission['taskId']] ?? null;
                                                        $student = $studentLookup[$submission['studentId']] ?? null;
                                                        $isGraded = isset($submission['grade']);
                                                        $isLate = $task && new DateTime($submission['submittedAt']) > new DateTime($task['dueDate']);
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                        <i class="fas fa-user"></i>
                                                                    </div>
                                                                    <div>
                                                                        <h6 class="mb-0"><?php echo htmlspecialchars($student['name'] ?? 'Unknown'); ?></h6>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($student['email'] ?? ''); ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <h6 class="mb-0"><?php echo htmlspecialchars($task['title'] ?? 'Unknown Task'); ?></h6>
                                                                    <small class="text-muted">
                                                                        Due: <?php echo $task ? date('M d, Y', strtotime($task['dueDate'])) : 'N/A'; ?>
                                                                    </small>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <div><?php echo date('M d, Y', strtotime($submission['submittedAt'])); ?></div>
                                                                    <small class="text-muted"><?php echo date('H:i', strtotime($submission['submittedAt'])); ?></small>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php if ($isLate): ?>
                                                                    <span class="badge bg-danger">
                                                                        <i class="fas fa-clock me-1"></i>Late
                                                                    </span>
                                                                <?php elseif ($isGraded): ?>
                                                                    <span class="badge bg-success">
                                                                        <i class="fas fa-check me-1"></i>Graded
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-warning">
                                                                        <i class="fas fa-clock me-1"></i>Pending
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($isGraded): ?>
                                                                    <span class="badge bg-primary">
                                                                        <?php echo $submission['grade']; ?>/<?php echo $task['points'] ?? 100; ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewSubmission('<?php echo $submission['id']; ?>')">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <?php if (!$isGraded): ?>
                                                                        <button class="btn btn-sm btn-outline-success" onclick="gradeSubmission('<?php echo $submission['id']; ?>', '<?php echo htmlspecialchars($task['title'] ?? ''); ?>', '<?php echo $task['points'] ?? 100; ?>')">
                                                                            <i class="fas fa-star"></i>
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <button class="btn btn-sm btn-outline-warning" onclick="editGrade('<?php echo $submission['id']; ?>', '<?php echo $submission['grade']; ?>', '<?php echo htmlspecialchars($submission['feedback'] ?? ''); ?>')">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
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

    <!-- Grade Submission Modal -->
    <div class="modal fade" id="gradeSubmissionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-star me-2 text-primary"></i>Grade Submission
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="grade">
                    <input type="hidden" name="submission_id" id="gradeSubmissionId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Task: <span id="gradeTaskTitle"></span></label>
                        </div>
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade (out of <span id="maxPoints"></span>)</label>
                            <input type="number" class="form-control" id="grade" name="grade" min="0" max="100" required>
                        </div>
                        <div class="mb-3">
                            <label for="feedback" class="form-label">Feedback (optional)</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-modern">
                            <i class="fas fa-star me-2"></i>Submit Grade
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Submission Modal -->
    <div class="modal fade" id="viewSubmissionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2 text-primary"></i>View Submission
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="submissionContent">
                        <!-- Content will be loaded here -->
                    </div>
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
        function gradeSubmission(submissionId, taskTitle, maxPoints) {
            document.getElementById('gradeSubmissionId').value = submissionId;
            document.getElementById('gradeTaskTitle').textContent = taskTitle;
            document.getElementById('maxPoints').textContent = maxPoints;
            document.getElementById('grade').max = maxPoints;
            new bootstrap.Modal(document.getElementById('gradeSubmissionModal')).show();
        }

        function editGrade(submissionId, currentGrade, currentFeedback) {
            document.getElementById('gradeSubmissionId').value = submissionId;
            document.getElementById('grade').value = currentGrade;
            document.getElementById('feedback').value = currentFeedback;
            new bootstrap.Modal(document.getElementById('gradeSubmissionModal')).show();
        }

        function viewSubmission(submissionId) {
            // TODO: Implement view submission functionality
            document.getElementById('submissionContent').innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Submission Content</h5>
                    <p class="text-muted">Submission ID: ${submissionId}</p>
                    <p class="text-muted">Content viewing functionality coming soon!</p>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('viewSubmissionModal')).show();
        }
    </script>
</body>
</html> 