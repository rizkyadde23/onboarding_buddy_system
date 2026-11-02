<?php
/**
 * POST /api/auth.php   action=login   { email, password }
 * POST /api/auth.php   action=logout
 * GET  /api/auth.php   action=me      — returns current user profile
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ── me ────────────────────────────────────────────────────────────────────────
if ($action === 'me') {
    $user = auth_user();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    unset($user['password_hash']);
    echo json_encode($user);
    exit;
}

// ── login ─────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $email    = trim($body['email']    ?? $_POST['email']    ?? '');
    $password =      $body['password'] ?? $_POST['password'] ?? '';

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Email dan password wajib diisi.']);
        exit;
    }

    if (login($email, $password)) {
        $user = auth_user();
        unset($user['password_hash']);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Email atau password salah.']);
    }
    exit;
}

// ── logout ────────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    logout();
    // logout() calls exit internally; code below is unreachable
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
