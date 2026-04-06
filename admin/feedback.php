<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
ensureFeatureInfrastructure($conn);
$result = $conn->query("
    SELECT f.*, u.fullname, u.email
    FROM feedback f
    JOIN users u ON u.id = f.user_id
    ORDER BY f.created_at DESC
");
$feedbackEntries = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - CareerPath AI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%); min-height: 100vh; color: white; }
        .glass { background: rgba(30, 41, 59, 0.75); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(99,102,241,0.2); border-left: 3px solid #6366f1; }
    </style>
</head>
<body>
    <aside class="fixed left-0 top-0 h-full w-64 glass border-r border-white/10 z-50 hidden md:block">
        <div class="p-6"><a href="<?php echo escape(appUrl('career.php')); ?>" class="font-bold text-xl">CareerPath AI</a></div>
        <nav class="mt-6">
            <a href="<?php echo escape(appUrl('admin/dashboard.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Dashboard</a>
            <a href="<?php echo escape(appUrl('admin/users.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Users</a>
            <a href="<?php echo escape(appUrl('admin/assessments.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Assessments</a>
            <a href="<?php echo escape(appUrl('admin/content.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Content</a>
            <a href="<?php echo escape(appUrl('admin/feedback.php')); ?>" class="sidebar-link active block px-6 py-3 text-white border-l-3 border-indigo-500 bg-indigo-500/20">Feedback</a>
        </nav>
    </aside>

    <main class="md:ml-64 p-8 space-y-4">
        <h1 class="text-3xl font-bold">Feedback Management</h1>
        <?php if (!empty($feedbackEntries)): ?>
            <?php foreach ($feedbackEntries as $entry): ?>
                <div class="glass rounded-2xl p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold"><?php echo escape($entry['career_title']); ?></div>
                            <div class="text-sm text-gray-400"><?php echo escape($entry['fullname']); ?>, <?php echo escape($entry['email']); ?></div>
                        </div>
                        <div class="text-yellow-300"><?php echo escape(str_repeat('★', (int) $entry['rating'])); ?></div>
                    </div>
                    <p class="mt-4 text-gray-200"><?php echo escape($entry['feedback_text'] ?? ''); ?></p>
                    <div class="mt-3 text-sm text-gray-400"><?php echo escape(date('F j, Y g:i A', strtotime($entry['created_at']))); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="glass rounded-2xl p-8 text-center text-gray-400">No feedback submitted yet.</div>
        <?php endif; ?>
    </main>
</body>
</html>
