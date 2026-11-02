# BuddyAI — Onboarding Buddy System

> Asisten virtual onboarding interaktif berbasis AI untuk karyawan baru.

---

## Fitur Utama

| Fitur | Keterangan |
|---|---|
| 💬 **Chat BuddyAI** | Tanya-jawab natural dengan AI menggunakan multi-layer system prompt |
| 📚 **Knowledge Base** | Basis data artikel SOP, fasilitas, budaya yang dapat dikelola HR |
| ✅ **Checklist Onboarding** | Checklist per hari dengan auto-advance ke hari berikutnya |
| 🔐 **Auth** | Login berbasis session PHP yang aman |
| 🛡️ **Safety Guardrails** | Eskalasi otomatis ke mentor/HR untuk topik sensitif |

---

## Prasyarat

- **XAMPP** (PHP 8.1+, MySQL 5.7+ / MariaDB 10.3+, Apache)
- **Akun OpenAI** dengan API Key aktif (atau kompatibel OpenAI API)
- Browser modern

---

## Instalasi

### 1. Clone / copy project

```
C:\xampp\htdocs\onboarding-buddy-system\
```

### 2. Import database

1. Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Klik **Import** → pilih file `database.sql`
3. Klik **Go**

Database `onboarding_buddy` akan dibuat otomatis beserta tabel dan data contoh.

### 3. Konfigurasi API Key

Edit `config/app.php`:

```php
define('OPENAI_API_KEY', 'sk-GANTI_DENGAN_API_KEY_KAMU');
define('COMPANY_NAME',   'PT Nama Perusahaan Kamu');
```

> **Alternatif (lebih aman):** Set environment variable `OPENAI_API_KEY` di sistem/server dan biarkan `getenv()` mengambilnya secara otomatis.

### 4. Konfigurasi Database (jika berbeda)

Edit `config/database.php`:

```php
private const HOST   = 'localhost';
private const DBNAME = 'onboarding_buddy';
private const USER   = 'root';
private const PASS   = '';          // isi password MySQL kamu
```

### 5. Aktifkan `mod_rewrite` di Apache

Pastikan baris berikut di `httpd.conf` tidak dikomentari:
```
LoadModule rewrite_module modules/mod_rewrite.so
```
Dan `AllowOverride All` aktif untuk direktori `htdocs`.

### 6. Akses aplikasi

```
http://localhost/onboarding-buddy-system/
```

---

## Akun Default (seed)

| Email | Password | Role |
|---|---|---|
| `admin@perusahaan.id` | `Admin@1234` | HR Admin |
| `budi.santoso@perusahaan.id` | `Admin@1234` | Mentor |
| `rina.maharani@perusahaan.id` | `Admin@1234` | Karyawan Baru |

> ⚠️ **Ganti semua password** ini segera setelah deploy ke production.

---

## Struktur Proyek

```
onboarding-buddy-system/
├── index.php                  ← Entry point (redirect)
├── .htaccess                  ← Apache rewrite & security
├── database.sql               ← Schema + seed data
│
├── config/
│   ├── app.php                ← API key, company name, constants
│   └── database.php           ← PDO singleton
│
├── includes/
│   ├── auth.php               ← Session, login, logout helpers
│   ├── knowledge_base.php     ← KB search (FULLTEXT + LIKE fallback)
│   └── buddy_engine.php       ← System prompt builder + OpenAI caller
│
├── api/
│   ├── auth.php               ← POST login / logout / GET me
│   ├── chat.php               ← POST chat (BuddyAI response)
│   └── checklist.php          ← GET/POST checklist items
│
├── pages/
│   ├── login.php              ← Login page
│   └── app.php                ← Main app shell (chat + checklist)
│
└── assets/
    ├── css/
    │   ├── main.css           ← Global styles, components
    │   └── chat.css           ← Chat & dashboard layout
    └── js/
        └── app.js             ← Chat engine, checklist, navigation
```

---

## Arsitektur Multi-Layer Prompt

```
User Message
     │
     ▼
kb_retrieve()  ←── MySQL FULLTEXT search on knowledge_base table
     │
     ▼
build_system_prompt()
     │  Layer 1: Persona (BuddyAI, gaya bahasa ramah)
     │  Layer 2: Task & Instruksi
     │  Layer 3: Knowledge Base Context (dinamis, per-query)
     │  Layer 4: Guardrails & Eskalasi
     │  Layer 5: Format Output
     │  Layer 6: Profil Karyawan (nama, divisi, mentor, hari ke-)
     │
     ▼
OpenAI API (gpt-4o-mini)
     │
     ▼
Reply → saved to chat_messages → returned to frontend
```

---

## Menambah Artikel Knowledge Base

Melalui phpMyAdmin atau query SQL:

```sql
INSERT INTO knowledge_base (category, title, content, tags) VALUES
('SOP', 'Prosedur Lembur', 'Lembur harus disetujui atasan minimal 1 hari sebelumnya...', 'lembur, overtime, SOP');
```

---

## Menambah Karyawan Baru

```sql
-- Buat hash password dulu via PHP: password_hash('Password123', PASSWORD_BCRYPT)
INSERT INTO users (name, email, password_hash, role, division, position, mentor_id, onboarding_day)
VALUES ('Siti Rahayu', 'siti@perusahaan.id', '$2y$12$...', 'employee', 'Marketing', 'Marketing Staff', 2, 1);
```

---

## Catatan Keamanan (Production)

- [ ] Gunakan HTTPS
- [ ] Set `'secure' => true` di session cookie (`includes/auth.php`)
- [ ] Simpan `OPENAI_API_KEY` di environment variable, bukan hardcode
- [ ] Ganti semua password default
- [ ] Batasi akses `phpMyAdmin` dan `database.sql`
- [ ] Aktifkan rate limiting di endpoint `/api/chat.php`

---

## Lisensi

MIT — bebas digunakan dan dimodifikasi untuk kebutuhan internal perusahaan.
