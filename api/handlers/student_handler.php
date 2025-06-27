<?php
require_once 'config/database.php';
require_once 'config/auth.php';

class StudentHandler {
    public static function getStudents($params = []) {
        $user = Auth::getCurrentUser();
        
        // Only admins can view all students
        if ($user['user_type'] !== 'admin') {
            throw new Exception('Access denied', 403);
        }

        $students = Database::read('students');

        // Filter by status if provided
        if (isset($params['status'])) {
            $students = array_filter($students, function($student) use ($params) {
                return $student['status'] === $params['status'];
            });
        }

        // Filter by grade if provided
        if (isset($params['grade'])) {
            $students = array_filter($students, function($student) use ($params) {
                return $student['grade'] === $params['grade'];
            });
        }

        return array_values($students);
    }

    public static function createStudent($data) {
        $user = Auth::requireAdmin();

        if (!isset($data['name']) || !isset($data['email'])) {
            throw new Exception('Name and email are required', 400);
        }

        $name = trim($data['name']);
        $email = trim($data['email']);

        // Check if email already exists
        $students = Database::read('students');
        foreach ($students as $student) {
            if ($student['email'] === $email) {
                throw new Exception('Email already exists', 409);
            }
        }

        $student = [
            'id' => Database::generateId('student'),
            'name' => $name,
            'email' => $email,
            'grade' => isset($data['grade']) ? $data['grade'] : '',
            'section' => isset($data['section']) ? $data['section'] : '',
            'phone' => isset($data['phone']) ? $data['phone'] : '',
            'address' => isset($data['address']) ? $data['address'] : '',
            'status' => 'active',
            'createdAt' => date('c'),
            'createdBy' => $user['user_id']
        ];

        Database::insert('students', $student);
        return $student;
    }

    public static function updateStudent($studentId, $data) {
        $user = Auth::requireAdmin();
        
        $existingStudent = Database::findById('students', $studentId);
        if (!$existingStudent) {
            throw new Exception('Student not found', 404);
        }

        $updates = [];
        
        if (isset($data['name'])) {
            $updates['name'] = trim($data['name']);
        }
        
        if (isset($data['email'])) {
            $email = trim($data['email']);
            
            // Check if email already exists (excluding current student)
            $students = Database::read('students');
            foreach ($students as $student) {
                if ($student['id'] !== $studentId && $student['email'] === $email) {
                    throw new Exception('Email already exists', 409);
                }
            }
            
            $updates['email'] = $email;
        }
        
        if (isset($data['grade'])) {
            $updates['grade'] = $data['grade'];
        }
        
        if (isset($data['section'])) {
            $updates['section'] = $data['section'];
        }
        
        if (isset($data['phone'])) {
            $updates['phone'] = $data['phone'];
        }
        
        if (isset($data['address'])) {
            $updates['address'] = $data['address'];
        }
        
        if (isset($data['status'])) {
            if (!in_array($data['status'], ['active', 'inactive', 'graduated'])) {
                throw new Exception('Invalid status', 400);
            }
            $updates['status'] = $data['status'];
        }

        return Database::update('students', $studentId, $updates);
    }

    public static function deleteStudent($studentId) {
        $user = Auth::requireAdmin();
        
        $existingStudent = Database::findById('students', $studentId);
        if (!$existingStudent) {
            throw new Exception('Student not found', 404);
        }

        Database::delete('students', $studentId);
        return ['message' => 'Student deleted successfully'];
    }

    public static function getStudentById($studentId) {
        $user = Auth::getCurrentUser();
        
        $student = Database::findById('students', $studentId);
        if (!$student) {
            throw new Exception('Student not found', 404);
        }

        // Students can only view their own profile
        if ($user['user_type'] === 'student' && $user['user_id'] !== $studentId) {
            throw new Exception('Access denied', 403);
        }

        return $student;
    }

    public static function getCount() {
        return Database::count('students');
    }

    public static function searchStudents($query) {
        $user = Auth::requireAdmin();
        
        $students = Database::read('students');
        $results = [];

        foreach ($students as $student) {
            if (stripos($student['name'], $query) !== false ||
                stripos($student['email'], $query) !== false ||
                stripos($student['grade'], $query) !== false) {
                $results[] = $student;
            }
        }

        return $results;
    }
}
?> 