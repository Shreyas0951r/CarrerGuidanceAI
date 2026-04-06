<?php
require_once '../config/database.php';
requireAuth();

$assessmentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($assessmentId <= 0) {
    redirect('user/dashboard.php');
}

$conn = getDBConnection();
$stmt = $conn->prepare('SELECT id, user_id, result, answers, created_at FROM assessments WHERE id = ? AND user_id = ? LIMIT 1');
$userId = (int) $_SESSION['user_id'];
$stmt->bind_param('ii', $assessmentId, $userId);
$stmt->execute();
$assessment = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$assessment) {
    redirect('user/dashboard.php');
}

$responses = getAssessmentResponses($assessment);
$matchScore = getAssessmentMatchScore($assessment);
$questionLabels = [
    'interest_area' => 'Interest Area',
    'work_style' => 'Preferred Work Style',
    'impact_type' => 'Desired Impact',
    'risk_tolerance' => 'Risk Tolerance',
    'learning_style' => 'Learning Style',
    'physical_fitness' => 'Physical Fitness Preference',
    'leadership_style' => 'Leadership Style',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Details - CareerPath AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            min-height: 100vh;
            color: white;
        }

        .glass {
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="max-w-5xl mx-auto p-6 md:p-10">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <a href="<?php echo escape(appUrl('user/dashboard.php')); ?>" class="text-indigo-300 hover:text-white text-sm inline-flex items-center gap-2 mb-3">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold"><?php echo escape(getAssessmentCareerName($assessment)); ?></h1>
                <p class="text-gray-400 mt-2">Saved on <?php echo escape(date('F j, Y g:i A', strtotime($assessment['created_at']))); ?></p>
            </div>
            <?php if ($matchScore !== null): ?>
                <div class="glass rounded-2xl px-6 py-4 text-center">
                    <div class="text-sm text-gray-400">Match Score</div>
                    <div class="text-3xl font-bold text-green-400"><?php echo escape((string) $matchScore); ?>%</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid gap-6">
            <section class="glass rounded-3xl p-6 md:p-8">
                <h2 class="text-2xl font-bold mb-4">Recommended Career</h2>
                <p class="text-lg text-indigo-300"><?php echo escape(getAssessmentCareerName($assessment)); ?></p>
            </section>

            <section class="glass rounded-3xl p-6 md:p-8">
                <h2 class="text-2xl font-bold mb-6">Recorded Answers</h2>
                <?php if (!empty($responses)): ?>
                    <div class="grid gap-4">
                        <?php foreach ($responses as $key => $value): ?>
                            <div class="rounded-2xl bg-white/5 px-5 py-4">
                                <div class="text-sm text-gray-400"><?php echo escape($questionLabels[$key] ?? ucwords(str_replace('_', ' ', (string) $key))); ?></div>
                                <div class="mt-1 text-lg font-medium"><?php echo escape(ucwords(str_replace('_', ' ', (string) $value))); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-400">No answer payload was saved for this assessment.</p>
                <?php endif; ?>
            </section>

            <section class="glass rounded-3xl p-6 md:p-8">
                <h2 class="text-2xl font-bold mb-4">Raw Stored Payload</h2>
                <pre class="text-sm text-gray-300 whitespace-pre-wrap break-words"><?php echo escape($assessment['answers'] ?? ''); ?></pre>
            </section>
        </div>
    </div>
</body>
</html>
