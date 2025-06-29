<?php
// Tasks section content - redirect to appropriate page based on user type
if (isAdmin()) {
    header('Location: admin/tasks.php');
} else {
    header('Location: student/tasks.php');
}
exit;
?> 