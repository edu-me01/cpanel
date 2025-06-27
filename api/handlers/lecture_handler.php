<?php
require_once 'config/database.php';
require_once 'config/auth.php';

class LectureHandler {
    public static function getLectures($params = []) {
        $user = Auth::getCurrentUser();
        
        // Students can only access lectures if they have attended today
        if ($user['user_type'] === 'student') {
            if (!self::hasStudentAttendedToday($user['user_id'])) {
                throw new Exception('You must attend today\'s session before accessing lectures', 403);
            }
        }
        
        $lectures = Database::read('lectures');
        
        // Filter by date if provided
        if (isset($params['date'])) {
            $lectures = array_filter($lectures, function($lecture) use ($params) {
                return $lecture['date'] === $params['date'];
            });
        }
        
        // Filter by category if provided
        if (isset($params['category'])) {
            $lectures = array_filter($lectures, function($lecture) use ($params) {
                return $lecture['category'] === $params['category'];
            });
        }
        
        // Students can only see lectures from today and previous days
        if ($user['user_type'] === 'student') {
            $today = date('Y-m-d');
            $lectures = array_filter($lectures, function($lecture) use ($today) {
                return $lecture['date'] <= $today;
            });
        }
        
        return array_values($lectures);
    }
    
    public static function createLecture($data) {
        $user = Auth::requireAdmin();
        
        if (!isset($data['title']) || !isset($data['content']) || !isset($data['date'])) {
            throw new Exception('Title, content, and date are required', 400);
        }
        
        $lecture = [
            'id' => Database::generateId('lecture'),
            'title' => trim($data['title']),
            'content' => $data['content'],
            'category' => isset($data['category']) ? $data['category'] : 'general',
            'date' => $data['date'],
            'duration' => isset($data['duration']) ? $data['duration'] : 60, // minutes
            'videoUrl' => isset($data['videoUrl']) ? $data['videoUrl'] : null,
            'attachments' => isset($data['attachments']) ? $data['attachments'] : [],
            'createdBy' => $user['user_id'],
            'createdAt' => date('c'),
            'status' => 'active' // active, archived
        ];
        
        Database::insert('lectures', $lecture);
        return $lecture;
    }
    
    public static function updateLecture($lectureId, $data) {
        $user = Auth::requireAdmin();
        
        $existingLecture = Database::findById('lectures', $lectureId);
        if (!$existingLecture) {
            throw new Exception('Lecture not found', 404);
        }
        
        $updates = [];
        
        if (isset($data['title'])) {
            $updates['title'] = trim($data['title']);
        }
        
        if (isset($data['content'])) {
            $updates['content'] = $data['content'];
        }
        
        if (isset($data['category'])) {
            $updates['category'] = $data['category'];
        }
        
        if (isset($data['date'])) {
            $updates['date'] = $data['date'];
        }
        
        if (isset($data['duration'])) {
            $updates['duration'] = $data['duration'];
        }
        
        if (isset($data['videoUrl'])) {
            $updates['videoUrl'] = $data['videoUrl'];
        }
        
        if (isset($data['attachments'])) {
            $updates['attachments'] = $data['attachments'];
        }
        
        if (isset($data['status'])) {
            if (!in_array($data['status'], ['active', 'archived'])) {
                throw new Exception('Invalid status', 400);
            }
            $updates['status'] = $data['status'];
        }
        
        return Database::update('lectures', $lectureId, $updates);
    }
    
    public static function deleteLecture($lectureId) {
        $user = Auth::requireAdmin();
        
        $existingLecture = Database::findById('lectures', $lectureId);
        if (!$existingLecture) {
            throw new Exception('Lecture not found', 404);
        }
        
        Database::delete('lectures', $lectureId);
        return ['message' => 'Lecture deleted successfully'];
    }
    
    public static function getLectureById($lectureId) {
        $user = Auth::getCurrentUser();
        
        $lecture = Database::findById('lectures', $lectureId);
        if (!$lecture) {
            throw new Exception('Lecture not found', 404);
        }
        
        // Students can only access lectures if they have attended today
        if ($user['user_type'] === 'student') {
            if (!self::hasStudentAttendedToday($user['user_id'])) {
                throw new Exception('You must attend today\'s session before accessing lectures', 403);
            }
            
            // Students can only see lectures from today and previous days
            $today = date('Y-m-d');
            if ($lecture['date'] > $today) {
                throw new Exception('Access denied', 403);
            }
        }
        
        return $lecture;
    }
    
    public static function getCount() {
        return Database::count('lectures');
    }
    
    public static function getLectureCategories() {
        $lectures = Database::read('lectures');
        $categories = [];
        
        foreach ($lectures as $lecture) {
            if (!in_array($lecture['category'], $categories)) {
                $categories[] = $lecture['category'];
            }
        }
        
        return $categories;
    }
    
    public static function getTodayLectures() {
        $today = date('Y-m-d');
        $lectures = Database::read('lectures');
        
        $todayLectures = array_filter($lectures, function($lecture) use ($today) {
            return $lecture['date'] === $today && $lecture['status'] === 'active';
        });
        
        return array_values($todayLectures);
    }
    
    public static function getPreviousDayLectures() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lectures = Database::read('lectures');
        
        $yesterdayLectures = array_filter($lectures, function($lecture) use ($yesterday) {
            return $lecture['date'] === $yesterday && $lecture['status'] === 'active';
        });
        
        return array_values($yesterdayLectures);
    }
    
    private static function hasStudentAttendedToday($studentId) {
        $attendance = Database::read('attendance');
        $today = date('Y-m-d');
        
        foreach ($attendance as $record) {
            if ($record['studentId'] === $studentId && 
                $record['date'] === $today && 
                $record['status'] === 'present') {
                return true;
            }
        }
        
        return false;
    }
}
?> 