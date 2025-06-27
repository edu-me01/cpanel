<?php
class Auth {
    private static $secretKey = 'your-secret-key-change-this-in-production';
    private static $algorithm = 'HS256';

    public static function generateToken($userId, $userType) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'user_type' => $userType,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secretKey, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public static function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        $expectedSignature = hash_hmac('sha256', $header . "." . $payload, self::$secretKey, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));

        if ($signature !== $expectedSignature) {
            return false;
        }

        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        if (!$payloadData) {
            return false;
        }

        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }

        return $payloadData;
    }

    public static function getAuthHeader() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }
        return null;
    }

    public static function getTokenFromHeader($authHeader) {
        if (!$authHeader) {
            return null;
        }

        $parts = explode(' ', $authHeader);
        if (count($parts) !== 2 || $parts[0] !== 'Bearer') {
            return null;
        }

        return $parts[1];
    }

    public static function authenticate() {
        $authHeader = self::getAuthHeader();
        $token = self::getTokenFromHeader($authHeader);

        if (!$token) {
            throw new Exception('Access token required', 401);
        }

        $payload = self::verifyToken($token);
        if (!$payload) {
            throw new Exception('Invalid token', 403);
        }

        return $payload;
    }

    public static function requireAdmin() {
        $user = self::authenticate();
        if ($user['user_type'] !== 'admin') {
            throw new Exception('Admin access required', 403);
        }
        return $user;
    }

    public static function requireStudent() {
        $user = self::authenticate();
        if ($user['user_type'] !== 'student') {
            throw new Exception('Student access required', 403);
        }
        return $user;
    }

    public static function getCurrentUser() {
        return self::authenticate();
    }
}
?> 