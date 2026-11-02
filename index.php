<?php
/**
 * Root entry point — redirect to login or app
 */
require_once __DIR__ . '/includes/auth.php';

session_start_safe();

if (!empty($_SESSION['user_id'])) {
    header('Location: /onboarding-buddy-system/pages/app.php');
} else {
    header('Location: /onboarding-buddy-system/pages/login.php');
}
exit;
