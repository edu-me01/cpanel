<?php
require_once 'config/database.php';
require_once 'config/auth.php';

class FeedbackHandler {
    public static function getFeedbacks($params = []) {
        $user = Auth::getCurrentUser();
        $feedbacks = Database::read('feedbacks');
        
        // Filter by student if student user
        if ($user['user_type'] === 'student') {
            $feedbacks = array_filter($feedbacks, function($feedback) use ($user) {
                return $feedback['studentId'] === $user['user_id'];
            });
        }
        
        // Filter by type if provided
        if (isset($params['type'])) {
            $feedbacks = array_filter($feedbacks, function($feedback) use ($params) {
                return $feedback['type'] === $params['type'];
            });
        }
        
        // Filter by date if provided
        if (isset($params['date'])) {
            $feedbacks = array_filter($feedbacks, function($feedback) use ($params) {
                return $feedback['date'] === $params['date'];
            });
        }
        
        return array_values($feedbacks);
    }
    
    public static function createFeedback($data) {
        $user = Auth::getCurrentUser();
        
        if (!isset($data['type']) || !isset($data['answers'])) {
            throw new Exception('Type and answers are required', 400);
        }
        
        // Check if student has already submitted feedback for this type and date
        $existingFeedbacks = Database::read('feedbacks');
        $today = date('Y-m-d');
        
        foreach ($existingFeedbacks as $feedback) {
            if ($feedback['studentId'] === $user['user_id'] && 
                $feedback['type'] === $data['type'] && 
                $feedback['date'] === $today) {
                throw new Exception('You have already submitted feedback for today', 409);
            }
        }
        
        $feedback = [
            'id' => Database::generateId('feedback'),
            'studentId' => $user['user_id'],
            'studentName' => isset($data['studentName']) ? $data['studentName'] : '',
            'type' => $data['type'], // daily, final
            'date' => $today,
            'answers' => $data['answers'],
            'submittedAt' => date('c'),
            'status' => 'submitted' // submitted, reviewed
        ];
        
        Database::insert('feedbacks', $feedback);
        return $feedback;
    }
    
    public static function updateFeedback($feedbackId, $data) {
        $user = Auth::getCurrentUser();
        
        $existingFeedback = Database::findById('feedbacks', $feedbackId);
        if (!$existingFeedback) {
            throw new Exception('Feedback not found', 404);
        }
        
        // Students can only update their own feedback if it's not reviewed
        if ($user['user_type'] === 'student') {
            if ($existingFeedback['studentId'] !== $user['user_id']) {
                throw new Exception('Access denied', 403);
            }
            
            if ($existingFeedback['status'] === 'reviewed') {
                throw new Exception('Cannot update reviewed feedback', 403);
            }
        }
        
        $updates = [];
        
        if (isset($data['answers'])) {
            $updates['answers'] = $data['answers'];
        }
        
        // Only admins can update status
        if ($user['user_type'] === 'admin' && isset($data['status'])) {
            if (!in_array($data['status'], ['submitted', 'reviewed'])) {
                throw new Exception('Invalid status', 400);
            }
            $updates['status'] = $data['status'];
        }
        
        return Database::update('feedbacks', $feedbackId, $updates);
    }
    
    public static function deleteFeedback($feedbackId) {
        $user = Auth::getCurrentUser();
        
        $existingFeedback = Database::findById('feedbacks', $feedbackId);
        if (!$existingFeedback) {
            throw new Exception('Feedback not found', 404);
        }
        
        // Students can only delete their own feedback
        if ($user['user_type'] === 'student') {
            if ($existingFeedback['studentId'] !== $user['user_id']) {
                throw new Exception('Access denied', 403);
            }
        }
        
        Database::delete('feedbacks', $feedbackId);
        return ['message' => 'Feedback deleted successfully'];
    }
    
    public static function getFeedbackById($feedbackId) {
        $user = Auth::getCurrentUser();
        
        $feedback = Database::findById('feedbacks', $feedbackId);
        if (!$feedback) {
            throw new Exception('Feedback not found', 404);
        }
        
        // Students can only view their own feedback
        if ($user['user_type'] === 'student' && $feedback['studentId'] !== $user['user_id']) {
            throw new Exception('Access denied', 403);
        }
        
        return $feedback;
    }
    
    public static function getCount() {
        return Database::count('feedbacks');
    }
    
    public static function checkDailyFeedbackRequired($studentId) {
        $feedbacks = Database::read('feedbacks');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Check if student has submitted daily feedback for yesterday
        foreach ($feedbacks as $feedback) {
            if ($feedback['studentId'] === $studentId && 
                $feedback['type'] === 'daily' && 
                $feedback['date'] === $yesterday) {
                return false; // No feedback required
            }
        }
        
        return true; // Feedback required
    }
    
    public static function checkFinalFeedbackRequired($studentId) {
        $feedbacks = Database::read('feedbacks');
        $today = date('Y-m-d');
        
        // Check if student has submitted final feedback for today
        foreach ($feedbacks as $feedback) {
            if ($feedback['studentId'] === $studentId && 
                $feedback['type'] === 'final' && 
                $feedback['date'] === $today) {
                return false; // No feedback required
            }
        }
        
        return true; // Feedback required
    }
    
    public static function getFeedbackQuestions($type) {
        $questions = [
            'daily' => [
                'understanding' => 'How well did you understand today\'s material? (1-5)',
                'difficulty' => 'How difficult was today\'s content? (1-5)',
                'engagement' => 'How engaged were you during the session? (1-5)',
                'questions' => 'What questions do you have about today\'s material?',
                'suggestions' => 'Any suggestions for improving the session?'
            ],
            'final' => [
                'overall_satisfaction' => 'Overall, how satisfied are you with the training? (1-5)',
                'knowledge_gained' => 'How much knowledge do you feel you gained? (1-5)',
                'practical_applicability' => 'How applicable do you find the knowledge gained? (1-5)',
                'instructor_rating' => 'How would you rate the instructor? (1-5)',
                'course_structure' => 'How would you rate the course structure? (1-5)',
                'recommendation' => 'Would you recommend this training to others? (Yes/No)',
                'improvements' => 'What could be improved in this training?',
                'additional_comments' => 'Any additional comments or feedback?'
            ]
        ];
        
        return isset($questions[$type]) ? $questions[$type] : [];
    }
    
    public static function getFeedbackStats($type = null) {
        $feedbacks = Database::read('feedbacks');
        
        if ($type) {
            $feedbacks = array_filter($feedbacks, function($feedback) use ($type) {
                return $feedback['type'] === $type;
            });
        }
        
        $stats = [
            'total' => count($feedbacks),
            'submitted' => 0,
            'reviewed' => 0,
            'today' => 0,
            'yesterday' => 0
        ];
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        foreach ($feedbacks as $feedback) {
            if ($feedback['status'] === 'submitted') $stats['submitted']++;
            if ($feedback['status'] === 'reviewed') $stats['reviewed']++;
            if ($feedback['date'] === $today) $stats['today']++;
            if ($feedback['date'] === $yesterday) $stats['yesterday']++;
        }
        
        return $stats;
    }
}
?> 