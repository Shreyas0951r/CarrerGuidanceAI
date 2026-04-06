<?php
require_once '../config/database.php';

requireAuth();
requirePostRequest();
requireValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    jsonResponse(['success' => false, 'message' => 'Invalid request body.'], 400);
}

$careerTitle = trim((string) ($payload['career_title'] ?? ''));
$rating = (int) ($payload['rating'] ?? 0);
$feedbackText = trim((string) ($payload['feedback_text'] ?? ''));
$assessmentId = isset($payload['assessment_id']) ? (int) $payload['assessment_id'] : null;

if ($careerTitle === '' || $rating < 1 || $rating > 5) {
    jsonResponse(['success' => false, 'message' => 'Career title and rating are required.'], 422);
}

$conn = getDBConnection();
ensureFeatureInfrastructure($conn);
$stmt = $conn->prepare('INSERT INTO feedback (user_id, assessment_id, career_title, rating, feedback_text) VALUES (?, ?, ?, ?, ?)');
$userId = (int) $_SESSION['user_id'];
$stmt->bind_param('iisis', $userId, $assessmentId, $careerTitle, $rating, $feedbackText);
$stmt->execute();
$stmt->close();
$conn->close();

jsonResponse(['success' => true, 'message' => 'Feedback submitted.']);
?>
