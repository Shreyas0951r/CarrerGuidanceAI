<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
ensureFeatureInfrastructure($conn);
$adminName = $_SESSION['admin_name'] ?? 'Admin';

$totalUsers = (int) $conn->query('SELECT COUNT(*) AS count FROM users WHERE is_admin = 0')->fetch_assoc()['count'];
$totalAdmins = (int) $conn->query('SELECT COUNT(*) AS count FROM admins')->fetch_assoc()['count'];
$totalAssessments = (int) $conn->query('SELECT COUNT(*) AS count FROM assessments')->fetch_assoc()['count'];
$todayAssessments = (int) $conn->query('SELECT COUNT(*) AS count FROM assessments WHERE DATE(created_at) = CURDATE()')->fetch_assoc()['count'];
$uniqueCareers = (int) $conn->query("SELECT COUNT(DISTINCT result) AS count FROM assessments WHERE result IS NOT NULL AND result <> ''")->fetch_assoc()['count'];

$recentResult = $conn->query("
    SELECT a.id, a.result, a.answers, a.created_at, u.fullname, u.email
    FROM assessments a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 10
");
$recentAssessments = $recentResult ? $recentResult->fetch_all(MYSQLI_ASSOC) : [];

$analyticsResult = $conn->query('SELECT result, answers, created_at FROM assessments ORDER BY created_at DESC LIMIT 250');
$analyticsRows = $analyticsResult ? $analyticsResult->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();

$topCareerCounts = [];
$interestAreaCounts = [];
$dailyAssessmentCounts = [];

foreach ($analyticsRows as $row) {
    $careerName = getAssessmentCareerName($row);
    $topCareerCounts[$careerName] = ($topCareerCounts[$careerName] ?? 0) + 1;

    $responses = getAssessmentResponses($row);
    $interestArea = $responses['interest_area'] ?? 'unknown';
    $interestAreaCounts[$interestArea] = ($interestAreaCounts[$interestArea] ?? 0) + 1;

    $dayKey = date('M j', strtotime($row['created_at']));
    $dailyAssessmentCounts[$dayKey] = ($dailyAssessmentCounts[$dayKey] ?? 0) + 1;
}

arsort($topCareerCounts);
arsort($interestAreaCounts);
$topCareerCounts = array_slice($topCareerCounts, 0, 5, true);

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
    <title>Admin Dashboard - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .sidebar-link:hover,
        .sidebar-link.active {
            background: rgba(99, 102, 241, 0.2);
            border-left: 3px solid #6366f1;
        }
    </style>
</head>
<body class="font-sans">
    <aside class="fixed left-0 top-0 h-full w-64 glass border-r border-white/10 z-50 hidden md:block">
        <div class="p-6">
            <a href="<?php echo escape(appUrl('career.php')); ?>" class="flex items-center space-x-2">
                <i class="fas fa-compass text-2xl text-indigo-500"></i>
                <span class="font-bold text-xl">CareerPath<span class="text-indigo-500">AI</span></span>
            </a>
        </div>

        <nav class="mt-6">
            <a href="<?php echo escape(appUrl('admin/dashboard.php')); ?>" class="sidebar-link active block px-6 py-3 text-white border-l-3 border-indigo-500 bg-indigo-500/20">
                <i class="fas fa-tachometer-alt w-6"></i> Dashboard
            </a>
            <a href="<?php echo escape(appUrl('admin/users.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">
                <i class="fas fa-users w-6"></i> All Users
            </a>
            <a href="<?php echo escape(appUrl('admin/assessments.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">
                <i class="fas fa-clipboard-list w-6"></i> Assessments
            </a>
            <a href="<?php echo escape(appUrl('admin/content.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">
                <i class="fas fa-layer-group w-6"></i> Content
            </a>
            <a href="<?php echo escape(appUrl('admin/feedback.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">
                <i class="fas fa-comment-dots w-6"></i> Feedback
            </a>
        </nav>

        <div class="absolute bottom-0 left-0 right-0 p-6">
            <form method="POST" action="<?php echo escape(appUrl('auth/admin-logout.php')); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                <button type="submit" class="text-red-400 hover:text-red-300">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <main class="md:ml-64 min-h-screen p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold">Dashboard Overview</h1>
                <p id="adminGreetingMessage" class="text-gray-400" data-username="<?php echo escape($adminName); ?>">
                    <?php echo escape($defaultGreeting); ?>, <?php echo escape($adminName); ?>!
                </p>
            </div>
            <span class="text-gray-400"><?php echo escape(date('F j, Y')); ?></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6 mb-8">
            <div class="glass rounded-2xl p-6">
                <div class="text-3xl font-bold"><?php echo escape((string) $totalUsers); ?></div>
                <div class="text-gray-400 text-sm mt-2">Total Users</div>
            </div>
            <div class="glass rounded-2xl p-6">
                <div class="text-3xl font-bold"><?php echo escape((string) $totalAdmins); ?></div>
                <div class="text-gray-400 text-sm mt-2">Total Admins</div>
            </div>
            <div class="glass rounded-2xl p-6">
                <div class="text-3xl font-bold"><?php echo escape((string) $totalAssessments); ?></div>
                <div class="text-gray-400 text-sm mt-2">Total Assessments</div>
            </div>
            <div class="glass rounded-2xl p-6">
                <div class="text-3xl font-bold"><?php echo escape((string) $todayAssessments); ?></div>
                <div class="text-gray-400 text-sm mt-2">Today's Assessments</div>
            </div>
            <div class="glass rounded-2xl p-6">
                <div class="text-3xl font-bold"><?php echo escape((string) $uniqueCareers); ?></div>
                <div class="text-gray-400 text-sm mt-2">Unique Careers</div>
            </div>
        </div>

        <div class="grid xl:grid-cols-2 gap-6 mb-8">
            <div class="glass rounded-2xl p-6">
                <h3 class="text-xl font-bold mb-4">Most Selected Careers</h3>
                <canvas id="careerChart" height="220"></canvas>
            </div>
            <div class="glass rounded-2xl p-6">
                <h3 class="text-xl font-bold mb-4">Category Popularity</h3>
                <canvas id="interestChart" height="220"></canvas>
            </div>
        </div>

        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">Recent Assessments</h3>
                <a href="<?php echo escape(appUrl('admin/assessments.php')); ?>" class="text-sm text-indigo-300 hover:text-white">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/10 text-gray-400">
                            <th class="pb-3 pr-4">User</th>
                            <th class="pb-3 pr-4">Career Result</th>
                            <th class="pb-3 pr-4">Match</th>
                            <th class="pb-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentAssessments)): ?>
                            <?php foreach ($recentAssessments as $row): ?>
                                <?php $matchScore = getAssessmentMatchScore($row); ?>
                                <tr class="border-b border-white/5">
                                    <td class="py-3 pr-4">
                                        <div class="font-medium"><?php echo escape($row['fullname']); ?></div>
                                        <div class="text-gray-500 text-sm"><?php echo escape($row['email']); ?></div>
                                    </td>
                                    <td class="py-3 pr-4">
                                        <span class="px-3 py-1 rounded-full bg-white/10 text-sm">
                                            <?php echo escape(getAssessmentCareerName($row)); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-green-400">
                                        <?php echo $matchScore !== null ? escape((string) $matchScore) . '%' : 'N/A'; ?>
                                    </td>
                                    <td class="py-3 text-gray-400">
                                        <?php echo escape(date('M j, Y H:i', strtotime($row['created_at']))); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-gray-400">No assessments saved yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function getGreetingByHour(hour) {
            if (hour < 12) return 'Good Morning';
            if (hour < 17) return 'Good Afternoon';
            return 'Good Evening';
        }

        function updateAdminGreeting() {
            const greetingElement = document.getElementById('adminGreetingMessage');
            if (!greetingElement) return;

            const username = greetingElement.dataset.username || 'Admin';
            greetingElement.textContent = `${getGreetingByHour(new Date().getHours())}, ${username}!`;
        }

        updateAdminGreeting();
        setInterval(updateAdminGreeting, 60000);

        new Chart(document.getElementById('careerChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($topCareerCounts)); ?>,
                datasets: [{
                    label: 'Assessments',
                    data: <?php echo json_encode(array_values($topCareerCounts)); ?>,
                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                    borderRadius: 12
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.08)' } },
                    y: { ticks: { color: '#cbd5e1' }, grid: { color: 'rgba(255,255,255,0.08)' } }
                }
            }
        });

        new Chart(document.getElementById('interestChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($interestAreaCounts)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($interestAreaCounts)); ?>,
                    backgroundColor: ['#6366f1', '#ec4899', '#8b5cf6', '#22c55e', '#f59e0b', '#06b6d4']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#e2e8f0' }
                    }
                }
            }
        });
    </script>
</body>
</html>
