<?php
/**
 * POST /api/chat.php
 * Accepts JSON: { "message": "...", "session_id": <int|null> }
 * Returns JSON: { "reply": "...", "session_id": <int> }
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/buddy_engine.php';

auth_check();
$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$userMsg   = trim($body['message']   ?? '');
$sessionId = isset($body['session_id']) ? (int)$body['session_id'] : null;

if ($userMsg === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

$pdo = Database::connect();

// ── Ensure a valid session exists ────────────────────────────────────────────
if ($sessionId) {
    $chk = $pdo->prepare('SELECT id FROM chat_sessions WHERE id = ? AND user_id = ?');
    $chk->execute([$sessionId, $user['id']]);
    if (!$chk->fetch()) {
        $sessionId = null;   // invalid or belongs to another user — start fresh
    }
}

if (!$sessionId) {
    $ins = $pdo->prepare('INSERT INTO chat_sessions (user_id) VALUES (?)');
    $ins->execute([$user['id']]);
    $sessionId = (int)$pdo->lastInsertId();
}

// ── Load recent history (last 10 exchanges = 20 messages) ────────────────────
$histStmt = $pdo->prepare(
    'SELECT role, content FROM chat_messages
     WHERE session_id = ? ORDER BY id DESC LIMIT 20'
);
$histStmt->execute([$sessionId]);
$history = array_reverse($histStmt->fetchAll());

// ── Call BuddyAI ─────────────────────────────────────────────────────────────
$reply = buddyai_chat($user, $history, $userMsg);

// ── Persist both turns ────────────────────────────────────────────────────────
$ins2 = $pdo->prepare('INSERT INTO chat_messages (session_id, role, content) VALUES (?,?,?)');
$ins2->execute([$sessionId, 'user',      $userMsg]);
$ins2->execute([$sessionId, 'assistant', $reply]);

echo json_encode([
    'reply'      => $reply,
    'session_id' => $sessionId,
]);
