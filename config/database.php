<?php
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'career_ai');
define('APP_BASE_PATH', '/' . basename(dirname(__DIR__)));
define('CUSTOM_CONTENT_FILE', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'custom_content.json');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die('Database connection failed.');
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function sanitize($data) {
    return htmlspecialchars(trim((string) $data), ENT_QUOTES, 'UTF-8');
}

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function appUrl($path = '') {
    $normalized = ltrim((string) $path, '/');
    if ($normalized === '') {
        return APP_BASE_PATH . '/';
    }

    return APP_BASE_PATH . '/' . $normalized;
}

function redirect($path) {
    header('Location: ' . appUrl($path));
    exit();
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']) && ($_SESSION['user_type'] ?? null) === 'user';
}

function isAdminLoggedIn() {
    return !empty($_SESSION['admin_id']) && ($_SESSION['user_type'] ?? null) === 'admin';
}

function isAnyLoggedIn() {
    return isLoggedIn() || isAdminLoggedIn();
}

function requireAuth() {
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect('auth/admin-login.php');
    }
}

function showError($message) {
    return "<div class='bg-red-500/20 border border-red-500/50 text-red-200 px-4 py-3 rounded mb-4'><i class='fas fa-exclamation-circle mr-2'></i>" . escape($message) . '</div>';
}

function showSuccess($message) {
    return "<div class='bg-green-500/20 border border-green-500/50 text-green-200 px-4 py-3 rounded mb-4'><i class='fas fa-check-circle mr-2'></i>" . escape($message) . '</div>';
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireValidCsrfToken($token) {
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die('Invalid request token.');
    }
}

function requirePostRequest() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        die('Method not allowed.');
    }
}

function jsonResponse($payload, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit();
}

function destroyCurrentSession() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
    }

    session_destroy();
}

function decodeAssessmentPayload($rawAnswers) {
    if (!is_string($rawAnswers) || $rawAnswers === '') {
        return [];
    }

    $decoded = json_decode($rawAnswers, true);
    return is_array($decoded) ? $decoded : [];
}

function getAssessmentResponses(array $assessment) {
    $payload = decodeAssessmentPayload($assessment['answers'] ?? '');
    if (isset($payload['responses']) && is_array($payload['responses'])) {
        return $payload['responses'];
    }

    return $payload;
}

function getAssessmentMatchScore(array $assessment) {
    if (isset($assessment['match_score']) && is_numeric($assessment['match_score'])) {
        return (int) $assessment['match_score'];
    }

    $payload = decodeAssessmentPayload($assessment['answers'] ?? '');
    if (isset($payload['result']['match_score']) && is_numeric($payload['result']['match_score'])) {
        return (int) $payload['result']['match_score'];
    }

    return null;
}

function getAssessmentCareerName(array $assessment) {
    if (!empty($assessment['recommended_career'])) {
        return $assessment['recommended_career'];
    }

    if (!empty($assessment['result'])) {
        return $assessment['result'];
    }

    $payload = decodeAssessmentPayload($assessment['answers'] ?? '');
    if (!empty($payload['result']['title'])) {
        return $payload['result']['title'];
    }

    return 'Unknown Career';
}

function ensureFeatureInfrastructure($conn) {
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $queries = [
        "CREATE TABLE IF NOT EXISTS user_profiles (
            user_id INT NOT NULL PRIMARY KEY,
            education_level VARCHAR(100) DEFAULT '',
            interests TEXT,
            goals TEXT,
            preferred_country VARCHAR(100) DEFAULT '',
            preferred_language VARCHAR(20) DEFAULT 'en',
            theme_preference VARCHAR(20) DEFAULT 'dark',
            email_verified TINYINT(1) NOT NULL DEFAULT 0,
            verification_token VARCHAR(128) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS user_status (
            user_id INT NOT NULL PRIMARY KEY,
            is_blocked TINYINT(1) NOT NULL DEFAULT 0,
            blocked_at DATETIME DEFAULT NULL,
            block_reason VARCHAR(255) DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS feedback (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            assessment_id INT DEFAULT NULL,
            career_title VARCHAR(255) NOT NULL,
            rating TINYINT NOT NULL,
            feedback_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
    ];

    foreach ($queries as $query) {
        $conn->query($query);
    }

    $initialized = true;
}

function getUserProfile($conn, $userId) {
    ensureFeatureInfrastructure($conn);

    $stmt = $conn->prepare('SELECT * FROM user_profiles WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($profile) {
        return $profile;
    }

    $insert = $conn->prepare('INSERT INTO user_profiles (user_id) VALUES (?)');
    $insert->bind_param('i', $userId);
    $insert->execute();
    $insert->close();

    return [
        'user_id' => $userId,
        'education_level' => '',
        'interests' => '',
        'goals' => '',
        'preferred_country' => '',
        'preferred_language' => 'en',
        'theme_preference' => 'dark',
        'email_verified' => 0,
        'verification_token' => null,
    ];
}

function getUserStatus($conn, $userId) {
    ensureFeatureInfrastructure($conn);

    $stmt = $conn->prepare('SELECT * FROM user_status WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $status = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($status) {
        return $status;
    }

    $insert = $conn->prepare('INSERT INTO user_status (user_id) VALUES (?)');
    $insert->bind_param('i', $userId);
    $insert->execute();
    $insert->close();

    return [
        'user_id' => $userId,
        'is_blocked' => 0,
        'blocked_at' => null,
        'block_reason' => null,
    ];
}

function isUserBlocked($conn, $userId) {
    $status = getUserStatus($conn, $userId);
    return !empty($status['is_blocked']);
}

function ensureCustomContentFile() {
    $directory = dirname(CUSTOM_CONTENT_FILE);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    if (!file_exists(CUSTOM_CONTENT_FILE)) {
        $seed = [
            'careers' => [],
            'questions' => [],
        ];
        file_put_contents(CUSTOM_CONTENT_FILE, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function loadCustomContent() {
    ensureCustomContentFile();
    $raw = file_get_contents(CUSTOM_CONTENT_FILE);
    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        return ['careers' => [], 'questions' => []];
    }

    $decoded['careers'] = is_array($decoded['careers'] ?? null) ? $decoded['careers'] : [];
    $decoded['questions'] = is_array($decoded['questions'] ?? null) ? $decoded['questions'] : [];
    return $decoded;
}

function saveCustomContent(array $content) {
    ensureCustomContentFile();
    $payload = [
        'careers' => array_values(is_array($content['careers'] ?? null) ? $content['careers'] : []),
        'questions' => array_values(is_array($content['questions'] ?? null) ? $content['questions'] : []),
    ];

    file_put_contents(CUSTOM_CONTENT_FILE, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function nextCustomContentId(array $items) {
    $ids = array_map(function ($item) {
        return (int) ($item['id'] ?? 0);
    }, $items);

    return empty($ids) ? 1 : (max($ids) + 1);
}
?>
