<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BuddyAI — Login</title>
  <link rel="stylesheet" href="/onboarding-buddy-system/assets/css/main.css">
  <style>
    body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--primary-soft); }

    .login-card {
      width: 100%; max-width: 420px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 40px 36px;
      box-shadow: 0 8px 40px rgba(79,70,229,.12);
    }
    .login-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 32px;
    }
    .login-logo .logo-mark {
      width: 48px; height: 48px;
      border-radius: 14px;
      background: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.5rem;
    }
    .login-logo .logo-text { font-size: 1.5rem; font-weight: 800; }
    .login-logo .logo-text em { color: var(--primary); font-style: normal; }

    .login-subtitle { color: var(--text-muted); font-size: .9rem; margin-bottom: 28px; }

    .btn-login { width: 100%; justify-content: center; padding: 13px; font-size: 1rem; }
    .login-footer { text-align: center; margin-top: 20px; font-size: .83rem; color: var(--text-muted); }
  </style>
</head>
<body>

<div class="login-card">
  <div class="login-logo">
    <div class="logo-mark">🤝</div>
    <div class="logo-text">Buddy<em>AI</em></div>
  </div>

  <p class="login-subtitle">
    Selamat datang! Masuk untuk memulai perjalanan onboarding kamu bersama BuddyAI.
  </p>

  <div id="alert-box"></div>

  <form id="login-form" novalidate>
    <div class="form-group">
      <label class="form-label" for="email">Email Perusahaan</label>
      <input class="form-input" type="email" id="email" name="email"
             placeholder="nama@perusahaan.id" required autocomplete="username">
    </div>
    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input class="form-input" type="password" id="password" name="password"
             placeholder="••••••••" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary btn-login" id="submit-btn">
      Masuk
    </button>
  </form>

  <p class="login-footer">
    Butuh bantuan? Hubungi HR di <a href="mailto:hr@perusahaan.id">hr@perusahaan.id</a>
  </p>
</div>

<script>
  const form       = document.getElementById('login-form');
  const alertBox   = document.getElementById('alert-box');
  const submitBtn  = document.getElementById('submit-btn');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    alertBox.innerHTML = '';
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Masuk...';

    const payload = {
      email:    document.getElementById('email').value.trim(),
      password: document.getElementById('password').value,
    };

    try {
      const res  = await fetch('/onboarding-buddy-system/api/auth.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (res.ok && data.success) {
        window.location.href = '/onboarding-buddy-system/pages/app.php';
      } else {
        showAlert(data.error || 'Terjadi kesalahan. Silakan coba lagi.', 'error');
      }
    } catch (err) {
      showAlert('Tidak dapat terhubung ke server.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Masuk';
    }
  });

  function showAlert(msg, type = 'error') {
    alertBox.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
  }
</script>
</body>
</html>
