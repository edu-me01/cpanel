<?php
require_once 'config/database.php';
require_once 'config/auth.php';

class AuthHandler {
    public static function login($data) {
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['userType'])) {
            throw new Exception('Email, password, and userType are required', 400);
        }

        $email = trim($data['email']);
        $password = $data['password'];
        $userType = $data['userType'];

        // Validate userType
        if (!in_array($userType, ['admin', 'student'])) {
            throw new Exception('Invalid userType. Must be admin or student', 400);
        }

        // Find user by email and userType
        $users = Database::read('users');
        $user = null;

        foreach ($users as $u) {
            if ($u['email'] === $email && $u['userType'] === $userType) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            throw new Exception('Invalid credentials', 401);
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new Exception('Invalid credentials', 401);
        }

        // Generate JWT token
        $token = Auth::generateToken($user['id'], $user['userType']);

        return [
            'success' => true,
            'token' => $token,
            'userType' => $user['userType'],
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'userType' => $user['userType']
            ]
        ];
    }

    public static function register($data) {
        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name']) || !isset($data['userType'])) {
            throw new Exception('Email, password, name, and userType are required', 400);
        }

        $email = trim($data['email']);
        $password = $data['password'];
        $name = trim($data['name']);
        $userType = $data['userType'];

        // Validate userType
        if (!in_array($userType, ['admin', 'student'])) {
            throw new Exception('Invalid userType. Must be admin or student', 400);
        }

        // Check if email already exists
        $users = Database::read('users');
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                throw new Exception('Email already exists', 409);
            }
        }

        // Create new user
        $newUser = [
            'id' => Database::generateId($userType),
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
            'userType' => $userType,
            'createdAt' => date('c')
        ];

        Database::insert('users', $newUser);

        // Generate token
        $token = Auth::generateToken($newUser['id'], $newUser['userType']);

        return [
            'success' => true,
            'token' => $token,
            'userType' => $newUser['userType'],
            'user' => [
                'id' => $newUser['id'],
                'email' => $newUser['email'],
                'name' => $newUser['name'],
                'userType' => $newUser['userType']
            ]
        ];
    }

    public static function verifyToken($token) {
        $payload = Auth::verifyToken($token);
        if (!$payload) {
            throw new Exception('Invalid token', 401);
        }

        return [
            'success' => true,
            'user' => $payload
        ];
    }

    public static function getProfile() {
        $user = Auth::getCurrentUser();
        $users = Database::read('users');
        
        foreach ($users as $u) {
            if ($u['id'] === $user['user_id']) {
                return [
                    'success' => true,
                    'user' => [
                        'id' => $u['id'],
                        'email' => $u['email'],
                        'name' => $u['name'],
                        'userType' => $u['userType'],
                        'createdAt' => $u['createdAt']
                    ]
                ];
            }
        }

        throw new Exception('User not found', 404);
    }
}
?> 