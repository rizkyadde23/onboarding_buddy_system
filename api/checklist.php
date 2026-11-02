<?php
/**
 * GET  /api/checklist.php          — list items for current user's onboarding_day
 * POST /api/checklist.php          — toggle a checklist item
 *   body: { "template_id": <int>, "is_done": true|false }
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';

auth_check();
$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = Database::connect();

// ── GET ───────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return items for the user's current onboarding day + their progress
    $stmt = $pdo->prepare(
        'SELECT t.id AS template_id, t.title, t.description, t.sort_order,
                COALESCE(p.is_done, 0) AS is_done, p.done_at
         FROM checklist_templates t
         LEFT JOIN checklist_progress p
               ON  p.template_id = t.id AND p.user_id = ?
         WHERE t.day = ? AND t.is_active = 1
         ORDER BY t.sort_order'
    );
    $stmt->execute([$user['id'], $user['onboarding_day']]);
    $items = $stmt->fetchAll();

    // Cast types
    foreach ($items as &$item) {
        $item['is_done'] = (bool)(int)$item['is_done'];
    }

    echo json_encode(['day' => (int)$user['onboarding_day'], 'items' => $items]);
    exit;
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body       = json_decode(file_get_contents('php://input'), true);
    $templateId = (int)($body['template_id'] ?? 0);
    $isDone     = (bool)($body['is_done']     ?? false);

    if (!$templateId) {
        http_response_code(400);
        echo json_encode(['error' => 'template_id required']);
        exit;
    }

    // Upsert progress row
    $stmt = $pdo->prepare(
        'INSERT INTO checklist_progress (user_id, template_id, is_done, done_at)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           is_done = VALUES(is_done),
           done_at = VALUES(done_at)'
    );
    $doneAt = $isDone ? date('Y-m-d H:i:s') : null;
    $stmt->execute([$user['id'], $templateId, (int)$isDone, $doneAt]);

    // Auto-advance onboarding_day when ALL items for the current day are done
    $totalStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM checklist_templates WHERE day = ? AND is_active = 1'
    );
    $totalStmt->execute([$user['onboarding_day']]);
    $total = (int)$totalStmt->fetchColumn();

    $doneStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM checklist_progress p
         JOIN checklist_templates t ON t.id = p.template_id
         WHERE p.user_id = ? AND t.day = ? AND p.is_done = 1'
    );
    $doneStmt->execute([$user['id'], $user['onboarding_day']]);
    $doneCount = (int)$doneStmt->fetchColumn();

    $advanced = false;
    if ($total > 0 && $doneCount >= $total) {
        // Check if next day has items
        $nextDay  = $user['onboarding_day'] + 1;
        $nextStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM checklist_templates WHERE day = ? AND is_active = 1'
        );
        $nextStmt->execute([$nextDay]);
        if ((int)$nextStmt->fetchColumn() > 0) {
            $pdo->prepare('UPDATE users SET onboarding_day = ? WHERE id = ?')
                ->execute([$nextDay, $user['id']]);
            $advanced = true;
        }
    }

    echo json_encode([
        'success'  => true,
        'advanced' => $advanced,
        'is_done'  => $isDone,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
