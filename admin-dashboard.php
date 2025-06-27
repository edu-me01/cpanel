<?php
// Admin Dashboard Main Page
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container" id="dashboard">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <!-- Main dashboard content goes here -->
            <div id="dashboardSection" class="section active">
                <div class="section-header">
                    <div class="header-content">
                        <h1>Dashboard Overview</h1>
                        <p>Welcome back! Here's what's happening today.</p>
                    </div>
                </div>
                <!-- Stats Cards, etc. -->
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js" defer></script>
    <script src="js/realtime-updates.js" defer></script>
    <script src="js/students.js" defer></script>
    <script src="js/tasks.js" defer></script>
    <script src="js/attendance.js" defer></script>
    <script src="js/attendance-token.js" defer></script>
    <script src="js/submissions.js" defer></script>
    <script src="js/settings.js" defer></script>
    <script src="js/main.js" defer></script>
</body>
</html> 