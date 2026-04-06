<?php
require_once '../config/database.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';

if ($token === '') {
    redirect('auth/forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $conn = getDBConnection();
        ensureFeatureInfrastructure($conn);
        $stmt = $conn->prepare('SELECT user_id FROM password_reset_tokens WHERE token = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $error = 'This reset link is invalid or expired.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $userId = (int) $row['user_id'];
            $update->bind_param('si', $hash, $userId);
            $update->execute();
            $update->close();

            $markUsed = $conn->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE token = ?');
            $markUsed->bind_param('s', $token);
            $markUsed->execute();
            $markUsed->close();

            $success = 'Password updated successfully. You can login now.';
        }

        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center px-4">
    <div class="max-w-lg w-full bg-white/10 border border-white/10 rounded-3xl p-8 backdrop-blur">
        <h1 class="text-3xl font-bold mb-3">Reset Password</h1>

        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                <input type="hidden" name="token" value="<?php echo escape($token); ?>">
                <input type="password" name="password" required minlength="8" class="w-full p-3 rounded-xl bg-white/10 border border-white/10" placeholder="New password">
                <input type="password" name="confirm_password" required minlength="8" class="w-full p-3 rounded-xl bg-white/10 border border-white/10" placeholder="Confirm password">
                <button type="submit" class="w-full px-5 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-600 font-semibold">Reset Password</button>
            </form>
        <?php else: ?>
            <a href="<?php echo escape(appUrl('auth/login.php')); ?>" class="inline-block mt-6 text-sm text-indigo-300 hover:text-white">Go to login</a>
        <?php endif; ?>
    </div>
</body>
</html>
