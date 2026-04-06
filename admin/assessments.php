<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
ensureFeatureInfrastructure($conn);

$search = trim($_GET['search'] ?? '');
$sql = "
    SELECT a.id, a.result, a.answers, a.created_at, u.fullname, u.email
    FROM assessments a
    JOIN users u ON u.id = a.user_id
    WHERE 1=1
";
$types = '';
$params = [];

if ($search !== '') {
    $sql .= ' AND (u.fullname LIKE ? OR u.email LIKE ? OR a.result LIKE ?)';
    $like = '%' . $search . '%';
    $types = 'sss';
    $params = [$like, $like, $like];
}

$sql .= ' ORDER BY a.created_at DESC';
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$questionLabels = [
    'interest_area' => 'Interest Area',
    'work_style' => 'Work Style',
    'impact_type' => 'Impact Type',
    'risk_tolerance' => 'Risk Tolerance',
    'learning_style' => 'Learning Style',
    'physical_fitness' => 'Physical Fitness',
    'leadership_style' => 'Leadership Style',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessments - CareerPath AI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%); min-height: 100vh; color: white; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(99,102,241,0.2); border-left: 3px solid #6366f1; }
    </style>
</head>
<body>
    <aside class="fixed left-0 top-0 h-full w-64 glass border-r border-white/10 z-50 hidden md:block">
        <div class="p-6"><a href="<?php echo escape(appUrl('career.php')); ?>" class="font-bold text-xl">CareerPath AI</a></div>
        <nav class="mt-6">
            <a href="<?php echo escape(appUrl('admin/dashboard.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Dashboard</a>
            <a href="<?php echo escape(appUrl('admin/users.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Users</a>
            <a href="<?php echo escape(appUrl('admin/assessments.php')); ?>" class="sidebar-link active block px-6 py-3 text-white border-l-3 border-indigo-500 bg-indigo-500/20">Assessments</a>
            <a href="<?php echo escape(appUrl('admin/content.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Content</a>
            <a href="<?php echo escape(appUrl('admin/feedback.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Feedback</a>
        </nav>
    </aside>

    <main class="md:ml-64 min-h-screen p-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">Assessment Reports</h1>
                <p class="text-gray-400 mt-2">Detailed answer patterns and recommendation history.</p>
            </div>
            <span class="text-gray-400"><?php echo escape((string) count($assessments)); ?> records</span>
        </div>

        <form method="GET" class="glass rounded-2xl p-4 mb-6">
            <input type="text" name="search" value="<?php echo escape($search); ?>" placeholder="Search by user, email, or career" class="w-full p-3 rounded-xl bg-white/10 border border-white/10">
        </form>

        <div class="grid gap-4">
            <?php if (!empty($assessments)): ?>
                <?php foreach ($assessments as $assessment): ?>
                    <?php $matchScore = getAssessmentMatchScore($assessment); ?>
                    <?php $responses = getAssessmentResponses($assessment); ?>
                    <div class="glass rounded-2xl p-6">
                        <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
                            <div class="xl:w-1/3">
                                <h2 class="text-xl font-semibold text-indigo-300"><?php echo escape(getAssessmentCareerName($assessment)); ?></h2>
                                <p class="text-gray-300 mt-1"><?php echo escape($assessment['fullname']); ?>, <?php echo escape($assessment['email']); ?></p>
                                <p class="text-sm text-gray-400 mt-2"><?php echo escape(date('F j, Y g:i A', strtotime($assessment['created_at']))); ?></p>
                                <p class="mt-3 text-sm text-green-300">Match: <?php echo $matchScore !== null ? escape((string) $matchScore) . '%' : 'N/A'; ?></p>
                            </div>
                            <div class="xl:w-2/3">
                                <div class="grid md:grid-cols-2 gap-3">
                                    <?php foreach ($responses as $key => $value): ?>
                                        <div class="bg-white/5 rounded-2xl p-4">
                                            <div class="text-xs uppercase tracking-wide text-gray-400"><?php echo escape($questionLabels[$key] ?? ucwords(str_replace('_', ' ', (string) $key))); ?></div>
                                            <div class="mt-1 font-medium"><?php echo escape(ucwords(str_replace('_', ' ', (string) $value))); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass rounded-2xl p-8 text-center text-gray-400">No assessments available.</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
