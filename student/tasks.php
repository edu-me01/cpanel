<?php
require_once '../includes/config.php';
requireStudent();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'submit') {
        $taskId = $_POST['task_id'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if (empty($taskId) || empty($content)) {
            $error = 'Task ID and content are required.';
        } else {
            // Check if already submitted
            $existingSubmission = Database::findBy('submissions', 'taskId', $taskId);
            $alreadySubmitted = false;
            foreach ($existingSubmission as $sub) {
                if ($sub['studentId'] === $user['id']) {
                    $alreadySubmitted = true;
                    break;
                }
            }
            
            if ($alreadySubmitted) {
                $error = 'You have already submitted this task.';
            } else {
                $newSubmission = [
                    'id' => Database::generateId('submission'),
                    'taskId' => $taskId,
                    'studentId' => $user['id'],
                    'content' => $content,
                    'submittedAt' => date('c'),
                    'status' => 'submitted'
                ];
                
                Database::insert('submissions', $newSubmission);
                $message = 'Task submitted successfully!';
            }
        }
    }
}

// Get all tasks
$tasks = Database::getAll('tasks');
$submissions = Database::findBy('submissions', 'studentId', $user['id']);

// Create submission lookup
$submissionLookup = [];
foreach ($submissions as $sub) {
    $submissionLookup[$sub['taskId']] = $sub;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Task Manager</title>
    
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

        .task-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .task-card.overdue {
            border-left-color: var(--danger-color);
        }

        .task-card.submitted {
            border-left-color: var(--success-color);
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
                <i class="fas fa-tasks me-2"></i>My Tasks
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
                            <a href="student-tasks.php" class="nav-item active">
                                <div class="nav-icon"><i class="fas fa-tasks"></i></div>
                                <span>My Tasks</span>
                            </a>
                            <a href="student-attendance.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
                                <span>Attendance</span>
                            </a>
                            <a href="student-submissions.php" class="nav-item">
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
                                                <i class="fas fa-tasks me-2 text-primary"></i>My Tasks
                                            </h2>
                                            <p class="text-muted mb-0">View and submit your assignments</p>
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
                                <div class="stats-number"><?php echo count($tasks); ?></div>
                                <div class="stats-label">Total Tasks</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo count($submissions); ?></div>
                                <div class="stats-label">Submitted</div>
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
                                    <?php 
                                    $overdueCount = 0;
                                    foreach ($tasks as $task) {
                                        if (new DateTime($task['dueDate']) < new DateTime() && !isset($submissionLookup[$task['id']])) {
                                            $overdueCount++;
                                        }
                                    }
                                    echo $overdueCount;
                                    ?>
                                </div>
                                <div class="stats-label">Overdue</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks Grid -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>Available Tasks
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($tasks)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No tasks available</h5>
                                            <p class="text-muted">Your instructor will assign tasks here.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($tasks as $task): ?>
                                                <?php
                                                $dueDate = new DateTime($task['dueDate']);
                                                $now = new DateTime();
                                                $isOverdue = $dueDate < $now;
                                                $isSubmitted = isset($submissionLookup[$task['id']]);
                                                $submission = $submissionLookup[$task['id']] ?? null;
                                                $isGraded = $submission && isset($submission['grade']);
                                                ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="card task-card <?php echo $isOverdue ? 'overdue' : ($isSubmitted ? 'submitted' : ''); ?>">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <h6 class="card-title mb-0"><?php echo htmlspecialchars($task['title']); ?></h6>
                                                                <span class="badge badge-modern <?php echo $isOverdue ? 'bg-danger' : ($isSubmitted ? 'bg-success' : 'bg-primary'); ?>">
                                                                    <?php if ($isOverdue && !$isSubmitted): ?>
                                                                        <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                                                                    <?php elseif ($isSubmitted): ?>
                                                                        <i class="fas fa-check me-1"></i>Submitted
                                                                    <?php else: ?>
                                                                        <i class="fas fa-clock me-1"></i>Active
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            
                                                            <p class="card-text text-muted small mb-3">
                                                                <?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?>
                                                                <?php if (strlen($task['description']) > 100): ?>...<?php endif; ?>
                                                            </p>
                                                            
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <span class="badge badge-modern bg-info">
                                                                    <i class="fas fa-star me-1"></i><?php echo $task['points']; ?> pts
                                                                </span>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calendar me-1"></i>
                                                                    Due: <?php echo $dueDate->format('M d, Y'); ?>
                                                                </small>
                                                            </div>
                                                            
                                                            <?php if ($isSubmitted): ?>
                                                                <div class="mb-3">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-upload me-1"></i>
                                                                        Submitted: <?php echo date('M d, Y H:i', strtotime($submission['submittedAt'])); ?>
                                                                    </small>
                                                                </div>
                                                                
                                                                <?php if ($isGraded): ?>
                                                                    <div class="mb-3">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <span class="badge bg-success">
                                                                                Grade: <?php echo $submission['grade']; ?>/<?php echo $task['points']; ?>
                                                                            </span>
                                                                            <button class="btn btn-sm btn-outline-primary" onclick="viewFeedback('<?php echo htmlspecialchars($submission['feedback'] ?? ''); ?>')">
                                                                                <i class="fas fa-comment me-1"></i>Feedback
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="mb-3">
                                                                        <span class="badge bg-warning">
                                                                            <i class="fas fa-clock me-1"></i>Pending Grade
                                                                        </span>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            
                                                            <div class="d-flex justify-content-between">
                                                                <button class="btn btn-sm btn-outline-primary" onclick="viewTask('<?php echo htmlspecialchars($task['title']); ?>', '<?php echo htmlspecialchars($task['description']); ?>', '<?php echo $task['points']; ?>')">
                                                                    <i class="fas fa-eye me-1"></i>View
                                                                </button>
                                                                
                                                                <?php if (!$isSubmitted): ?>
                                                                    <button class="btn btn-sm btn-primary" onclick="submitTask('<?php echo $task['id']; ?>', '<?php echo htmlspecialchars($task['title']); ?>')">
                                                                        <i class="fas fa-upload me-1"></i>Submit
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-outline-secondary" onclick="viewSubmission('<?php echo htmlspecialchars($submission['content']); ?>')">
                                                                        <i class="fas fa-file-alt me-1"></i>View Submission
                                                                    </button>
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

    <!-- Submit Task Modal -->
    <div class="modal fade" id="submitTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2 text-primary"></i>Submit Task
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="submit">
                    <input type="hidden" name="task_id" id="submitTaskId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Task: <span id="submitTaskTitle"></span></label>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Your Submission</label>
                            <textarea class="form-control" id="content" name="content" rows="8" placeholder="Enter your submission content here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern">
                            <i class="fas fa-upload me-2"></i>Submit Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Task Modal -->
    <div class="modal fade" id="viewTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2 text-primary"></i>Task Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="viewTaskTitle"></h6>
                    <div class="mb-3">
                        <span class="badge bg-info">
                            <i class="fas fa-star me-1"></i><span id="viewTaskPoints"></span> points
                        </span>
                    </div>
                    <div id="viewTaskDescription"></div>
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

    <!-- View Submission Modal -->
    <div class="modal fade" id="viewSubmissionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt me-2 text-primary"></i>Your Submission
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function submitTask(taskId, taskTitle) {
            document.getElementById('submitTaskId').value = taskId;
            document.getElementById('submitTaskTitle').textContent = taskTitle;
            new bootstrap.Modal(document.getElementById('submitTaskModal')).show();
        }

        function viewTask(taskTitle, taskDescription, taskPoints) {
            document.getElementById('viewTaskTitle').textContent = taskTitle;
            document.getElementById('viewTaskPoints').textContent = taskPoints;
            document.getElementById('viewTaskDescription').innerHTML = taskDescription.replace(/\n/g, '<br>');
            new bootstrap.Modal(document.getElementById('viewTaskModal')).show();
        }

        function viewFeedback(feedback) {
            document.getElementById('feedbackContent').innerHTML = feedback ? feedback.replace(/\n/g, '<br>') : '<p class="text-muted">No feedback provided.</p>';
            new bootstrap.Modal(document.getElementById('viewFeedbackModal')).show();
        }

        function viewSubmission(content) {
            document.getElementById('submissionContent').innerHTML = content.replace(/\n/g, '<br>');
            new bootstrap.Modal(document.getElementById('viewSubmissionModal')).show();
        }
    </script>
</body>
</html> 