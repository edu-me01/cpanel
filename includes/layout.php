<?php
require_once 'config.php';

// Get current page and user
$currentPage = $_GET['page'] ?? 'dashboard';
$user = getCurrentUser();

// Determine if user is admin or student
$isAdmin = isAdmin();
$userType = $isAdmin ? 'admin' : 'student';

// Get page title
$pageTitles = [
    'dashboard' => 'Dashboard',
    'students' => 'Students Management',
    'tasks' => 'Tasks Management',
    'attendance' => 'Attendance Management',
    'submissions' => 'Submissions Management',
    'analytics' => 'Analytics',
    'lectures' => 'Lectures',
    'settings' => 'Settings'
];

$pageTitle = $pageTitles[$currentPage] ?? ucfirst($currentPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Task Manager</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
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

        .nav-tabs {
            border: none;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 8px;
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            padding: 12px 24px;
            margin: 0 4px;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .nav-tabs .nav-link.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stats-card.success {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }

        .stats-card.info {
            background: linear-gradient(135deg, var(--info-color), #0891b2);
        }

        .stats-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .stats-label {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
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

        .badge-modern {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            border-radius: 4px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-tasks me-2"></i>Task Manager
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($user['name'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?page=profile"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="?page=settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
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
                            <?php if ($isAdmin): ?>
                                <!-- Admin Navigation -->
                                <a href="?page=dashboard" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                                    <span>Dashboard</span>
                                </a>
                                <a href="?page=students" class="nav-item <?php echo $currentPage === 'students' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-users"></i></div>
                                    <span>Students</span>
                                </a>
                                <a href="?page=tasks" class="nav-item <?php echo $currentPage === 'tasks' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-tasks"></i></div>
                                    <span>Tasks</span>
                                </a>
                                <a href="?page=attendance" class="nav-item <?php echo $currentPage === 'attendance' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
                                    <span>Attendance</span>
                                </a>
                                <a href="?page=submissions" class="nav-item <?php echo $currentPage === 'submissions' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
                                    <span>Submissions</span>
                                </a>
                                <a href="?page=analytics" class="nav-item <?php echo $currentPage === 'analytics' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
                                    <span>Analytics</span>
                                </a>
                            <?php else: ?>
                                <!-- Student Navigation -->
                                <a href="?page=dashboard" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                                    <span>Dashboard</span>
                                </a>
                                <a href="?page=tasks" class="nav-item <?php echo $currentPage === 'tasks' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-tasks"></i></div>
                                    <span>My Tasks</span>
                                </a>
                                <a href="?page=attendance" class="nav-item <?php echo $currentPage === 'attendance' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
                                    <span>Attendance</span>
                                </a>
                                <a href="?page=submissions" class="nav-item <?php echo $currentPage === 'submissions' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
                                    <span>My Submissions</span>
                                </a>
                                <a href="?page=lectures" class="nav-item <?php echo $currentPage === 'lectures' ? 'active' : ''; ?>">
                                    <div class="nav-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                                    <span>Lectures</span>
                                </a>
                            <?php endif; ?>
                            
                            <a href="?page=settings" class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                                <div class="nav-icon"><i class="fas fa-cog"></i></div>
                                <span>Settings</span>
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
                    <?php
                    $pageFile = "includes/sections/{$currentPage}.php";
                    if (file_exists($pageFile)) {
                        include $pageFile;
                    } else {
                        echo '<div class="alert alert-warning">Page not found: ' . htmlspecialchars($currentPage) . '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Simple JavaScript for interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add active class to current nav item
            const currentPage = '<?php echo $currentPage; ?>';
            const navItems = document.querySelectorAll('.nav-item');
            
            navItems.forEach(item => {
                if (item.getAttribute('href') === `?page=${currentPage}`) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
