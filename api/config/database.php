<?php
class Database {
    private static $dataDir = '../data/';
    private static $files = [
        'tasks' => 'tasks.json',
        'students' => 'students.json',
        'attendance' => 'attendance.json',
        'submissions' => 'submissions.json',
        'users' => 'users.json',
        'attendance_tokens' => 'attendance_tokens.json',
        'lectures' => 'lectures.json',
        'feedbacks' => 'feedbacks.json'
    ];

    public static function init() {
        // Create data directory if it doesn't exist
        if (!is_dir(self::$dataDir)) {
            mkdir(self::$dataDir, 0755, true);
        }

        // Initialize data files if they don't exist
        foreach (self::$files as $key => $file) {
            $filePath = self::$dataDir . $file;
            if (!file_exists($filePath)) {
                file_put_contents($filePath, json_encode([]));
            }
        }

        // Initialize default users if users.json is empty
        $usersFile = self::$dataDir . self::$files['users'];
        $users = json_decode(file_get_contents($usersFile), true);
        if (empty($users)) {
            $defaultUsers = [
                [
                    'id' => 'admin_1',
                    'email' => 'admin@school.com',
                    'password' => password_hash('admin123', PASSWORD_DEFAULT),
                    'userType' => 'admin',
                    'name' => 'Administrator',
                    'createdAt' => date('c')
                ],
                [
                    'id' => 'student_1',
                    'email' => 'student@school.com',
                    'password' => password_hash('student123', PASSWORD_DEFAULT),
                    'userType' => 'student',
                    'name' => 'Demo Student',
                    'createdAt' => date('c')
                ]
            ];
            file_put_contents($usersFile, json_encode($defaultUsers, JSON_PRETTY_PRINT));
        }

        // Initialize sample lectures if lectures.json is empty
        $lecturesFile = self::$dataDir . self::$files['lectures'];
        $lectures = json_decode(file_get_contents($lecturesFile), true);
        if (empty($lectures)) {
            $sampleLectures = [
                [
                    'id' => 'lecture_1',
                    'title' => 'Introduction to Web Development',
                    'content' => 'This lecture covers the basics of HTML, CSS, and JavaScript.',
                    'category' => 'web-development',
                    'date' => date('Y-m-d'),
                    'duration' => 60,
                    'videoUrl' => null,
                    'attachments' => [],
                    'createdBy' => 'admin_1',
                    'createdAt' => date('c'),
                    'status' => 'active'
                ],
                [
                    'id' => 'lecture_2',
                    'title' => 'Advanced JavaScript Concepts',
                    'content' => 'Learn about closures, promises, and async/await.',
                    'category' => 'web-development',
                    'date' => date('Y-m-d', strtotime('-1 day')),
                    'duration' => 90,
                    'videoUrl' => null,
                    'attachments' => [],
                    'createdBy' => 'admin_1',
                    'createdAt' => date('c', strtotime('-1 day')),
                    'status' => 'active'
                ]
            ];
            file_put_contents($lecturesFile, json_encode($sampleLectures, JSON_PRETTY_PRINT));
        }
    }

    public static function read($table) {
        if (!isset(self::$files[$table])) {
            throw new Exception("Unknown table: $table");
        }

        $filePath = self::$dataDir . self::$files[$table];
        $data = file_get_contents($filePath);
        return json_decode($data, true) ?: [];
    }

    public static function write($table, $data) {
        if (!isset(self::$files[$table])) {
            throw new Exception("Unknown table: $table");
        }

        $filePath = self::$dataDir . self::$files[$table];
        return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public static function insert($table, $item) {
        $data = self::read($table);
        $data[] = $item;
        self::write($table, $data);
        return $item;
    }

    public static function update($table, $id, $updates) {
        $data = self::read($table);
        $found = false;

        foreach ($data as &$item) {
            if ($item['id'] === $id) {
                $item = array_merge($item, $updates);
                $item['updatedAt'] = date('c');
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Item not found with id: $id");
        }

        self::write($table, $data);
        return $item;
    }

    public static function delete($table, $id) {
        $data = self::read($table);
        $found = false;

        foreach ($data as $key => $item) {
            if ($item['id'] === $id) {
                unset($data[$key]);
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception("Item not found with id: $id");
        }

        self::write($table, array_values($data));
        return true;
    }

    public static function findById($table, $id) {
        $data = self::read($table);
        
        foreach ($data as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        
        return null;
    }

    public static function findBy($table, $field, $value) {
        $data = self::read($table);
        $results = [];
        
        foreach ($data as $item) {
            if (isset($item[$field]) && $item[$field] === $value) {
                $results[] = $item;
            }
        }
        
        return $results;
    }

    public static function count($table) {
        $data = self::read($table);
        return count($data);
    }

    public static function generateId($prefix = 'item') {
        return $prefix . '_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    }
}

// Initialize database on first load
Database::init();
?> 