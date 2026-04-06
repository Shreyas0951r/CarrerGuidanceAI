<?php
require_once '../config/database.php';
requireAuth();

$conn = getDBConnection();
ensureFeatureInfrastructure($conn);
$userId = (int) $_SESSION['user_id'];
$profile = getUserProfile($conn, $userId);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? '');
    $educationLevel = trim($_POST['education_level'] ?? '');
    $interests = trim($_POST['interests'] ?? '');
    $goals = trim($_POST['goals'] ?? '');
    $preferredCountry = trim($_POST['preferred_country'] ?? '');
    $preferredLanguage = trim($_POST['preferred_language'] ?? 'en');
    $themePreference = trim($_POST['theme_preference'] ?? 'dark');

    $stmt = $conn->prepare('UPDATE user_profiles SET education_level = ?, interests = ?, goals = ?, preferred_country = ?, preferred_language = ?, theme_preference = ? WHERE user_id = ?');
    $stmt->bind_param('ssssssi', $educationLevel, $interests, $goals, $preferredCountry, $preferredLanguage, $themePreference, $userId);
    $stmt->execute();
    $stmt->close();

    $profile = getUserProfile($conn, $userId);
    $message = 'Profile updated successfully.';
}

$conn->close();

$currentHour = (int) date('G');
$defaultGreeting = 'Good Evening';
if ($currentHour < 12) {
    $defaultGreeting = 'Good Morning';
} elseif ($currentHour < 17) {
    $defaultGreeting = 'Good Afternoon';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen px-4 py-10">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="<?php echo escape(appUrl('user/dashboard.php')); ?>" class="text-indigo-300 hover:text-white text-sm">Back to dashboard</a>
                <h1 class="text-3xl font-bold mt-2">Your Profile</h1>
                <p id="profileGreetingMessage" class="text-gray-300 mt-2" data-username="<?php echo escape($_SESSION['fullname'] ?? 'User'); ?>">
                    <?php echo escape($defaultGreeting); ?>, <?php echo escape($_SESSION['fullname'] ?? 'User'); ?>!
                </p>
            </div>
            <a href="<?php echo escape(appUrl('auth/forgot-password.php')); ?>" class="px-4 py-2 rounded-xl border border-white/20 hover:bg-white/10 text-sm">Forgot password</a>
        </div>

        <?php if ($message): ?>
            <?php echo showSuccess($message); ?>
        <?php endif; ?>

        <form method="POST" id="profileForm" class="space-y-6 bg-white/10 border border-white/10 rounded-3xl p-8">
            <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">

            <div>
                <label class="block text-sm text-gray-300 mb-2">Education level</label>
                <input type="text" name="education_level" value="<?php echo escape($profile['education_level'] ?? ''); ?>" class="w-full p-3 rounded-xl bg-white/10 border border-white/10">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-2">Interests</label>
                <textarea name="interests" rows="3" class="w-full p-3 rounded-xl bg-white/10 border border-white/10"><?php echo escape($profile['interests'] ?? ''); ?></textarea>
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-2">Goals</label>
                <textarea name="goals" rows="3" class="w-full p-3 rounded-xl bg-white/10 border border-white/10"><?php echo escape($profile['goals'] ?? ''); ?></textarea>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-300 mb-2">Preferred country</label>
                    <input type="text" name="preferred_country" value="<?php echo escape($profile['preferred_country'] ?? ''); ?>" class="w-full p-3 rounded-xl bg-white/10 border border-white/10">
                </div>
                <div>
                    <label class="block text-sm text-gray-300 mb-2">Language</label>
                    <select name="preferred_language" class="w-full p-3 rounded-xl bg-slate-900 border border-white/10">
                        <option value="en" <?php echo ($profile['preferred_language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                        <option value="hi" <?php echo ($profile['preferred_language'] ?? '') === 'hi' ? 'selected' : ''; ?>>Hindi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-300 mb-2">Theme</label>
                    <select name="theme_preference" class="w-full p-3 rounded-xl bg-slate-900 border border-white/10">
                        <option value="dark" <?php echo ($profile['theme_preference'] ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                        <option value="light" <?php echo ($profile['theme_preference'] ?? '') === 'light' ? 'selected' : ''; ?>>Light</option>
                    </select>
                </div>
            </div>

            <div class="text-sm text-gray-300">
                Email verification status:
                <span class="font-semibold <?php echo !empty($profile['email_verified']) ? 'text-green-400' : 'text-yellow-300'; ?>">
                    <?php echo !empty($profile['email_verified']) ? 'Verified' : 'Pending'; ?>
                </span>
            </div>

            <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-600 font-semibold">Save Profile</button>
        </form>
    </div>

    <script>
        function getGreetingByHour(hour) {
            if (hour < 12) return 'Good Morning';
            if (hour < 17) return 'Good Afternoon';
            return 'Good Evening';
        }

        function updateProfileGreeting() {
            const greetingElement = document.getElementById('profileGreetingMessage');
            if (!greetingElement) return;

            const username = greetingElement.dataset.username || 'User';
            greetingElement.textContent = `${getGreetingByHour(new Date().getHours())}, ${username}!`;
        }

        updateProfileGreeting();
        setInterval(updateProfileGreeting, 60000);

        document.getElementById('profileForm')?.addEventListener('submit', () => {
            const theme = document.querySelector('[name="theme_preference"]')?.value;
            const language = document.querySelector('[name="preferred_language"]')?.value;
            if (theme) localStorage.setItem('careerPathTheme', theme);
            if (language) localStorage.setItem('careerPathLanguage', language);
        });
    </script>
</body>
</html>
