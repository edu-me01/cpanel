<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include necessary files
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'handlers/auth_handler.php';
require_once 'handlers/task_handler.php';
require_once 'handlers/student_handler.php';
require_once 'handlers/attendance_handler.php';
require_once 'handlers/submission_handler.php';
require_once 'handlers/attendance_token_handler.php';
require_once 'handlers/lecture_handler.php';
require_once 'handlers/feedback_handler.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

// Remove trailing slash
$path = rtrim($path, '/');

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Route the request
try {
    switch ($path) {
        case '/login':
            if ($method === 'POST') {
                $response = AuthHandler::login($input);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/tasks':
            if ($method === 'GET') {
                $response = TaskHandler::getTasks($_GET);
            } elseif ($method === 'POST') {
                $response = TaskHandler::createTask($input);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case (preg_match('/^\/tasks\/(.+)$/', $path, $matches) ? true : false):
            $taskId = $matches[1];
            if ($method === 'PUT') {
                if (strpos($path, '/complete') !== false) {
                    $response = TaskHandler::completeTask($taskId, $input);
                } else {
                    $response = TaskHandler::updateTask($taskId, $input);
                }
            } elseif ($method === 'DELETE') {
                $response = TaskHandler::deleteTask($taskId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/students':
            if ($method === 'GET') {
                $response = StudentHandler::getStudents($_GET);
            } elseif ($method === 'POST') {
                $response = StudentHandler::createStudent($input);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case (preg_match('/^\/students\/(.+)$/', $path, $matches) ? true : false):
            $studentId = $matches[1];
            if ($method === 'PUT') {
                $response = StudentHandler::updateStudent($studentId, $input);
            } elseif ($method === 'DELETE') {
                $response = StudentHandler::deleteStudent($studentId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/attendance':
            if ($method === 'GET') {
                $response = AttendanceHandler::getAttendance($_GET);
            } elseif ($method === 'POST') {
                $response = AttendanceHandler::createAttendance($input);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case (preg_match('/^\/attendance\/(.+)$/', $path, $matches) ? true : false):
            $attendanceId = $matches[1];
            if ($method === 'PUT') {
                $response = AttendanceHandler::updateAttendance($attendanceId, $input);
            } elseif ($method === 'DELETE') {
                $response = AttendanceHandler::deleteAttendance($attendanceId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/submissions':
            if ($method === 'GET') {
                $response = SubmissionHandler::getSubmissions($_GET);
            } elseif ($method === 'POST') {
                $response = SubmissionHandler::createSubmission($input);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case (preg_match('/^\/submissions\/(.+)$/', $path, $matches) ? true : false):
            $submissionId = $matches[1];
            if ($method === 'PUT') {
                $response = SubmissionHandler::updateSubmission($submissionId, $input);
            } elseif ($method === 'DELETE') {
                $response = SubmissionHandler::deleteSubmission($submissionId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        // Attendance Token Management
        case '/attendance-token/generate':
            if ($method === 'POST') {
                $response = AttendanceTokenHandler::generateAttendanceToken();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/attendance-token/finish':
            if ($method === 'POST') {
                $response = AttendanceTokenHandler::finishAttendanceToken();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/attendance-token/status':
            if ($method === 'GET') {
                $response = AttendanceTokenHandler::getAttendanceTokenStatus();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/attendance-token/validate':
            if ($method === 'POST') {
                if (!isset($input['token'])) {
                    throw new Exception('Token is required', 400);
                }
                $response = AttendanceTokenHandler::validateAttendanceToken($input['token']);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/attendance-token/use':
            if ($method === 'POST') {
                if (!isset($input['token']) || !isset($input['studentId'])) {
                    throw new Exception('Token and studentId are required', 400);
                }
                $response = AttendanceTokenHandler::markTokenAsUsed($input['token'], $input['studentId']);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        // Lecture Management
        case '/lectures':
            if ($method === 'GET') {
                $response = LectureHandler::getLectures($_GET);
            } elseif ($method === 'POST') {
                $response = LectureHandler::createLecture($input);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case (preg_match('/^\/lectures\/(.+)$/', $path, $matches) ? true : false):
            $lectureId = $matches[1];
            if ($method === 'GET') {
                $response = LectureHandler::getLectureById($lectureId);
            } elseif ($method === 'PUT') {
                $response = LectureHandler::updateLecture($lectureId, $input);
            } elseif ($method === 'DELETE') {
                $response = LectureHandler::deleteLecture($lectureId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/lectures/today':
            if ($method === 'GET') {
                $response = LectureHandler::getTodayLectures();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/lectures/yesterday':
            if ($method === 'GET') {
                $response = LectureHandler::getPreviousDayLectures();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/lectures/categories':
            if ($method === 'GET') {
                $response = LectureHandler::getLectureCategories();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        // Feedback Management
        case '/feedbacks':
            if ($method === 'GET') {
                $response = FeedbackHandler::getFeedbacks($_GET);
            } elseif ($method === 'POST') {
                $response = FeedbackHandler::createFeedback($input);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case (preg_match('/^\/feedbacks\/(.+)$/', $path, $matches) ? true : false):
            $feedbackId = $matches[1];
            if ($method === 'GET') {
                $response = FeedbackHandler::getFeedbackById($feedbackId);
            } elseif ($method === 'PUT') {
                $response = FeedbackHandler::updateFeedback($feedbackId, $input);
            } elseif ($method === 'DELETE') {
                $response = FeedbackHandler::deleteFeedback($feedbackId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/feedbacks/questions':
            if ($method === 'GET') {
                $type = $_GET['type'] ?? 'daily';
                $response = FeedbackHandler::getFeedbackQuestions($type);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/feedbacks/stats':
            if ($method === 'GET') {
                $type = $_GET['type'] ?? null;
                $response = FeedbackHandler::getFeedbackStats($type);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/feedbacks/check-daily':
            if ($method === 'GET') {
                $user = Auth::getCurrentUser();
                $response = ['required' => FeedbackHandler::checkDailyFeedbackRequired($user['user_id'])];
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/feedbacks/check-final':
            if ($method === 'GET') {
                $user = Auth::getCurrentUser();
                $response = ['required' => FeedbackHandler::checkFinalFeedbackRequired($user['user_id'])];
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        case '/health':
            if ($method === 'GET') {
                $response = [
                    'status' => 'OK',
                    'timestamp' => date('c'),
                    'data' => [
                        'tasks' => TaskHandler::getCount(),
                        'students' => StudentHandler::getCount(),
                        'attendance' => AttendanceHandler::getCount(),
                        'submissions' => SubmissionHandler::getCount(),
                        'lectures' => LectureHandler::getCount(),
                        'feedbacks' => FeedbackHandler::getCount()
                    ]
                ];
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;

        default:
            throw new Exception('Endpoint not found', 404);
    }

    echo json_encode($response);

} catch (Exception $e) {
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $statusCode === 500 ? 'Internal server error' : null
    ]);
}
?> 