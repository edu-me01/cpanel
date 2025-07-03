<?php
require_once '../includes/config.php';
requireAdmin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $dueDate = $_POST['due_date'] ?? '';
        $points = $_POST['points'] ?? 0;
        
        if (empty($title) || empty($description) || empty($dueDate)) {
            $error = 'Title, description, and due date are required.';
        } else {
            $newTask = [
                'id' => Database::generateId('task'),
                'title' => $title,
                'description' => $description,
                'dueDate' => $dueDate,
                'points' => (int)$points,
                'status' => 'active',
                'createdBy' => $user['id'],
                'createdAt' => date('c')
            ];
            
            Database::insert('tasks', $newTask);
            $message = 'Task created successfully!';
        }
    } elseif ($action === 'delete') {
        $taskId = $_POST['task_id'] ?? '';
        if (!empty($taskId)) {
            try {
                Database::delete('tasks', $taskId);
                $message = 'Task deleted successfully!';
            } catch (Exception $e) {
                $error = 'Error deleting task: ' . $e->getMessage();
            }
        }
    }
}

// Get all tasks
$tasks = Database::getAll('tasks');
$students = Database::findBy('users', 'userType', 'student');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Management - Task Manager</title>
    
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

        .task-card:hover {
            transform: translateX(5px);
        }

        .task-card.overdue {
            border-left-color: var(--danger-color);
        }

        .task-card.completed {
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
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tasks me-2"></i>Tasks Management
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
                            <a href="admin-tasks.php" class="nav-item active">
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
                                                <i class="fas fa-tasks me-2 text-primary"></i>Tasks Management
                                            </h2>
                                            <p class="text-muted mb-0">Create and manage assignments for students</p>
                                        </div>
                                        <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                            <i class="fas fa-plus me-2"></i>Create Task
                                        </button>
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

                    <!-- Tasks Grid -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>All Tasks (<?php echo count($tasks); ?>)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($tasks)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No tasks created yet</h5>
                                            <p class="text-muted">Create your first task to get started.</p>
                                            <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                                <i class="fas fa-plus me-2"></i>Create Task
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($tasks as $task): ?>
                                                <?php
                                                $dueDate = new DateTime($task['dueDate']);
                                                $now = new DateTime();
                                                $isOverdue = $dueDate < $now;
                                                $isCompleted = $task['status'] === 'completed';
                                                ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="card task-card <?php echo $isOverdue ? 'overdue' : ($isCompleted ? 'completed' : ''); ?>">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <h6 class="card-title mb-0"><?php echo htmlspecialchars($task['title']); ?></h6>
                                                                <div class="dropdown">
                                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li><a class="dropdown-item" href="#" onclick="editTask('<?php echo $task['id']; ?>')">
                                                                            <i class="fas fa-edit me-2"></i>Edit
                                                                        </a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="viewSubmissions('<?php echo $task['id']; ?>')">
                                                                            <i class="fas fa-file-alt me-2"></i>View Submissions
                                                                        </a></li>
                                                                        <li><hr class="dropdown-divider"></li>
                                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteTask('<?php echo $task['id']; ?>', '<?php echo htmlspecialchars($task['title']); ?>')">
                                                                            <i class="fas fa-trash me-2"></i>Delete
                                                                        </a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            
                                                            <p class="card-text text-muted small mb-3">
                                                                <?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?>
                                                                <?php if (strlen($task['description']) > 100): ?>...<?php endif; ?>
                                                            </p>
                                                            
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <span class="badge badge-modern <?php echo $isOverdue ? 'bg-danger' : ($isCompleted ? 'bg-success' : 'bg-primary'); ?>">
                                                                    <?php if ($isOverdue): ?>
                                                                        <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                                                                    <?php elseif ($isCompleted): ?>
                                                                        <i class="fas fa-check me-1"></i>Completed
                                                                    <?php else: ?>
                                                                        <i class="fas fa-clock me-1"></i>Active
                                                                    <?php endif; ?>
                                                                </span>
                                                                <span class="badge badge-modern bg-info">
                                                                    <i class="fas fa-star me-1"></i><?php echo $task['points']; ?> pts
                                                                </span>
                                                            </div>
                                                            
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calendar me-1"></i>
                                                                    Due: <?php echo $dueDate->format('M d, Y'); ?>
                                                                </small>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-users me-1"></i>
                                                                    <?php 
                                                                    $submissions = Database::findBy('submissions', 'taskId', $task['id']);
                                                                    echo count($submissions) . '/' . count($students);
                                                                    ?>
                                                                </small>
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

    <!-- Add Task Modal -->
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2 text-primary"></i>Create New Task
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Task Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="points" class="form-label">Points</label>
                                    <input type="number" class="form-control" id="points" name="points" value="10" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="datetime-local" class="form-control" id="due_date" name="due_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern">
                            <i class="fas fa-plus me-2"></i>Create Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteTaskName"></strong>?</p>
                    <p class="text-muted">This will also delete all associated submissions.</p>
                </div>
                <form method="POST" action="" id="deleteTaskForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="task_id" id="deleteTaskId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-modern">
                            <i class="fas fa-trash me-2"></i>Delete Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deleteTask(taskId, taskName) {
            document.getElementById('deleteTaskId').value = taskId;
            document.getElementById('deleteTaskName').textContent = taskName;
            new bootstrap.Modal(document.getElementById('deleteTaskModal')).show();
        }

        function editTask(taskId) {
            // TODO: Implement edit functionality
            alert('Edit functionality coming soon!');
        }

        function viewSubmissions(taskId) {
            // TODO: Implement view submissions functionality
            alert('View submissions functionality coming soon!');
        }

        // Set default due date to tomorrow
        document.addEventListener('DOMContentLoaded', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(23, 59, 0, 0);
            document.getElementById('due_date').value = tomorrow.toISOString().slice(0, 16);
        });
    </script>
</body>
</html> 