<?php
require_once '../config/database.php';

if (isAdminLoggedIn()) {
    redirect('admin/dashboard.php');
}

if (isLoggedIn()) {
    destroyCurrentSession();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? '');

    $conn = getDBConnection();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid admin email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, fullname, email, password, role FROM admins WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_name'] = $admin['fullname'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['user_type'] = 'admin';

                $update = $conn->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?');
                $update->bind_param('i', $admin['id']);
                $update->execute();
                $update->close();

                $stmt->close();
                $conn->close();
                redirect('admin/dashboard.php');
            }
        }

        $error = 'Invalid admin credentials.';
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
    <title>Admin Login - CareerPath AI</title>
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
            <div class="w-16 h-16 mx-auto bg-red-500/20 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-shield-alt text-3xl text-red-400"></i>
            </div>
            <h2 class="text-3xl font-bold">Admin Portal</h2>
            <p class="text-gray-400 mt-2">Authorized personnel only</p>
        </div>

        <?php if ($error): ?>
            <?php echo showError($error); ?>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">

            <div>
                <label class="block text-sm text-gray-400 mb-2">Admin Email</label>
                <input
                    type="email"
                    name="email"
                    required
                    value="<?php echo escape($_POST['email'] ?? ''); ?>"
                    class="w-full px-4 py-3 rounded-lg bg-gray-700 border border-gray-600 focus:border-red-500 focus:outline-none text-white"
                    placeholder="admin@example.com"
                >
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-2">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full px-4 py-3 rounded-lg bg-gray-700 border border-gray-600 focus:border-red-500 focus:outline-none text-white"
                    placeholder="Enter your password"
                >
            </div>

            <button
                type="submit"
                class="w-full py-3 bg-red-500 hover:bg-red-600 rounded-lg font-semibold transition flex items-center justify-center gap-2"
            >
                <i class="fas fa-lock"></i> Secure Login
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="<?php echo escape(appUrl('auth/login.php')); ?>" class="text-gray-400 hover:text-white text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Back to User Login
            </a>
        </div>
    </div>
</body>
</html>
