<?php
require_once '../config/database.php';

if (isAdminLoggedIn()) {
    redirect('admin/dashboard.php');
}

if (isLoggedIn()) {
    redirect('user/dashboard.php');
}

$error = '';
$success = '';

if (isset($_GET['verified'])) {
    if ($_GET['verified'] === '1') {
        $success = 'Email verified successfully. You can login now.';
    } else {
        $error = 'Verification link is invalid or expired.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? '');

    $conn = getDBConnection();
    ensureFeatureInfrastructure($conn);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, fullname, email, password FROM users WHERE email = ? AND is_admin = 0 LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                if (isUserBlocked($conn, (int) $user['id'])) {
                    $error = 'Your account has been blocked. Please contact the administrator.';
                } else {
                    $profile = getUserProfile($conn, (int) $user['id']);
                    if (empty($profile['email_verified'])) {
                        $stmt->close();
                        $conn->close();
                        header('Location: ' . appUrl('auth/verify-notice.php?user_id=' . (int) $user['id']));
                        exit();
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = 'user';

                    $stmt->close();
                    $conn->close();
                    redirect('user/dashboard.php');
                }
            }

            if ($error === '') {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="text-white flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full p-8 bg-gray-800/80 backdrop-blur rounded-2xl shadow-2xl border border-white/10">
        <div class="text-center mb-8">
            <i class="fas fa-compass text-4xl text-indigo-500 mb-4"></i>
            <h2 class="text-3xl font-bold">Welcome Back</h2>
            <p class="text-gray-400 mt-2">Login to your account</p>
        </div>

        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>
        <?php if ($success): ?>
            <?php echo showSuccess($success); ?>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">

            <div>
                <label class="block text-sm text-gray-400 mb-2">Email Address</label>
                <input
                    type="email"
                    name="email"
                    required
                    value="<?php echo escape($_POST['email'] ?? ''); ?>"
                    class="w-full px-4 py-3 rounded-lg bg-gray-700 border border-gray-600 focus:border-indigo-500 focus:outline-none text-white"
                    placeholder="your@email.com"
                >
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-2">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full px-4 py-3 rounded-lg bg-gray-700 border border-gray-600 focus:border-indigo-500 focus:outline-none text-white"
                    placeholder="Enter your password"
                >
            </div>

            <button
                type="submit"
                class="w-full py-3 bg-indigo-500 hover:bg-indigo-600 rounded-lg font-semibold transition flex items-center justify-center gap-2"
            >
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="mt-6 flex justify-between text-sm">
            <a href="<?php echo escape(appUrl('auth/register.php')); ?>" class="text-indigo-400 hover:text-indigo-300">Create account</a>
            <a href="<?php echo escape(appUrl('auth/forgot-password.php')); ?>" class="text-indigo-400 hover:text-indigo-300">Forgot password</a>
            <a href="<?php echo escape(appUrl('auth/admin-login.php')); ?>" class="text-red-400 hover:text-red-300">
                <i class="fas fa-shield-alt mr-1"></i>Admin Login
            </a>
        </div>
    </div>
</body>
</html>
