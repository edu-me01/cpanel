<?php
// Submissions section content - redirect to appropriate page based on user type
if (isAdmin()) {
    header('Location: admin/submissions.php');
} else {
    header('Location: student/submissions.php');
}
exit;
?> 