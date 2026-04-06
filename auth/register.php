<?php
require_once '../config/database.php';

if (isLoggedIn()) {
    redirect('user/dashboard.php');
}

if (isAdminLoggedIn()) {
    redirect('admin/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? '');

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname === '' || mb_strlen($fullname) < 3) {
        $error = 'Enter your full name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $conn = getDBConnection();
        ensureFeatureInfrastructure($conn);

        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (fullname, email, password, is_admin) VALUES (?, ?, ?, 0)');
            $stmt->bind_param('sss', $fullname, $email, $hashedPassword);

            if ($stmt->execute()) {
                $newUserId = $stmt->insert_id;
                $verificationToken = bin2hex(random_bytes(32));
                $profile = $conn->prepare('INSERT INTO user_profiles (user_id, preferred_language, theme_preference, email_verified, verification_token) VALUES (?, ?, ?, 0, ?)');
                $defaultLanguage = 'en';
                $defaultTheme = 'dark';
                $profile->bind_param('isss', $newUserId, $defaultLanguage, $defaultTheme, $verificationToken);
                $profile->execute();
                $profile->close();

                $stmt->close();
                $check->close();
                $conn->close();
                header('Location: ' . appUrl('auth/verify-notice.php?user_id=' . $newUserId));
                exit();
            }

            $error = 'Unable to create your account right now.';
            $stmt->close();
        }

        $check->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-indigo-900 via-purple-900 to-slate-900 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white/10 backdrop-blur-md p-10 rounded-2xl w-full max-w-md text-white shadow-xl border border-white/10">
        <h2 class="text-3xl font-bold mb-6 text-center">Create Account</h2>

        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>

        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">

            <input
                type="text"
                name="fullname"
                placeholder="Full Name"
                value="<?php echo escape($_POST['fullname'] ?? ''); ?>"
                required
                class="w-full p-3 rounded-lg bg-white/20 placeholder-white outline-none border border-white/10"
            >

            <input
                type="email"
                name="email"
                placeholder="Email"
                value="<?php echo escape($_POST['email'] ?? ''); ?>"
                required
                class="w-full p-3 rounded-lg bg-white/20 placeholder-white outline-none border border-white/10"
            >

            <input
                type="password"
                name="password"
                placeholder="Password"
                required
                minlength="8"
                class="w-full p-3 rounded-lg bg-white/20 placeholder-white outline-none border border-white/10"
            >

            <button
                type="submit"
                class="w-full bg-purple-500 hover:bg-purple-600 p-3 rounded-lg font-semibold transition"
            >
                Register
            </button>
        </form>

        <p class="text-center mt-4 text-sm">
            Already have an account?
            <a href="<?php echo escape(appUrl('auth/login.php')); ?>" class="text-indigo-300 hover:text-white">Login</a>
        </p>
    </div>
</body>
</html>
