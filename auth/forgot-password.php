<?php
require_once '../config/database.php';

$message = '';
$error = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $conn = getDBConnection();
        ensureFeatureInfrastructure($conn);
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $insert = $conn->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
            $userId = (int) $user['id'];
            $insert->bind_param('iss', $userId, $token, $expiresAt);
            $insert->execute();
            $insert->close();

            $resetLink = appUrl('auth/reset-password.php?token=' . urlencode($token));
        }

        $message = 'If the email exists, a reset link has been generated for local testing.';
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center px-4">
    <div class="max-w-lg w-full bg-white/10 border border-white/10 rounded-3xl p-8 backdrop-blur">
        <h1 class="text-3xl font-bold mb-3">Forgot Password</h1>
        <p class="text-gray-300 mb-6">Enter your email to generate a local reset link.</p>

        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        <?php if ($message): ?>
            <?php echo showSuccess($message); ?>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
            <input type="email" name="email" required class="w-full p-3 rounded-xl bg-white/10 border border-white/10" placeholder="you@example.com">
            <button type="submit" class="w-full px-5 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-600 font-semibold">Generate Reset Link</button>
        </form>

        <?php if ($resetLink): ?>
            <div class="mt-6 p-4 rounded-2xl bg-black/30">
                <div class="text-sm text-gray-400 mb-2">Local reset link</div>
                <a href="<?php echo escape($resetLink); ?>" class="break-all text-indigo-300 hover:text-white"><?php echo escape($resetLink); ?></a>
            </div>
        <?php endif; ?>

        <a href="<?php echo escape(appUrl('auth/login.php')); ?>" class="inline-block mt-6 text-sm text-indigo-300 hover:text-white">Back to login</a>
    </div>
</body>
</html>
