<?php
require_once 'config/database.php';
require_once 'config/auth.php';

class AttendanceHandler {
    public static function getAttendance($params = []) {
        $user = Auth::getCurrentUser();
        $attendance = Database::read('attendance');

        // Filter by studentId if provided
        if (isset($params['studentId'])) {
            $attendance = array_filter($attendance, function($record) use ($params) {
                return $record['studentId'] === $params['studentId'];
            });
        }

        // Filter by date if provided
        if (isset($params['date'])) {
            $attendance = array_filter($attendance, function($record) use ($params) {
                return $record['date'] === $params['date'];
            });
        }

        // Filter by status if provided
        if (isset($params['status'])) {
            $attendance = array_filter($attendance, function($record) use ($params) {
                return $record['status'] === $params['status'];
            });
        }

        // Students can only see their own attendance
        if ($user['user_type'] === 'student') {
            $attendance = array_filter($attendance, function($record) use ($user) {
                return $record['studentId'] === $user['user_id'];
            });
        }

        return array_values($attendance);
    }

    public static function createAttendance($data) {
        $user = Auth::getCurrentUser();
        
        // Only admins can create attendance records
        if ($user['user_type'] !== 'admin') {
            throw new Exception('Access denied', 403);
        }

        if (!isset($data['studentId']) || !isset($data['date']) || !isset($data['status'])) {
            throw new Exception('Student ID, date, and status are required', 400);
        }

        // Check if attendance record already exists for this student and date
        $existingAttendance = Database::findBy('attendance', 'studentId', $data['studentId']);
        foreach ($existingAttendance as $record) {
            if ($record['date'] === $data['date']) {
                throw new Exception('Attendance record already exists for this student and date', 409);
            }
        }

        $attendance = [
            'id' => Database::generateId('attendance'),
            'studentId' => $data['studentId'],
            'studentName' => isset($data['studentName']) ? $data['studentName'] : '',
            'date' => $data['date'],
            'status' => $data['status'], // present, absent, late, excused
            'timeIn' => isset($data['timeIn']) ? $data['timeIn'] : null,
            'timeOut' => isset($data['timeOut']) ? $data['timeOut'] : null,
            'notes' => isset($data['notes']) ? $data['notes'] : '',
            'createdBy' => $user['user_id'],
            'createdAt' => date('c')
        ];

        // Validate status
        if (!in_array($attendance['status'], ['present', 'absent', 'late', 'excused'])) {
            throw new Exception('Invalid status. Must be present, absent, late, or excused', 400);
        }

        Database::insert('attendance', $attendance);
        return $attendance;
    }

    public static function updateAttendance($attendanceId, $data) {
        $user = Auth::getCurrentUser();
        
        // Only admins can update attendance records
        if ($user['user_type'] !== 'admin') {
            throw new Exception('Access denied', 403);
        }
        
        $existingAttendance = Database::findById('attendance', $attendanceId);
        if (!$existingAttendance) {
            throw new Exception('Attendance record not found', 404);
        }

        $updates = [];
        
        if (isset($data['status'])) {
            if (!in_array($data['status'], ['present', 'absent', 'late', 'excused'])) {
                throw new Exception('Invalid status. Must be present, absent, late, or excused', 400);
            }
            $updates['status'] = $data['status'];
        }
        
        if (isset($data['timeIn'])) {
            $updates['timeIn'] = $data['timeIn'];
        }
        
        if (isset($data['timeOut'])) {
            $updates['timeOut'] = $data['timeOut'];
        }
        
        if (isset($data['notes'])) {
            $updates['notes'] = $data['notes'];
        }

        return Database::update('attendance', $attendanceId, $updates);
    }

    public static function deleteAttendance($attendanceId) {
        $user = Auth::requireAdmin();
        
        $existingAttendance = Database::findById('attendance', $attendanceId);
        if (!$existingAttendance) {
            throw new Exception('Attendance record not found', 404);
        }

        Database::delete('attendance', $attendanceId);
        return ['message' => 'Attendance record deleted successfully'];
    }

    public static function getCount() {
        return Database::count('attendance');
    }

    public static function getAttendanceById($attendanceId) {
        $user = Auth::getCurrentUser();
        
        $attendance = Database::findById('attendance', $attendanceId);
        if (!$attendance) {
            throw new Exception('Attendance record not found', 404);
        }

        // Students can only view their own attendance
        if ($user['user_type'] === 'student' && $attendance['studentId'] !== $user['user_id']) {
            throw new Exception('Access denied', 403);
        }

        return $attendance;
    }

    public static function getAttendanceReport($params = []) {
        $user = Auth::requireAdmin();
        
        $attendance = Database::read('attendance');
        $students = Database::read('students');
        
        $report = [];
        
        foreach ($students as $student) {
            $studentAttendance = array_filter($attendance, function($record) use ($student) {
                return $record['studentId'] === $student['id'];
            });
            
            $totalDays = count($studentAttendance);
            $presentDays = count(array_filter($studentAttendance, function($record) {
                return $record['status'] === 'present';
            }));
            $absentDays = count(array_filter($studentAttendance, function($record) {
                return $record['status'] === 'absent';
            }));
            $lateDays = count(array_filter($studentAttendance, function($record) {
                return $record['status'] === 'late';
            }));
            
            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
            
            $report[] = [
                'studentId' => $student['id'],
                'studentName' => $student['name'],
                'grade' => $student['grade'],
                'totalDays' => $totalDays,
                'presentDays' => $presentDays,
                'absentDays' => $absentDays,
                'lateDays' => $lateDays,
                'attendanceRate' => $attendanceRate
            ];
        }
        
        return $report;
    }
}
?> 