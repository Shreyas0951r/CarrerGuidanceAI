<?php
require_once '../config/database.php';
requireAuth();

$conn = getDBConnection();
$stmt = $conn->prepare('SELECT id, user_id, result, answers, created_at FROM assessments WHERE user_id = ? ORDER BY created_at DESC');
$userId = (int) $_SESSION['user_id'];
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$assessments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
    <title>My Dashboard - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            min-height: 100vh;
            color: white;
        }

        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="font-sans">
    <nav class="glass border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="<?php echo escape(appUrl('career.php')); ?>" class="flex items-center space-x-2">
                <i class="fas fa-compass text-2xl text-indigo-500"></i>
                <span class="font-bold text-xl">CareerPath<span class="text-indigo-500">AI</span></span>
            </a>
            <div class="flex items-center space-x-4">
                <a href="<?php echo escape(appUrl('career.php')); ?>" class="hover:text-indigo-400">New Assessment</a>
                <a href="<?php echo escape(appUrl('user/profile.php')); ?>" class="hover:text-indigo-400">Profile</a>
                <form method="POST" action="<?php echo escape(appUrl('auth/logout.php')); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg text-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold">Your Career History</h1>
                <p id="greetingMessage" class="text-gray-400 mt-2" data-username="<?php echo escape($_SESSION['fullname'] ?? 'User'); ?>">
                    <?php echo escape($defaultGreeting); ?>, <?php echo escape($_SESSION['fullname'] ?? 'User'); ?>!
                </p>
            </div>
            <a href="<?php echo escape(appUrl('career.php')); ?>" class="bg-indigo-500 hover:bg-indigo-600 px-6 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-plus"></i> Take New Assessment
            </a>
        </div>

        <?php if (!empty($assessments)): ?>
            <div class="grid gap-6">
                <?php foreach ($assessments as $assessment): ?>
                    <?php $matchScore = getAssessmentMatchScore($assessment); ?>
                    <div class="glass rounded-2xl p-6 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-indigo-400">
                                <?php echo escape(getAssessmentCareerName($assessment)); ?>
                            </h3>
                            <p class="text-gray-400 text-sm mt-1">
                                Taken on <?php echo escape(date('F j, Y g:i A', strtotime($assessment['created_at']))); ?>
                            </p>
                            <?php if ($matchScore !== null): ?>
                                <span class="inline-block mt-2 px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-sm">
                                    Match: <?php echo escape((string) $matchScore); ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <a href="<?php echo escape(appUrl('user/view-assessment.php?id=' . (int) $assessment['id'])); ?>" class="text-indigo-400 hover:text-white">
                                <i class="fas fa-eye text-xl"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass rounded-2xl p-12 text-center">
                <i class="fas fa-clipboard-list text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-bold mb-2">No Assessments Yet</h3>
                <p class="text-gray-400 mb-6">Take your first career assessment to get personalized recommendations.</p>
                <a href="<?php echo escape(appUrl('career.php')); ?>" class="bg-indigo-500 hover:bg-indigo-600 px-8 py-3 rounded-lg inline-block">
                    Start Assessment
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function getGreetingByHour(hour) {
            if (hour < 12) return 'Good Morning';
            if (hour < 17) return 'Good Afternoon';
            return 'Good Evening';
        }

        function updateGreetingMessage() {
            const greetingElement = document.getElementById('greetingMessage');
            if (!greetingElement) return;

            const username = greetingElement.dataset.username || 'User';
            const currentHour = new Date().getHours();
            greetingElement.textContent = `${getGreetingByHour(currentHour)}, ${username}!`;
        }

        updateGreetingMessage();
        setInterval(updateGreetingMessage, 60000);
    </script>
</body>
</html>
