<?php
require_once 'includes/config.php';
requireAdmin();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_token') {
        $lectureName = $_POST['lecture_name'] ?? '';
        $duration = $_POST['duration'] ?? 15;
        
        if (empty($lectureName)) {
            $error = 'Lecture name is required.';
        } else {
            $token = generateAttendanceToken();
            $newToken = [
                'id' => Database::generateId('token'),
                'token' => $token,
                'lectureName' => $lectureName,
                'duration' => (int)$duration,
                'createdBy' => $user['id'],
                'createdAt' => date('c'),
                'expiresAt' => date('c', time() + ($duration * 60)),
                'isActive' => true
            ];
            
            Database::insert('attendance_tokens', $newToken);
            $message = 'Attendance token generated successfully! Token: ' . $token;
        }
    } elseif ($action === 'deactivate_token') {
        $tokenId = $_POST['token_id'] ?? '';
        if (!empty($tokenId)) {
            try {
                $token = Database::find('attendance_tokens', $tokenId);
                if ($token) {
                    $token['isActive'] = false;
                    Database::update('attendance_tokens', $tokenId, $token);
                    $message = 'Token deactivated successfully!';
                }
            } catch (Exception $e) {
                $error = 'Error deactivating token: ' . $e->getMessage();
            }
        }
    }
}

// Get active tokens
$activeTokens = Database::findBy('attendance_tokens', 'isActive', true);
$allTokens = Database::getAll('attendance_tokens');
$students = Database::findBy('users', 'userType', 'student');

// Get attendance records
$attendanceRecords = Database::getAll('attendance');

function generateAttendanceToken() {
    return strtoupper(substr(md5(uniqid()), 0, 8));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - Task Manager</title>
    
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

        .token-card {
            border-left: 4px solid var(--success-color);
            transition: all 0.3s ease;
        }

        .token-card.expired {
            border-left-color: var(--danger-color);
        }

        .token-display {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 2px;
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
                <i class="fas fa-calendar-check me-2"></i>Attendance Management
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
                            <a href="admin-attendance.php" class="nav-item active">
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
                                                <i class="fas fa-calendar-check me-2 text-primary"></i>Attendance Management
                                            </h2>
                                            <p class="text-muted mb-0">Generate tokens and track student attendance</p>
                                        </div>
                                        <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#generateTokenModal">
                                            <i class="fas fa-plus me-2"></i>Generate Token
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

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo count($activeTokens); ?></div>
                                <div class="stats-label">Active Tokens</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo count($allTokens); ?></div>
                                <div class="stats-label">Total Tokens</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo count($attendanceRecords); ?></div>
                                <div class="stats-label">Attendance Records</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo count($students); ?></div>
                                <div class="stats-label">Total Students</div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Tokens -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-key me-2"></i>Active Attendance Tokens
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($activeTokens)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-key fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No active tokens</h5>
                                            <p class="text-muted">Generate a new token to start attendance tracking.</p>
                                            <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#generateTokenModal">
                                                <i class="fas fa-plus me-2"></i>Generate Token
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($activeTokens as $token): ?>
                                                <?php
                                                $expiresAt = new DateTime($token['expiresAt']);
                                                $now = new DateTime();
                                                $isExpired = $expiresAt < $now;
                                                $timeLeft = $expiresAt->diff($now);
                                                ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="card token-card <?php echo $isExpired ? 'expired' : ''; ?>">
                                                        <div class="card-body">
                                                            <div class="token-display mb-3">
                                                                <?php echo $token['token']; ?>
                                                            </div>
                                                            
                                                            <h6 class="card-title"><?php echo htmlspecialchars($token['lectureName']); ?></h6>
                                                            
                                                            <div class="mb-3">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    Duration: <?php echo $token['duration']; ?> minutes
                                                                </small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calendar me-1"></i>
                                                                    Created: <?php echo date('M d, Y H:i', strtotime($token['createdAt'])); ?>
                                                                </small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <?php if ($isExpired): ?>
                                                                    <span class="badge bg-danger">
                                                                        <i class="fas fa-times me-1"></i>Expired
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-success">
                                                                        <i class="fas fa-clock me-1"></i>
                                                                        <?php echo $timeLeft->format('%H:%I:%S'); ?> left
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <div class="d-flex justify-content-between">
                                                                <button class="btn btn-sm btn-outline-primary" onclick="copyToken('<?php echo $token['token']; ?>')">
                                                                    <i class="fas fa-copy me-1"></i>Copy
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger" onclick="deactivateToken('<?php echo $token['id']; ?>')">
                                                                    <i class="fas fa-stop me-1"></i>Deactivate
                                                                </button>
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

                    <!-- Recent Attendance Records -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>Recent Attendance Records
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($attendanceRecords)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No attendance records</h5>
                                            <p class="text-muted">Students will appear here once they mark their attendance.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Student</th>
                                                        <th>Token</th>
                                                        <th>Lecture</th>
                                                        <th>Date & Time</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $recentRecords = array_slice($attendanceRecords, -10);
                                                    foreach ($recentRecords as $record): 
                                                        $student = Database::find('users', $record['studentId']);
                                                        $token = Database::find('attendance_tokens', $record['tokenId']);
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
                                                                <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($token['token'] ?? 'N/A'); ?></code>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($token['lectureName'] ?? 'N/A'); ?></td>
                                                            <td><?php echo date('M d, Y H:i', strtotime($record['timestamp'])); ?></td>
                                                            <td>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check me-1"></i>Present
                                                                </span>
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

    <!-- Generate Token Modal -->
    <div class="modal fade" id="generateTokenModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2 text-primary"></i>Generate Attendance Token
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate_token">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="lecture_name" class="form-label">Lecture Name</label>
                            <input type="text" class="form-control" id="lecture_name" name="lecture_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="duration" class="form-label">Token Duration (minutes)</label>
                            <select class="form-control" id="duration" name="duration">
                                <option value="5">5 minutes</option>
                                <option value="10">10 minutes</option>
                                <option value="15" selected>15 minutes</option>
                                <option value="30">30 minutes</option>
                                <option value="60">1 hour</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern">
                            <i class="fas fa-key me-2"></i>Generate Token
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyToken(token) {
            navigator.clipboard.writeText(token).then(function() {
                alert('Token copied to clipboard: ' + token);
            });
        }

        function deactivateToken(tokenId) {
            if (confirm('Are you sure you want to deactivate this token?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="deactivate_token">
                    <input type="hidden" name="token_id" value="${tokenId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-refresh page every 30 seconds to update token status
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 