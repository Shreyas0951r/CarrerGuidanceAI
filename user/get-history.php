<?php
require_once '../config/database.php';

requireAuth();

$conn = getDBConnection();
$stmt = $conn->prepare('SELECT id, result, answers, created_at FROM assessments WHERE user_id = ? ORDER BY created_at DESC');
$userId = (int) $_SESSION['user_id'];
$stmt->bind_param('i', $userId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$history = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'career' => getAssessmentCareerName($row),
        'match_score' => getAssessmentMatchScore($row),
        'created_at' => $row['created_at'],
    ];
}, $rows);

jsonResponse(['success' => true, 'history' => $history]);
?>
