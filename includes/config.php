<?php
// Database configuration
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

    public static function getAll($table) {
        return self::read($table);
    }

    public static function find($table, $id) {
        return self::findById($table, $id);
    }

    public static function count($table) {
        $data = self::read($table);
        return count($data);
    }

    public static function generateId($prefix = 'item') {
        return $prefix . '_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    }
}

// Initialize database
Database::init();

// Session management
session_start();

// Authentication helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: admin-dashboard.php');
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $users = Database::read('users');
    foreach ($users as $user) {
        if ($user['id'] === $_SESSION['user_id']) {
            return $user;
        }
    }
    
    return null;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?> 