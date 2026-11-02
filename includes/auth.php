<?php
/**
 * Authentication helpers
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

function session_start_safe(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,   // set true in production (HTTPS)
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function auth_check(): void
{
    session_start_safe();
    if (empty($_SESSION['user_id'])) {
        header('Location: /onboarding-buddy-system/pages/login.php');
        exit;
    }
}

function auth_user(): ?array
{
    session_start_safe();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $pdo  = Database::connect();
    $stmt = $pdo->prepare(
        'SELECT u.*, m.name AS mentor_name
         FROM users u
         LEFT JOIN users m ON m.id = u.mentor_id
         WHERE u.id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function login(string $email, string $password): bool
{
    $pdo  = Database::connect();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_start_safe();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}

function logout(): void
{
    session_start_safe();
    session_destroy();
    header('Location: /onboarding-buddy-system/pages/login.php');
    exit;
}
