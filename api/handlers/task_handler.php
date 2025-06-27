<?php
require_once 'config/database.php';
require_once 'config/auth.php';

class TaskHandler {
    public static function getTasks($params = []) {
        $user = Auth::getCurrentUser();
        $tasks = Database::read('tasks');

        // Filter tasks based on user type and parameters
        if ($user['user_type'] === 'student') {
            // Students can only see tasks assigned to them or unassigned tasks
            $tasks = array_filter($tasks, function($task) use ($user) {
                return !isset($task['assignedTo']) || 
                       $task['assignedTo'] === $user['user_id'] ||
                       $task['assignedTo'] === null;
            });
        }

        // Filter by studentId if provided
        if (isset($params['studentId'])) {
            $tasks = array_filter($tasks, function($task) use ($params) {
                return !isset($task['assignedTo']) || 
                       $task['assignedTo'] === $params['studentId'];
            });
        }

        // Filter by status if provided
        if (isset($params['status'])) {
            $tasks = array_filter($tasks, function($task) use ($params) {
                return $task['status'] === $params['status'];
            });
        }

        // Filter by priority if provided
        if (isset($params['priority'])) {
            $tasks = array_filter($tasks, function($task) use ($params) {
                return $task['priority'] === $params['priority'];
            });
        }

        return array_values($tasks);
    }

    public static function createTask($data) {
        $user = Auth::requireAdmin();

        if (!isset($data['title']) || !isset($data['dueDate'])) {
            throw new Exception('Title and due date are required', 400);
        }

        $task = [
            'id' => Database::generateId('task'),
            'title' => trim($data['title']),
            'description' => isset($data['description']) ? trim($data['description']) : '',
            'priority' => isset($data['priority']) ? $data['priority'] : 'medium',
            'dueDate' => $data['dueDate'],
            'dueTime' => isset($data['dueTime']) ? $data['dueTime'] : null,
            'createdBy' => $user['user_id'],
            'createdByName' => isset($data['createdByName']) ? $data['createdByName'] : 'Admin',
            'status' => 'pending',
            'createdAt' => date('c'),
            'assignedTo' => isset($data['assignedTo']) ? $data['assignedTo'] : null
        ];

        // Validate priority
        if (!in_array($task['priority'], ['low', 'medium', 'high'])) {
            throw new Exception('Invalid priority. Must be low, medium, or high', 400);
        }

        Database::insert('tasks', $task);
        return $task;
    }

    public static function updateTask($taskId, $data) {
        $user = Auth::requireAdmin();
        
        $existingTask = Database::findById('tasks', $taskId);
        if (!$existingTask) {
            throw new Exception('Task not found', 404);
        }

        $updates = [];
        
        if (isset($data['title'])) {
            $updates['title'] = trim($data['title']);
        }
        
        if (isset($data['description'])) {
            $updates['description'] = trim($data['description']);
        }
        
        if (isset($data['priority'])) {
            if (!in_array($data['priority'], ['low', 'medium', 'high'])) {
                throw new Exception('Invalid priority. Must be low, medium, or high', 400);
            }
            $updates['priority'] = $data['priority'];
        }
        
        if (isset($data['dueDate'])) {
            $updates['dueDate'] = $data['dueDate'];
        }
        
        if (isset($data['dueTime'])) {
            $updates['dueTime'] = $data['dueTime'];
        }
        
        if (isset($data['status'])) {
            if (!in_array($data['status'], ['pending', 'in-progress', 'completed', 'overdue'])) {
                throw new Exception('Invalid status', 400);
            }
            $updates['status'] = $data['status'];
        }

        if (isset($data['assignedTo'])) {
            $updates['assignedTo'] = $data['assignedTo'];
        }

        return Database::update('tasks', $taskId, $updates);
    }

    public static function deleteTask($taskId) {
        $user = Auth::requireAdmin();
        
        $existingTask = Database::findById('tasks', $taskId);
        if (!$existingTask) {
            throw new Exception('Task not found', 404);
        }

        Database::delete('tasks', $taskId);
        return ['message' => 'Task deleted successfully'];
    }

    public static function completeTask($taskId, $data) {
        $user = Auth::getCurrentUser();
        
        $existingTask = Database::findById('tasks', $taskId);
        if (!$existingTask) {
            throw new Exception('Task not found', 404);
        }

        // Check if user can complete this task
        if ($user['user_type'] === 'student') {
            if (isset($existingTask['assignedTo']) && 
                $existingTask['assignedTo'] !== $user['user_id']) {
                throw new Exception('You can only complete tasks assigned to you', 403);
            }
        }

        $updates = [
            'status' => 'completed',
            'completedAt' => date('c'),
            'completedBy' => isset($data['completedBy']) ? $data['completedBy'] : $user['user_id']
        ];

        return Database::update('tasks', $taskId, $updates);
    }

    public static function getCount() {
        return Database::count('tasks');
    }

    public static function getTaskById($taskId) {
        $user = Auth::getCurrentUser();
        
        $task = Database::findById('tasks', $taskId);
        if (!$task) {
            throw new Exception('Task not found', 404);
        }

        // Check if user can access this task
        if ($user['user_type'] === 'student') {
            if (isset($task['assignedTo']) && 
                $task['assignedTo'] !== $user['user_id']) {
                throw new Exception('Access denied', 403);
            }
        }

        return $task;
    }
}
?> 