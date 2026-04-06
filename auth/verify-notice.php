<?php
require_once '../config/database.php';

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
if ($userId <= 0) {
    redirect('auth/login.php');
}

$conn = getDBConnection();
ensureFeatureInfrastructure($conn);
$stmt = $conn->prepare('SELECT u.fullname, u.email, p.email_verified, p.verification_token FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id WHERE u.id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) {
    redirect('auth/login.php');
}

if (!empty($user['email_verified'])) {
    redirect('auth/login.php');
}

$verificationLink = appUrl('auth/verify-email.php?token=' . urlencode((string) $user['verification_token']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center px-4">
    <div class="max-w-2xl w-full bg-white/10 border border-white/10 rounded-3xl p-8 backdrop-blur">
        <h1 class="text-3xl font-bold mb-3">Verify your email</h1>
        <p class="text-gray-300 mb-6">
            Account created for <span class="font-semibold text-white"><?php echo escape($user['email']); ?></span>.
            In production this link would be sent by email. For your local project, open the link below manually.
        </p>
        <div class="bg-black/30 rounded-2xl p-4 mb-6 break-all text-indigo-300">
            <a href="<?php echo escape($verificationLink); ?>" class="hover:text-white"><?php echo escape($verificationLink); ?></a>
        </div>
        <div class="flex gap-3">
            <a href="<?php echo escape($verificationLink); ?>" class="px-5 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-600 font-semibold">Verify Now</a>
            <a href="<?php echo escape(appUrl('auth/login.php')); ?>" class="px-5 py-3 rounded-xl border border-white/20 hover:bg-white/10">Back to Login</a>
        </div>
    </div>
</body>
</html>
