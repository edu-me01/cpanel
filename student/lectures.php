<?php
require_once '../includes/config.php';
requireStudent();

$user = getCurrentUser();
$message = '';
$error = '';

// Get lectures and attendance data
$lectures = Database::getAll('lectures');
$attendanceRecords = Database::findBy('attendance', 'studentId', $user['id']);
$allTokens = Database::getAll('attendance_tokens');

// Create attendance lookup
$attendanceLookup = [];
foreach ($attendanceRecords as $record) {
    $attendanceLookup[$record['tokenId']] = $record;
}

// Create token lookup
$tokenLookup = [];
foreach ($allTokens as $token) {
    $tokenLookup[$token['id']] = $token;
}

// Calculate attendance statistics
$totalLectures = count($lectures);
$attendedLectures = count($attendanceRecords);
$attendanceRate = $totalLectures > 0 ? round(($attendedLectures / $totalLectures) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lectures - Task Manager</title>
    
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

        .lecture-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .lecture-card.attended {
            border-left-color: var(--success-color);
        }

        .lecture-card.missed {
            border-left-color: var(--danger-color);
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

        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            border-radius: 4px;
        }

        .material-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .material-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chalkboard-teacher me-2"></i>Lectures
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
                            <a href="student-submissions.php" class="nav-item">
                                <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
                                <span>My Submissions</span>
                            </a>
                            <a href="student-lectures.php" class="nav-item active">
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
                                                <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Lectures
                                            </h2>
                                            <p class="text-muted mb-0">View lecture information and materials</p>
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
                                        <i class="fas fa-chart-line me-2"></i>Attendance Overview
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold">Lecture Attendance</span>
                                        <span class="text-muted"><?php echo $attendedLectures; ?> of <?php echo $totalLectures; ?> lectures</span>
                                    </div>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $attendanceRate; ?>%" aria-valuenow="<?php echo $attendanceRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php if ($attendanceRate >= 90): ?>
                                            <i class="fas fa-star text-warning me-1"></i>Excellent attendance! Keep it up!
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

                    <!-- Lectures List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>Lecture Schedule
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($lectures)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No lectures scheduled</h5>
                                            <p class="text-muted">Your instructor will add lectures here.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($lectures as $lecture): ?>
                                                <?php
                                                $lectureDate = new DateTime($lecture['date']);
                                                $now = new DateTime();
                                                $isPast = $lectureDate < $now;
                                                $isAttended = false;
                                                
                                                // Check if attended by looking through tokens
                                                foreach ($allTokens as $token) {
                                                    if ($token['lectureName'] === $lecture['title']) {
                                                        if (isset($attendanceLookup[$token['id']])) {
                                                            $isAttended = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <div class="col-md-6 col-lg-4 mb-4">
                                                    <div class="card lecture-card <?php echo $isAttended ? 'attended' : ($isPast ? 'missed' : ''); ?>">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <h6 class="card-title mb-0"><?php echo htmlspecialchars($lecture['title']); ?></h6>
                                                                <span class="badge <?php echo $isAttended ? 'bg-success' : ($isPast ? 'bg-danger' : 'bg-primary'); ?>">
                                                                    <?php if ($isAttended): ?>
                                                                        <i class="fas fa-check me-1"></i>Attended
                                                                    <?php elseif ($isPast): ?>
                                                                        <i class="fas fa-times me-1"></i>Missed
                                                                    <?php else: ?>
                                                                        <i class="fas fa-clock me-1"></i>Upcoming
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calendar me-1"></i>
                                                                    <?php echo $lectureDate->format('M d, Y'); ?>
                                                                </small>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?php echo $lectureDate->format('H:i'); ?>
                                                                </small>
                                                            </div>
                                                            
                                                            <?php if (!empty($lecture['description'])): ?>
                                                                <div class="mb-3">
                                                                    <p class="card-text small text-muted">
                                                                        <?php echo htmlspecialchars(substr($lecture['description'], 0, 100)); ?>
                                                                        <?php if (strlen($lecture['description']) > 100): ?>...<?php endif; ?>
                                                                    </p>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($lecture['materials'])): ?>
                                                                <div class="mb-3">
                                                                    <h6 class="small fw-bold mb-2">Materials:</h6>
                                                                    <?php 
                                                                    $materials = json_decode($lecture['materials'], true) ?: [];
                                                                    foreach (array_slice($materials, 0, 3) as $material): 
                                                                    ?>
                                                                        <div class="material-item">
                                                                            <i class="fas fa-file me-2"></i>
                                                                            <small><?php echo htmlspecialchars($material['name'] ?? 'Material'); ?></small>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                    <?php if (count($materials) > 3): ?>
                                                                        <small class="text-muted">+<?php echo count($materials) - 3; ?> more</small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="d-flex justify-content-between">
                                                                <button class="btn btn-sm btn-outline-primary" onclick="viewLecture('<?php echo htmlspecialchars($lecture['title']); ?>', '<?php echo htmlspecialchars($lecture['description']); ?>', '<?php echo htmlspecialchars($lecture['materials']); ?>')">
                                                                    <i class="fas fa-eye me-1"></i>View Details
                                                                </button>
                                                                
                                                                <?php if (!$isPast): ?>
                                                                    <span class="text-primary small">
                                                                        <i class="fas fa-calendar me-1"></i>Upcoming
                                                                    </span>
                                                                <?php elseif ($isAttended): ?>
                                                                    <span class="text-success small">
                                                                        <i class="fas fa-check me-1"></i>Attended
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-danger small">
                                                                        <i class="fas fa-times me-1"></i>Missed
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

    <!-- View Lecture Modal -->
    <div class="modal fade" id="viewLectureModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Lecture Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="lectureTitle"></h6>
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <span id="lectureDate"></span>
                        </small>
                    </div>
                    <div id="lectureDescription" class="mb-3"></div>
                    <div id="lectureMaterials"></div>
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
        function viewLecture(title, description, materials) {
            document.getElementById('lectureTitle').textContent = title;
            document.getElementById('lectureDescription').innerHTML = description ? description.replace(/\n/g, '<br>') : '<p class="text-muted">No description available.</p>';
            
            let materialsHtml = '<h6 class="fw-bold mb-2">Materials:</h6>';
            if (materials) {
                try {
                    const materialsList = JSON.parse(materials);
                    if (materialsList && materialsList.length > 0) {
                        materialsList.forEach(material => {
                            materialsHtml += `
                                <div class="material-item">
                                    <i class="fas fa-file me-2"></i>
                                    <span>${material.name || 'Material'}</span>
                                    ${material.url ? `<a href="${material.url}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">Download</a>` : ''}
                                </div>
                            `;
                        });
                    } else {
                        materialsHtml = '<p class="text-muted">No materials available.</p>';
                    }
                } catch (e) {
                    materialsHtml = '<p class="text-muted">No materials available.</p>';
                }
            } else {
                materialsHtml = '<p class="text-muted">No materials available.</p>';
            }
            
            document.getElementById('lectureMaterials').innerHTML = materialsHtml;
            new bootstrap.Modal(document.getElementById('viewLectureModal')).show();
        }
    </script>
</body>
</html> 