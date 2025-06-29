<?php
// Analytics section content - redirect to admin-analytics.php
if (isAdmin()) {
    header('Location: admin/analytics.php');
} else {
    // Students don't have analytics, redirect to dashboard
    header('Location: ?page=dashboard');
}
exit;
?> 