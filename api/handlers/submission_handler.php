<?php
require_once 'config/database.php';
require_once 'config/auth.php';

class SubmissionHandler {
    public static function getSubmissions($params = []) {
        $user = Auth::getCurrentUser();
        $submissions = Database::read('submissions');

        // Filter by taskId if provided
        if (isset($params['taskId'])) {
            $submissions = array_filter($submissions, function($submission) use ($params) {
                return $submission['taskId'] === $params['taskId'];
            });
        }

        // Filter by studentId if provided
        if (isset($params['studentId'])) {
            $submissions = array_filter($submissions, function($submission) use ($params) {
                return $submission['studentId'] === $params['studentId'];
            });
        }

        // Filter by status if provided
        if (isset($params['status'])) {
            $submissions = array_filter($submissions, function($submission) use ($params) {
                return $submission['status'] === $params['status'];
            });
        }

        // Students can only see their own submissions
        if ($user['user_type'] === 'student') {
            $submissions = array_filter($submissions, function($submission) use ($user) {
                return $submission['studentId'] === $user['user_id'];
            });
        }

        return array_values($submissions);
    }

    public static function createSubmission($data) {
        $user = Auth::getCurrentUser();
        
        // Students can create submissions for tasks assigned to them
        if ($user['user_type'] === 'student') {
            if (!isset($data['taskId'])) {
                throw new Exception('Task ID is required', 400);
            }
            
            // Check if task is assigned to this student
            $task = Database::findById('tasks', $data['taskId']);
            if (!$task) {
                throw new Exception('Task not found', 404);
            }
            
            if (isset($task['assignedTo']) && $task['assignedTo'] !== $user['user_id']) {
                throw new Exception('You can only submit for tasks assigned to you', 403);
            }
        }

        if (!isset($data['taskId']) || !isset($data['content'])) {
            throw new Exception('Task ID and content are required', 400);
        }

        // Check if submission already exists for this student and task
        $existingSubmissions = Database::findBy('submissions', 'taskId', $data['taskId']);
        foreach ($existingSubmissions as $submission) {
            if ($submission['studentId'] === $user['user_id']) {
                throw new Exception('Submission already exists for this task', 409);
            }
        }

        $submission = [
            'id' => Database::generateId('submission'),
            'taskId' => $data['taskId'],
            'studentId' => $user['user_id'],
            'studentName' => isset($data['studentName']) ? $data['studentName'] : '',
            'content' => $data['content'],
            'fileUrl' => isset($data['fileUrl']) ? $data['fileUrl'] : null,
            'status' => 'submitted', // submitted, reviewed, approved, rejected
            'grade' => isset($data['grade']) ? $data['grade'] : null,
            'feedback' => isset($data['feedback']) ? $data['feedback'] : '',
            'submittedAt' => date('c'),
            'reviewedAt' => null,
            'reviewedBy' => null
        ];

        Database::insert('submissions', $submission);
        return $submission;
    }

    public static function updateSubmission($submissionId, $data) {
        $user = Auth::getCurrentUser();
        
        $existingSubmission = Database::findById('submissions', $submissionId);
        if (!$existingSubmission) {
            throw new Exception('Submission not found', 404);
        }

        // Students can only update their own submissions before they're reviewed
        if ($user['user_type'] === 'student') {
            if ($existingSubmission['studentId'] !== $user['user_id']) {
                throw new Exception('Access denied', 403);
            }
            
            if ($existingSubmission['status'] !== 'submitted') {
                throw new Exception('Cannot update reviewed submission', 403);
            }
        }

        $updates = [];
        
        if (isset($data['content'])) {
            $updates['content'] = $data['content'];
        }
        
        if (isset($data['fileUrl'])) {
            $updates['fileUrl'] = $data['fileUrl'];
        }
        
        // Only admins can update status, grade, and feedback
        if ($user['user_type'] === 'admin') {
            if (isset($data['status'])) {
                if (!in_array($data['status'], ['submitted', 'reviewed', 'approved', 'rejected'])) {
                    throw new Exception('Invalid status', 400);
                }
                $updates['status'] = $data['status'];
                $updates['reviewedAt'] = date('c');
                $updates['reviewedBy'] = $user['user_id'];
            }
            
            if (isset($data['grade'])) {
                $updates['grade'] = $data['grade'];
            }
            
            if (isset($data['feedback'])) {
                $updates['feedback'] = $data['feedback'];
            }
        }

        return Database::update('submissions', $submissionId, $updates);
    }

    public static function deleteSubmission($submissionId) {
        $user = Auth::getCurrentUser();
        
        $existingSubmission = Database::findById('submissions', $submissionId);
        if (!$existingSubmission) {
            throw new Exception('Submission not found', 404);
        }

        // Students can only delete their own submissions
        if ($user['user_type'] === 'student') {
            if ($existingSubmission['studentId'] !== $user['user_id']) {
                throw new Exception('Access denied', 403);
            }
        }

        Database::delete('submissions', $submissionId);
        return ['message' => 'Submission deleted successfully'];
    }

    public static function getCount() {
        return Database::count('submissions');
    }

    public static function getSubmissionById($submissionId) {
        $user = Auth::getCurrentUser();
        
        $submission = Database::findById('submissions', $submissionId);
        if (!$submission) {
            throw new Exception('Submission not found', 404);
        }

        // Students can only view their own submissions
        if ($user['user_type'] === 'student' && $submission['studentId'] !== $user['user_id']) {
            throw new Exception('Access denied', 403);
        }

        return $submission;
    }

    public static function reviewSubmission($submissionId, $data) {
        $user = Auth::requireAdmin();
        
        $existingSubmission = Database::findById('submissions', $submissionId);
        if (!$existingSubmission) {
            throw new Exception('Submission not found', 404);
        }

        if (!isset($data['status']) || !isset($data['feedback'])) {
            throw new Exception('Status and feedback are required', 400);
        }

        if (!in_array($data['status'], ['reviewed', 'approved', 'rejected'])) {
            throw new Exception('Invalid status. Must be reviewed, approved, or rejected', 400);
        }

        $updates = [
            'status' => $data['status'],
            'feedback' => $data['feedback'],
            'reviewedAt' => date('c'),
            'reviewedBy' => $user['user_id']
        ];

        if (isset($data['grade'])) {
            $updates['grade'] = $data['grade'];
        }

        return Database::update('submissions', $submissionId, $updates);
    }
}
?> 