<?php
require_once 'config/database.php';
require_once 'config/auth.php';

class AttendanceTokenHandler {
    public static function generateAttendanceToken() {
        $user = Auth::requireAdmin();
        
        // Check if there's already an active token for today
        $tokens = Database::read('attendance_tokens');
        $today = date('Y-m-d');
        
        foreach ($tokens as $token) {
            if ($token['date'] === $today && $token['status'] === 'active') {
                throw new Exception('An active attendance token already exists for today', 409);
            }
        }
        
        // Generate new token
        $tokenData = [
            'id' => Database::generateId('attendance_token'),
            'token' => self::generateSecureToken(),
            'date' => $today,
            'status' => 'active', // active, finished
            'createdBy' => $user['user_id'],
            'createdAt' => date('c'),
            'expiresAt' => date('c', strtotime('tomorrow 00:00:00')), // Expires at midnight
            'usedBy' => [] // Track which students have used this token
        ];
        
        Database::insert('attendance_tokens', $tokenData);
        return $tokenData;
    }
    
    public static function finishAttendanceToken() {
        $user = Auth::requireAdmin();
        
        $tokens = Database::read('attendance_tokens');
        $today = date('Y-m-d');
        
        foreach ($tokens as $token) {
            if ($token['date'] === $today && $token['status'] === 'active') {
                $updates = [
                    'status' => 'finished',
                    'finishedBy' => $user['user_id'],
                    'finishedAt' => date('c')
                ];
                
                Database::update('attendance_tokens', $token['id'], $updates);
                return ['message' => 'Attendance finished successfully'];
            }
        }
        
        throw new Exception('No active attendance token found for today', 404);
    }
    
    public static function validateAttendanceToken($token) {
        $tokens = Database::read('attendance_tokens');
        $today = date('Y-m-d');
        
        foreach ($tokens as $tokenData) {
            if ($tokenData['token'] === $token && $tokenData['date'] === $today) {
                // Check if token is still active
                if ($tokenData['status'] !== 'active') {
                    throw new Exception('Attendance token is no longer active', 410);
                }
                
                // Check if token has expired
                if (strtotime($tokenData['expiresAt']) < time()) {
                    throw new Exception('Attendance token has expired', 410);
                }
                
                return $tokenData;
            }
        }
        
        throw new Exception('Invalid attendance token', 401);
    }
    
    public static function markTokenAsUsed($token, $studentId) {
        $tokens = Database::read('attendance_tokens');
        
        foreach ($tokens as $tokenData) {
            if ($tokenData['token'] === $token) {
                // Check if student already used this token
                if (in_array($studentId, $tokenData['usedBy'])) {
                    throw new Exception('Student has already used this attendance token', 409);
                }
                
                // Add student to used list
                $usedBy = $tokenData['usedBy'];
                $usedBy[] = $studentId;
                
                $updates = ['usedBy' => $usedBy];
                Database::update('attendance_tokens', $tokenData['id'], $updates);
                return true;
            }
        }
        
        throw new Exception('Token not found', 404);
    }
    
    public static function getCurrentAttendanceToken() {
        $tokens = Database::read('attendance_tokens');
        $today = date('Y-m-d');
        
        foreach ($tokens as $token) {
            if ($token['date'] === $today && $token['status'] === 'active') {
                return $token;
            }
        }
        
        return null;
    }
    
    public static function getAttendanceTokenStatus() {
        $tokens = Database::read('attendance_tokens');
        $today = date('Y-m-d');
        
        foreach ($tokens as $token) {
            if ($token['date'] === $today) {
                return [
                    'status' => $token['status'],
                    'date' => $token['date'],
                    'usedCount' => count($token['usedBy']),
                    'token' => $token['status'] === 'active' ? $token['token'] : null
                ];
            }
        }
        
        return ['status' => 'none', 'date' => $today, 'usedCount' => 0, 'token' => null];
    }
    
    private static function generateSecureToken() {
        return 'att_' . bin2hex(random_bytes(16));
    }
}
?> 