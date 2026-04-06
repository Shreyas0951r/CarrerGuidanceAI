<?php
require_once '../config/database.php';

requireAuth();
requirePostRequest();
requireValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!is_array($data)) {
    jsonResponse(['success' => false, 'message' => 'Invalid request body.'], 400);
}

$result = trim((string) ($data['result'] ?? ''));
$matchScore = $data['match_score'] ?? null;
$answers = $data['answers'] ?? [];

if ($result === '' || !is_array($answers)) {
    jsonResponse(['success' => false, 'message' => 'Assessment data is incomplete.'], 422);
}

$payload = [
    'responses' => $answers,
    'result' => [
        'title' => $result,
        'match_score' => is_numeric($matchScore) ? max(0, min(100, (int) $matchScore)) : null,
        'saved_at' => date('c'),
    ],
];

$encodedAnswers = json_encode($payload, JSON_UNESCAPED_UNICODE);
if ($encodedAnswers === false) {
    jsonResponse(['success' => false, 'message' => 'Unable to encode assessment data.'], 500);
}

$conn = getDBConnection();
$stmt = $conn->prepare('INSERT INTO assessments (user_id, result, answers) VALUES (?, ?, ?)');
$userId = (int) $_SESSION['user_id'];
$stmt->bind_param('iss', $userId, $result, $encodedAnswers);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    jsonResponse(['success' => false, 'message' => 'Unable to save assessment.'], 500);
}

$assessmentId = $stmt->insert_id;
$stmt->close();
$conn->close();

jsonResponse([
    'success' => true,
    'message' => 'Assessment saved successfully.',
    'assessment_id' => $assessmentId,
]);
?>
