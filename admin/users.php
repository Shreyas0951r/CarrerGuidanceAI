<?php
require_once '../config/database.php';
requireAdmin();

$conn = getDBConnection();
ensureFeatureInfrastructure($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? '');
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['block_reason'] ?? '');

    if ($userId > 0 && in_array($action, ['block', 'unblock'], true)) {
        $status = getUserStatus($conn, $userId);
        $isBlocked = $action === 'block' ? 1 : 0;
        $blockedAt = $action === 'block' ? date('Y-m-d H:i:s') : null;
        $reasonValue = $action === 'block' ? $reason : null;

        $stmt = $conn->prepare('UPDATE user_status SET is_blocked = ?, blocked_at = ?, block_reason = ? WHERE user_id = ?');
        $stmt->bind_param('issi', $isBlocked, $blockedAt, $reasonValue, $userId);
        $stmt->execute();
        $stmt->close();
    }
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT u.id, u.fullname, u.email, COUNT(a.id) AS assessment_count, MAX(a.created_at) AS last_assessment,
           COALESCE(s.is_blocked, 0) AS is_blocked, s.block_reason
    FROM users u
    LEFT JOIN assessments a ON a.user_id = u.id
    LEFT JOIN user_status s ON s.user_id = u.id
    WHERE u.is_admin = 0
";

$types = '';
$params = [];

if ($search !== '') {
    $sql .= ' AND (u.fullname LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter === 'blocked') {
    $sql .= ' AND COALESCE(s.is_blocked, 0) = 1';
} elseif ($statusFilter === 'active') {
    $sql .= ' AND COALESCE(s.is_blocked, 0) = 0';
}

$sql .= ' GROUP BY u.id, u.fullname, u.email, s.is_blocked, s.block_reason ORDER BY u.fullname ASC';
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=careerpath-users.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Email', 'Assessments', 'Blocked', 'Block Reason', 'Last Assessment']);
    foreach ($users as $user) {
        fputcsv($output, [
            $user['fullname'],
            $user['email'],
            $user['assessment_count'],
            !empty($user['is_blocked']) ? 'Yes' : 'No',
            $user['block_reason'],
            $user['last_assessment'],
        ]);
    }
    fclose($output);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - CareerPath AI Admin</title>
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
            <a href="<?php echo escape(appUrl('admin/users.php')); ?>" class="sidebar-link active block px-6 py-3 text-white border-l-3 border-indigo-500 bg-indigo-500/20">Users</a>
            <a href="<?php echo escape(appUrl('admin/assessments.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Assessments</a>
            <a href="<?php echo escape(appUrl('admin/content.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Content</a>
            <a href="<?php echo escape(appUrl('admin/feedback.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Feedback</a>
        </nav>
    </aside>

    <main class="md:ml-64 min-h-screen p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Users</h1>
                <p class="text-gray-400 mt-2">Search, filter, block, and export users.</p>
            </div>
            <a href="<?php echo escape(appUrl('admin/users.php?search=' . urlencode($search) . '&status=' . urlencode($statusFilter) . '&export=csv')); ?>" class="px-4 py-2 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-sm font-semibold">Export CSV</a>
        </div>

        <form method="GET" class="glass rounded-2xl p-4 mb-6 grid md:grid-cols-3 gap-4">
            <input type="text" name="search" value="<?php echo escape($search); ?>" placeholder="Search by name or email" class="p-3 rounded-xl bg-white/10 border border-white/10">
            <select name="status" class="p-3 rounded-xl bg-slate-900 border border-white/10">
                <option value="">All statuses</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="blocked" <?php echo $statusFilter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
            </select>
            <button type="submit" class="px-4 py-3 rounded-xl border border-white/20 hover:bg-white/10">Apply Filters</button>
        </form>

        <div class="space-y-4">
            <?php foreach ($users as $user): ?>
                <div class="glass rounded-2xl p-5">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <div class="font-semibold text-lg"><?php echo escape($user['fullname']); ?></div>
                            <div class="text-gray-400"><?php echo escape($user['email']); ?></div>
                            <div class="text-sm text-gray-400 mt-2">
                                Assessments: <?php echo escape((string) $user['assessment_count']); ?> |
                                Last: <?php echo !empty($user['last_assessment']) ? escape(date('M j, Y H:i', strtotime($user['last_assessment']))) : 'Never'; ?>
                            </div>
                            <div class="mt-2 text-sm <?php echo !empty($user['is_blocked']) ? 'text-red-300' : 'text-green-300'; ?>">
                                Status: <?php echo !empty($user['is_blocked']) ? 'Blocked' : 'Active'; ?>
                                <?php if (!empty($user['block_reason'])): ?>
                                    (<?php echo escape($user['block_reason']); ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                        <form method="POST" class="flex flex-col md:flex-row gap-3">
                            <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                            <input type="hidden" name="user_id" value="<?php echo escape((string) $user['id']); ?>">
                            <input type="text" name="block_reason" placeholder="Block reason" class="p-3 rounded-xl bg-white/10 border border-white/10">
                            <?php if (!empty($user['is_blocked'])): ?>
                                <input type="hidden" name="action" value="unblock">
                                <button type="submit" class="px-4 py-3 rounded-xl bg-green-500 hover:bg-green-600 font-semibold">Unblock</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="block">
                                <button type="submit" class="px-4 py-3 rounded-xl bg-red-500 hover:bg-red-600 font-semibold">Block</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
