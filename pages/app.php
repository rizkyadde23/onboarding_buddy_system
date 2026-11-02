<?php
require_once __DIR__ . '/../includes/auth.php';
auth_check();
$user = auth_user();

// Redirect admins to their own panel if needed
// For now, all users go to the main app.

$onboardingDay = (int)($user['onboarding_day'] ?? 1);
$userName      = htmlspecialchars($user['name'],         ENT_QUOTES, 'UTF-8');
$userInitials  = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $user['name']), 0, 2))));
$userPosition  = htmlspecialchars(($user['division'] ?? '') . ($user['position'] ? ' — ' . $user['position'] : ''), ENT_QUOTES, 'UTF-8');
$mentorName    = htmlspecialchars($user['mentor_name'] ?? 'Mentor Kamu', ENT_QUOTES, 'UTF-8');
$userId        = (int)$user['id'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BuddyAI — Onboarding</title>
  <link rel="stylesheet" href="/onboarding-buddy-system/assets/css/main.css">
  <link rel="stylesheet" href="/onboarding-buddy-system/assets/css/chat.css">
</head>
<body>

<div class="app-layout">

  <!-- ── Sidebar ──────────────────────────────────────── -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-mark">🤝</div>
      <span>Buddy<em>AI</em></span>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-section-label">Menu</div>
      <ul class="sidebar-nav">
        <li>
          <a href="#" class="active" id="nav-chat" data-view="chat">
            <span class="nav-icon">💬</span> Chat BuddyAI
          </a>
        </li>
        <li>
          <a href="#" id="nav-dashboard" data-view="dashboard">
            <span class="nav-icon">✅</span> Checklist Onboarding
          </a>
        </li>
      </ul>
    </div>

    <div class="sidebar-section mt-3">
      <div class="sidebar-section-label">Info Onboarding</div>
      <div class="card" style="padding:14px 16px; font-size:.85rem;">
        <div class="mb-2">
          <span class="badge badge-primary">Hari Ke-<?= $onboardingDay ?></span>
        </div>
        <div class="text-muted" style="font-size:.8rem;">Mentor: <strong><?= $mentorName ?></strong></div>
      </div>
    </div>

    <!-- Profile + logout -->
    <div class="sidebar-profile">
      <div class="avatar avatar-sm"><?= $userInitials ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= $userName ?></div>
        <div class="profile-role"><?= $userPosition ?></div>
      </div>
      <button class="logout-btn" title="Logout" onclick="logout()">⎋</button>
    </div>
  </nav>

  <!-- ── Main ─────────────────────────────────────────── -->
  <div class="main-content">

    <!-- ── CHAT VIEW ──────────────────────────────────── -->
    <div id="chat-view">
      <div class="chat-topbar">
        <div class="buddy-avatar">🤖</div>
        <div class="buddy-info">
          <div class="buddy-name">BuddyAI</div>
          <div class="buddy-status">Online &amp; siap membantu</div>
        </div>
        <div class="day-badge">Hari Ke-<?= $onboardingDay ?></div>
      </div>

      <div id="messages">
        <!-- Welcome message injected by JS -->
      </div>

      <div class="chat-inputbar">
        <div class="chat-input-wrap">
          <textarea id="chat-input" rows="1"
            placeholder="Tulis pertanyaanmu di sini…"
            aria-label="Pesan ke BuddyAI"></textarea>
          <button id="send-btn" title="Kirim" aria-label="Kirim pesan">➤</button>
        </div>
      </div>
    </div><!-- #chat-view -->

    <!-- ── DASHBOARD / CHECKLIST VIEW ─────────────────── -->
    <div id="dashboard-view" class="hidden">
      <h2 style="margin-bottom:6px;">✅ Checklist Onboarding</h2>
      <p class="text-muted mb-3">Hari Ke-<?= $onboardingDay ?> — selesaikan semua tugas di bawah ini.</p>

      <div class="card mb-3" style="padding:16px 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
          <span style="font-size:.85rem; font-weight:600; color:var(--text-muted);">PROGRES HARI INI</span>
          <span id="progress-label" style="font-size:.85rem; font-weight:700; color:var(--primary);">0 / 0</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" id="progress-bar" style="width:0%"></div>
        </div>
      </div>

      <div id="checklist-items">
        <div style="text-align:center; padding:40px; color:var(--text-muted);">
          <div class="spinner"></div>
          <p class="mt-2">Memuat checklist…</p>
        </div>
      </div>
    </div><!-- #dashboard-view -->

  </div><!-- .main-content -->
</div><!-- .app-layout -->

<!-- ── Inline data for JS ──────────────────────────────── -->
<script>
  window.BUDDY_CONFIG = {
    userName:      <?= json_encode($user['name']) ?>,
    userInitials:  <?= json_encode($userInitials) ?>,
    mentorName:    <?= json_encode($user['mentor_name'] ?? 'Mentor Kamu') ?>,
    onboardingDay: <?= $onboardingDay ?>,
  };
</script>
<script src="/onboarding-buddy-system/assets/js/app.js"></script>
</body>
</html>
