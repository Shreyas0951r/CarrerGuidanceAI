<?php
require_once '../config/database.php';
requireAdmin();

$content = loadCustomContent();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_career') {
        $careerId = (int) ($_POST['career_id'] ?? 0);
        $skills = array_values(array_filter(array_map('trim', explode(',', (string) ($_POST['skills_csv'] ?? '')))));
        $roadmap = json_decode((string) ($_POST['roadmap_json'] ?? '[]'), true);
        $resources = json_decode((string) ($_POST['resources_json'] ?? '[]'), true);

        if (!is_array($roadmap) || !is_array($resources)) {
            $error = 'Roadmap and resources must be valid JSON arrays.';
        } else {
            $career = [
                'id' => $careerId > 0 ? $careerId : nextCustomContentId($content['careers']),
                'category' => trim($_POST['category'] ?? ''),
                'key' => trim($_POST['key'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'icon' => trim($_POST['icon'] ?? 'fa-briefcase'),
                'description' => trim($_POST['description'] ?? ''),
                'skills' => $skills,
                'salary' => [
                    'entry' => trim($_POST['salary_entry'] ?? 'N/A'),
                    'mid' => trim($_POST['salary_mid'] ?? 'N/A'),
                    'senior' => trim($_POST['salary_senior'] ?? 'N/A'),
                ],
                'roadmap' => $roadmap,
                'resources' => $resources,
            ];

            $updated = false;
            foreach ($content['careers'] as $index => $existing) {
                if ((int) ($existing['id'] ?? 0) === $career['id']) {
                    $content['careers'][$index] = $career;
                    $updated = true;
                    break;
                }
            }

            if (!$updated) {
                $content['careers'][] = $career;
            }

            saveCustomContent($content);
            $message = 'Custom career saved.';
        }
    }

    if ($action === 'delete_career') {
        $careerId = (int) ($_POST['career_id'] ?? 0);
        $content['careers'] = array_values(array_filter($content['careers'], function ($career) use ($careerId) {
            return (int) ($career['id'] ?? 0) !== $careerId;
        }));
        saveCustomContent($content);
        $message = 'Custom career deleted.';
    }

    if ($action === 'save_question') {
        $questionId = (int) ($_POST['question_id'] ?? 0);
        $options = json_decode((string) ($_POST['options_json'] ?? '[]'), true);

        if (!is_array($options)) {
            $error = 'Options must be a valid JSON array.';
        } else {
            $question = [
                'id' => $questionId > 0 ? $questionId : nextCustomContentId($content['questions']),
                'question_order' => (int) ($_POST['question_order'] ?? 0),
                'id_key' => trim($_POST['id_key'] ?? ''),
                'title' => trim($_POST['question_title'] ?? ''),
                'options' => $options,
            ];

            $normalizedQuestion = [
                'id' => $question['id_key'],
                'title' => $question['title'],
                'options' => $question['options'],
                'question_order' => $question['question_order'],
                '_custom_id' => $question['id'],
            ];

            $updated = false;
            foreach ($content['questions'] as $index => $existing) {
                if ((int) ($existing['_custom_id'] ?? 0) === $question['id']) {
                    $content['questions'][$index] = $normalizedQuestion;
                    $updated = true;
                    break;
                }
            }

            if (!$updated) {
                $content['questions'][] = $normalizedQuestion;
            }

            usort($content['questions'], function ($a, $b) {
                return (int) ($a['question_order'] ?? 0) <=> (int) ($b['question_order'] ?? 0);
            });

            saveCustomContent($content);
            $message = 'Custom question saved.';
        }
    }

    if ($action === 'delete_question') {
        $questionId = (int) ($_POST['question_id'] ?? 0);
        $content['questions'] = array_values(array_filter($content['questions'], function ($question) use ($questionId) {
            return (int) ($question['_custom_id'] ?? 0) !== $questionId;
        }));
        saveCustomContent($content);
        $message = 'Custom question deleted.';
    }

    $content = loadCustomContent();
}

$editingCareerId = isset($_GET['edit_career']) ? (int) $_GET['edit_career'] : 0;
$editingQuestionId = isset($_GET['edit_question']) ? (int) $_GET['edit_question'] : 0;

$editingCareer = null;
foreach ($content['careers'] as $career) {
    if ((int) ($career['id'] ?? 0) === $editingCareerId) {
        $editingCareer = $career;
        break;
    }
}

$editingQuestion = null;
foreach ($content['questions'] as $question) {
    if ((int) ($question['_custom_id'] ?? 0) === $editingQuestionId) {
        $editingQuestion = $question;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Manager - CareerPath AI Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%); min-height: 100vh; color: white; }
        .glass { background: rgba(30, 41, 59, 0.75); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(99, 102, 241, 0.2); border-left: 3px solid #6366f1; }
    </style>
</head>
<body>
    <aside class="fixed left-0 top-0 h-full w-64 glass border-r border-white/10 z-50 hidden md:block">
        <div class="p-6">
            <a href="<?php echo escape(appUrl('career.php')); ?>" class="font-bold text-xl">CareerPath AI</a>
        </div>
        <nav class="mt-6">
            <a href="<?php echo escape(appUrl('admin/dashboard.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Dashboard</a>
            <a href="<?php echo escape(appUrl('admin/users.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Users</a>
            <a href="<?php echo escape(appUrl('admin/assessments.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Assessments</a>
            <a href="<?php echo escape(appUrl('admin/content.php')); ?>" class="sidebar-link active block px-6 py-3 text-white border-l-3 border-indigo-500 bg-indigo-500/20">Content</a>
            <a href="<?php echo escape(appUrl('admin/feedback.php')); ?>" class="sidebar-link block px-6 py-3 text-gray-400 hover:text-white">Feedback</a>
        </nav>
    </aside>

    <main class="md:ml-64 p-8 space-y-8">
        <div>
            <h1 class="text-3xl font-bold">Content Manager</h1>
            <p class="text-gray-300 mt-2">Add, edit, and delete custom careers and custom assessment questions.</p>
        </div>

        <?php if ($message): ?><?php echo showSuccess($message); ?><?php endif; ?>
        <?php if ($error): ?><?php echo showError($error); ?><?php endif; ?>

        <section class="glass rounded-3xl p-6">
            <h2 class="text-2xl font-semibold mb-4"><?php echo $editingCareer ? 'Edit Custom Career' : 'Add Custom Career'; ?></h2>
            <form method="POST" class="grid md:grid-cols-2 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                <input type="hidden" name="action" value="save_career">
                <input type="hidden" name="career_id" value="<?php echo escape((string) ($editingCareer['id'] ?? 0)); ?>">
                <input type="text" name="category" placeholder="Category (e.g. technology)" value="<?php echo escape($editingCareer['category'] ?? ''); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10" required>
                <input type="text" name="key" placeholder="Key (e.g. ai_engineer)" value="<?php echo escape($editingCareer['key'] ?? ''); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10" required>
                <input type="text" name="title" placeholder="Career title" value="<?php echo escape($editingCareer['title'] ?? ''); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10 md:col-span-2" required>
                <input type="text" name="icon" placeholder="Font Awesome icon" value="<?php echo escape($editingCareer['icon'] ?? 'fa-briefcase'); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10">
                <input type="text" name="skills_csv" placeholder="Skills comma separated" value="<?php echo escape(isset($editingCareer['skills']) ? implode(', ', $editingCareer['skills']) : ''); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10">
                <textarea name="description" rows="3" placeholder="Description" class="p-3 rounded-xl bg-white/10 border border-white/10 md:col-span-2"><?php echo escape($editingCareer['description'] ?? ''); ?></textarea>
                <input type="text" name="salary_entry" placeholder="Entry salary" value="<?php echo escape($editingCareer['salary']['entry'] ?? 'N/A'); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10">
                <input type="text" name="salary_mid" placeholder="Mid salary" value="<?php echo escape($editingCareer['salary']['mid'] ?? 'N/A'); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10">
                <input type="text" name="salary_senior" placeholder="Senior salary" value="<?php echo escape($editingCareer['salary']['senior'] ?? 'N/A'); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10">
                <textarea name="roadmap_json" rows="6" placeholder='Roadmap JSON [{"phase":"Start","duration":"0-6 months","tasks":["Task 1"]}]' class="p-3 rounded-xl bg-white/10 border border-white/10 md:col-span-2"><?php echo escape(json_encode($editingCareer['roadmap'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                <textarea name="resources_json" rows="6" placeholder='Resources JSON [{"name":"Course","type":"Course","url":"https://...","icon":"fa-book"}]' class="p-3 rounded-xl bg-white/10 border border-white/10 md:col-span-2"><?php echo escape(json_encode($editingCareer['resources'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                <button type="submit" class="px-5 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-600 font-semibold md:w-fit">Save Career</button>
            </form>

            <div class="mt-8 space-y-3">
                <?php foreach ($content['careers'] as $career): ?>
                    <div class="bg-white/5 rounded-2xl p-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold"><?php echo escape($career['title'] ?? 'Untitled'); ?></div>
                            <div class="text-sm text-gray-400"><?php echo escape(($career['category'] ?? '') . ' / ' . ($career['key'] ?? '')); ?></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="<?php echo escape(appUrl('admin/content.php?edit_career=' . (int) ($career['id'] ?? 0))); ?>" class="px-3 py-2 rounded-xl border border-white/20 hover:bg-white/10 text-sm">Edit</a>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                                <input type="hidden" name="action" value="delete_career">
                                <input type="hidden" name="career_id" value="<?php echo escape((string) ($career['id'] ?? 0)); ?>">
                                <button type="submit" class="px-3 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="glass rounded-3xl p-6">
            <h2 class="text-2xl font-semibold mb-4"><?php echo $editingQuestion ? 'Edit Custom Question' : 'Add Custom Question'; ?></h2>
            <form method="POST" class="grid md:grid-cols-2 gap-4">
                <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                <input type="hidden" name="action" value="save_question">
                <input type="hidden" name="question_id" value="<?php echo escape((string) ($editingQuestion['_custom_id'] ?? 0)); ?>">
                <input type="text" name="id_key" placeholder="Question key" value="<?php echo escape($editingQuestion['id'] ?? ''); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10" required>
                <input type="number" name="question_order" placeholder="Order" value="<?php echo escape((string) ($editingQuestion['question_order'] ?? 0)); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10">
                <input type="text" name="question_title" placeholder="Question title" value="<?php echo escape($editingQuestion['title'] ?? ''); ?>" class="p-3 rounded-xl bg-white/10 border border-white/10 md:col-span-2" required>
                <textarea name="options_json" rows="8" placeholder='Options JSON [{"id":"option_1","label":"Option","icon":"fa-star","desc":"Description"}]' class="p-3 rounded-xl bg-white/10 border border-white/10 md:col-span-2"><?php echo escape(json_encode($editingQuestion['options'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></textarea>
                <button type="submit" class="px-5 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-600 font-semibold md:w-fit">Save Question</button>
            </form>

            <div class="mt-8 space-y-3">
                <?php foreach ($content['questions'] as $question): ?>
                    <div class="bg-white/5 rounded-2xl p-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold"><?php echo escape($question['title'] ?? 'Untitled'); ?></div>
                            <div class="text-sm text-gray-400"><?php echo escape($question['id'] ?? ''); ?></div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="<?php echo escape(appUrl('admin/content.php?edit_question=' . (int) ($question['_custom_id'] ?? 0))); ?>" class="px-3 py-2 rounded-xl border border-white/20 hover:bg-white/10 text-sm">Edit</a>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo escape(csrfToken()); ?>">
                                <input type="hidden" name="action" value="delete_question">
                                <input type="hidden" name="question_id" value="<?php echo escape((string) ($question['_custom_id'] ?? 0)); ?>">
                                <button type="submit" class="px-3 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
