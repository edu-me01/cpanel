<?php
require_once 'includes/config.php';
requireStudent();

$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_attendance') {
        $token = $_POST['token'] ?? '';
        
        if (empty($token)) {
            $error = 'Please enter the attendance token.';
        } else {
            // Find active token
            $activeTokens = Database::findBy('attendance_tokens', 'isActive', true);
            $validToken = null;
            
            foreach ($activeTokens as $tokenData) {
                if ($tokenData['token'] === strtoupper($token)) {
                    $validToken = $tokenData;
                    break;
                }
            }
            
            if (!$validToken) {
                $error = 'Invalid or expired token.';
            } else {
                // Check if already marked attendance for this token
                $existingAttendance = Database::findBy('attendance', 'tokenId', $validToken['id']);
                $alreadyMarked = false;
                foreach ($existingAttendance as $att) {
                    if ($att['studentId'] === $user['id']) {
                        $alreadyMarked = true;
                        break;
                    }
                }
                
                if ($alreadyMarked) {
                    $error = 'You have already marked attendance for this lecture.';
                } else {
                    // Check if token is expired
                    $expiresAt = new DateTime($validToken['expiresAt']);
                    $now = new DateTime();
                    
                    if ($expiresAt < $now) {
                        $error = 'This token has expired.';
                    } else {
                        $newAttendance = [
                            'id' => Database::generateId('attendance'),
                            'studentId' => $user['id'],
                            'tokenId' => $validToken['id'],
                            'timestamp' => date('c'),
                            'status' => 'present'
                        ];
                        
                        Database::insert('attendance', $newAttendance);
                        $message = 'Attendance marked successfully for: ' . $validToken['lectureName'];
                    }
                }
            }
        }
    }
}

// Get student's attendance records
$attendanceRecords = Database::findBy('attendance', 'studentId', $user['id']);
$allTokens = Database::getAll('attendance_tokens');

// Create token lookup
$tokenLookup = [];
foreach ($allTokens as $token) {
    $tokenLookup[$token['id']] = $token;
}

// Get attendance statistics
$totalLectures = count($allTokens);
$attendedLectures = count($attendanceRecords);
$attendanceRate = $totalLectures > 0 ? round(($attendedLectures / $totalLectures) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Task Manager</title>
    
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

        .form-control {
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 2px;
            font-weight: bold;
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

        .attendance-card {
            border-left: 4px solid var(--success-color);
            transition: all 0.3s ease;
        }

        .attendance-card:hover {
            transform: translateX(5px);
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
                <i class="fas fa-calendar-check me-2"></i>Attendance
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
                            <a href="student-attendance.php" class="nav-item active">
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
                                                <i class="fas fa-calendar-check me-2 text-primary"></i>Attendance
                                            </h2>
                                            <p class="text-muted mb-0">Mark your attendance using tokens provided by your instructor</p>
                                        </div>
                                        <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
                                            <i class="fas fa-plus me-2"></i>Mark Attendance
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
                                <div class="stats-number"><?php echo $totalLectures; ?></div>
                                <div class="stats-label">Total Lectures</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $attendedLectures; ?></div>
                                <div class="stats-label">Attended</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $attendanceRate; ?>%</div>
                                <div class="stats-label">Attendance Rate</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $totalLectures - $attendedLectures; ?></div>
                                <div class="stats-label">Missed</div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Progress -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Attendance Progress
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold">Overall Attendance</span>
                                        <span class="text-muted"><?php echo $attendedLectures; ?> of <?php echo $totalLectures; ?> lectures</span>
                                    </div>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $attendanceRate; ?>%" aria-valuenow="<?php echo $attendanceRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php if ($attendanceRate >= 90): ?>
                                            <i class="fas fa-star text-warning me-1"></i>Excellent attendance!
                                        <?php elseif ($attendanceRate >= 80): ?>
                                            <i class="fas fa-thumbs-up text-success me-1"></i>Good attendance
                                        <?php elseif ($attendanceRate >= 70): ?>
                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>Attendance needs improvement
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-circle text-danger me-1"></i>Low attendance - please attend more lectures
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance History -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history me-2"></i>Attendance History
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($attendanceRecords)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No attendance records</h5>
                                            <p class="text-muted">Mark your first attendance to get started.</p>
                                            <button class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
                                                <i class="fas fa-plus me-2"></i>Mark Attendance
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($attendanceRecords as $record): ?>
                                                <?php
                                                $token = $tokenLookup[$record['tokenId']] ?? null;
                                                $attendanceDate = new DateTime($record['timestamp']);
                                                ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="card attendance-card">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <h6 class="card-title mb-0">
                                                                    <?php echo htmlspecialchars($token['lectureName'] ?? 'Unknown Lecture'); ?>
                                                                </h6>
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check me-1"></i>Present
                                                                </span>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calendar me-1"></i>
                                                                    <?php echo $attendanceDate->format('M d, Y'); ?>
                                                                </small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?php echo $attendanceDate->format('H:i'); ?>
                                                                </small>
                                                            </div>
                                                            
                                                            <?php if ($token): ?>
                                                                <div class="mb-3">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-key me-1"></i>
                                                                        Token: <code><?php echo $token['token']; ?></code>
                                                                    </small>
                                                                </div>
                                                            <?php endif; ?>
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

    <!-- Mark Attendance Modal -->
    <div class="modal fade" id="markAttendanceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-check me-2 text-primary"></i>Mark Attendance
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="mark_attendance">
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-key fa-3x text-primary mb-3"></i>
                            <h6>Enter Attendance Token</h6>
                            <p class="text-muted">Enter the token provided by your instructor to mark your attendance.</p>
                        </div>
                        <div class="mb-3">
                            <label for="token" class="form-label">Attendance Token</label>
                            <input type="text" class="form-control" id="token" name="token" placeholder="Enter token (e.g., ABC12345)" maxlength="8" required>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Tokens are case-insensitive and expire after a set time.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-modern">
                            <i class="fas fa-check me-2"></i>Mark Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-uppercase token input
        document.getElementById('token').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Focus on token input when modal opens
        document.getElementById('markAttendanceModal').addEventListener('shown.bs.modal', function() {
            document.getElementById('token').focus();
        });
    </script>
</body>
</html> 