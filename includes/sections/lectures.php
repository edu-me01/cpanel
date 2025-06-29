<?php
// Lectures section content - redirect to student-lectures.php
if (isAdmin()) {
    // Admins don't have lectures page, redirect to dashboard
    header('Location: ?page=dashboard');
} else {
    header('Location: student/lectures.php');
}
exit;
?> 