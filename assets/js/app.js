/**
 * BuddyAI — Main Frontend Application
 * Handles: navigation, chat (send/receive), checklist, markdown rendering
 */

/* ── Globals ─────────────────────────────────────────────── */
const cfg        = window.BUDDY_CONFIG || {};
let   sessionId  = null;
let   isSending  = false;

/* ── DOM refs ────────────────────────────────────────────── */
const messagesEl   = document.getElementById('messages');
const chatInput    = document.getElementById('chat-input');
const sendBtn      = document.getElementById('send-btn');
const chatView     = document.getElementById('chat-view');
const dashView     = document.getElementById('dashboard-view');
const navChat      = document.getElementById('nav-chat');
const navDashboard = document.getElementById('nav-dashboard');

/* ================================================================
   NAVIGATION
   ================================================================ */
function showView(name) {
  chatView.classList.toggle('hidden', name !== 'chat');
  dashView.classList.toggle('hidden', name !== 'dashboard');
  navChat.classList.toggle('active', name === 'chat');
  navDashboard.classList.toggle('active', name === 'dashboard');

  if (name === 'dashboard') loadChecklist();
}

navChat.addEventListener('click', (e) => { e.preventDefault(); showView('chat'); });
navDashboard.addEventListener('click', (e) => { e.preventDefault(); showView('dashboard'); });

/* ================================================================
   CHAT
   ================================================================ */

/** Very lightweight Markdown → HTML (bold, bullet lists, numbered lists, line breaks) */
function renderMarkdown(text) {
  return text
    // bold
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    // numbered list items
    .replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>')
    // bullet list items
    .replace(/^[-*]\s+(.+)$/gm, '<li>$1</li>')
    // wrap consecutive <li> blocks in <ul>
    .replace(/(<li>.*<\/li>\n?)+/gs, (m) => `<ul>${m}</ul>`)
    // paragraphs (blank line separated)
    .replace(/\n{2,}/g, '</p><p>')
    // remaining newlines → <br>
    .replace(/\n/g, '<br>')
    // wrap in <p>
    .replace(/^(.+)$/s, '<p>$1</p>');
}

function appendMessage(role, text) {
  const msg = document.createElement('div');
  msg.className = `msg ${role}`;

  const avatarHTML = role === 'assistant'
    ? `<div class="avatar avatar-sm" style="background:linear-gradient(135deg,#4F46E5,#06B6D4);">🤖</div>`
    : `<div class="avatar avatar-sm">${cfg.userInitials || '👤'}</div>`;

  const bubble = document.createElement('div');
  bubble.className = 'msg-bubble';

  if (role === 'assistant') {
    bubble.innerHTML = renderMarkdown(text);
  } else {
    bubble.textContent = text;
  }

  msg.innerHTML = avatarHTML;
  msg.appendChild(bubble);
  messagesEl.appendChild(msg);
  messagesEl.scrollTop = messagesEl.scrollHeight;
  return msg;
}

function showTyping() {
  const el = document.createElement('div');
  el.className = 'msg assistant typing-indicator';
  el.id = 'typing';
  el.innerHTML = `
    <div class="avatar avatar-sm" style="background:linear-gradient(135deg,#4F46E5,#06B6D4);">🤖</div>
    <div class="msg-bubble">
      <span class="dot"></span><span class="dot"></span><span class="dot"></span>
    </div>`;
  messagesEl.appendChild(el);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}

function hideTyping() {
  const el = document.getElementById('typing');
  if (el) el.remove();
}

async function sendMessage() {
  const text = chatInput.value.trim();
  if (!text || isSending) return;

  isSending = true;
  sendBtn.disabled = true;
  chatInput.value = '';
  chatInput.style.height = 'auto';

  appendMessage('user', text);
  showTyping();

  try {
    const res  = await fetch('/onboarding-buddy-system/api/chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text, session_id: sessionId }),
    });
    const data = await res.json();

    hideTyping();

    if (res.ok) {
      sessionId = data.session_id;
      appendMessage('assistant', data.reply);
    } else {
      appendMessage('assistant', data.error || 'Terjadi kesalahan. Silakan coba lagi.');
    }
  } catch (err) {
    hideTyping();
    appendMessage('assistant', 'Maaf, BuddyAI tidak dapat terhubung saat ini. Periksa koneksi internet kamu. 🙏');
  }

  isSending = false;
  sendBtn.disabled = false;
  chatInput.focus();
}

/* Send on button click */
sendBtn.addEventListener('click', sendMessage);

/* Send on Enter (Shift+Enter = newline) */
chatInput.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

/* Auto-resize textarea */
chatInput.addEventListener('input', () => {
  chatInput.style.height = 'auto';
  chatInput.style.height = Math.min(chatInput.scrollHeight, 120) + 'px';
});

/* ================================================================
   CHECKLIST
   ================================================================ */
async function loadChecklist() {
  const container = document.getElementById('checklist-items');
  container.innerHTML = `
    <div style="text-align:center; padding:40px; color:var(--text-muted);">
      <div class="spinner"></div>
      <p class="mt-2">Memuat checklist…</p>
    </div>`;

  try {
    const res  = await fetch('/onboarding-buddy-system/api/checklist.php');
    const data = await res.json();

    if (!res.ok) throw new Error(data.error);

    renderChecklist(data.items);
  } catch (err) {
    container.innerHTML = `<div class="alert alert-error">Gagal memuat checklist. Silakan refresh halaman.</div>`;
  }
}

function renderChecklist(items) {
  const container = document.getElementById('checklist-items');
  container.innerHTML = '';

  if (!items || items.length === 0) {
    container.innerHTML = `<div class="alert alert-info">Belum ada checklist untuk hari ini. Silakan hubungi HR.</div>`;
    updateProgress(0, 0);
    return;
  }

  const doneCount = items.filter(i => i.is_done).length;
  updateProgress(doneCount, items.length);

  items.forEach(item => {
    const div = document.createElement('div');
    div.className = 'checklist-item' + (item.is_done ? ' done' : '');
    div.dataset.id = item.template_id;

    div.innerHTML = `
      <input type="checkbox" ${item.is_done ? 'checked' : ''}
             aria-label="${escHtml(item.title)}">
      <div class="item-text">
        <div class="item-title">${escHtml(item.title)}</div>
        ${item.description ? `<div class="item-desc">${escHtml(item.description)}</div>` : ''}
      </div>`;

    const cb = div.querySelector('input');
    cb.addEventListener('change', () => toggleItem(item.template_id, cb.checked, div));

    container.appendChild(div);
  });
}

async function toggleItem(templateId, isDone, divEl) {
  divEl.classList.toggle('done', isDone);

  try {
    const res  = await fetch('/onboarding-buddy-system/api/checklist.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ template_id: templateId, is_done: isDone }),
    });
    const data = await res.json();

    if (!res.ok) throw new Error(data.error);

    // Recount from DOM
    const all  = document.querySelectorAll('.checklist-item');
    const done = document.querySelectorAll('.checklist-item.done');
    updateProgress(done.length, all.length);

    if (data.advanced) {
      showDayAdvancedToast();
    }
  } catch (err) {
    // Revert optimistic UI
    divEl.classList.toggle('done', !isDone);
    divEl.querySelector('input').checked = !isDone;
  }
}

function updateProgress(done, total) {
  const label = document.getElementById('progress-label');
  const bar   = document.getElementById('progress-bar');
  if (!label || !bar) return;
  label.textContent = `${done} / ${total}`;
  bar.style.width   = total > 0 ? Math.round((done / total) * 100) + '%' : '0%';
}

function showDayAdvancedToast() {
  const toast = document.createElement('div');
  toast.className = 'alert alert-success';
  toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;box-shadow:var(--shadow);max-width:320px;';
  toast.innerHTML = '🎉 <strong>Selamat!</strong> Semua tugas hari ini selesai. Kamu telah maju ke hari berikutnya!';
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 5000);
}

/* ================================================================
   LOGOUT
   ================================================================ */
function logout() {
  if (confirm('Apakah kamu yakin ingin keluar?')) {
    window.location.href = '/onboarding-buddy-system/api/auth.php?action=logout';
  }
}

/* ================================================================
   UTILITIES
   ================================================================ */
function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

/* ================================================================
   INIT — welcome message
   ================================================================ */
(function init() {
  const welcome = `Halo, **${cfg.userName}**! 👋 Selamat datang di hari ke-${cfg.onboardingDay} onboarding kamu.\n\nAku **BuddyAI**, asisten virtual yang siap membantu kamu mengenal perusahaan, menjawab pertanyaan seputar SOP, fasilitas, dan budaya kerja.\n\nMentor kamu hari ini adalah **${cfg.mentorName}**. Ada yang ingin kamu tanyakan untuk memulai hari ini?`;
  appendMessage('assistant', welcome);
  chatInput.focus();
})();
