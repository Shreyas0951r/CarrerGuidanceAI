<?php
require_once '../config/database.php';

$token = trim($_GET['token'] ?? '');
if ($token === '') {
    redirect('auth/login.php');
}

$conn = getDBConnection();
ensureFeatureInfrastructure($conn);
$stmt = $conn->prepare('UPDATE user_profiles SET email_verified = 1, verification_token = NULL WHERE verification_token = ?');
$stmt->bind_param('s', $token);
$stmt->execute();
$updated = $stmt->affected_rows > 0;
$stmt->close();
$conn->close();

if ($updated) {
    header('Location: ' . appUrl('auth/login.php?verified=1'));
    exit();
}

header('Location: ' . appUrl('auth/login.php?verified=0'));
exit();
?>
