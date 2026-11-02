-- ============================================================
-- BuddyAI Onboarding System — Database Schema
-- Engine: MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `onboarding_buddy`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `onboarding_buddy`;

-- ------------------------------------------------------------
-- Users (HR admins + new employees)
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`            VARCHAR(120)  NOT NULL,
  `email`           VARCHAR(180)  NOT NULL UNIQUE,
  `password_hash`   VARCHAR(255)  NOT NULL,
  `role`            ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  `division`        VARCHAR(100)  NULL,
  `position`        VARCHAR(100)  NULL,
  `mentor_id`       INT UNSIGNED  NULL,
  `onboarding_day`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `avatar`          VARCHAR(255)  NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Knowledge Base articles
-- ------------------------------------------------------------
CREATE TABLE `knowledge_base` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category`    VARCHAR(80)  NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `content`     TEXT         NOT NULL,
  `tags`        VARCHAR(255) NULL COMMENT 'comma-separated keywords',
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`  INT UNSIGNED NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_category` (`category`),
  INDEX `idx_is_active` (`is_active`),
  FULLTEXT INDEX `ft_content` (`title`, `content`, `tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Onboarding checklist templates (per-day tasks)
-- ------------------------------------------------------------
CREATE TABLE `checklist_templates` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `day`         TINYINT UNSIGNED NOT NULL COMMENT 'Onboarding day number',
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT         NULL,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Employee checklist progress
-- ------------------------------------------------------------
CREATE TABLE `checklist_progress` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `template_id` INT UNSIGNED NOT NULL,
  `is_done`     TINYINT(1)   NOT NULL DEFAULT 0,
  `done_at`     TIMESTAMP    NULL,
  UNIQUE KEY `uq_user_template` (`user_id`, `template_id`),
  CONSTRAINT `fk_cp_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)                ON DELETE CASCADE,
  CONSTRAINT `fk_cp_template` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Chat sessions
-- ------------------------------------------------------------
CREATE TABLE `chat_sessions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `started_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Chat messages
-- ------------------------------------------------------------
CREATE TABLE `chat_messages` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT UNSIGNED NOT NULL,
  `role`       ENUM('user','assistant') NOT NULL,
  `content`    TEXT         NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_session` (`session_id`),
  CONSTRAINT `fk_cm_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed data
-- ============================================================

-- Admin user  (password: Admin@1234)
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
('HR Administrator', 'admin@perusahaan.id',
 '$2y$12$YKqJ3eH0RQ3kQzL9sU2p8.Nl8/Ev7FbJk9gScSQlbcN7u5TbJq5Le', 'admin');

-- Sample mentor
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `division`, `position`) VALUES
('Budi Santoso', 'budi.santoso@perusahaan.id',
 '$2y$12$YKqJ3eH0RQ3kQzL9sU2p8.Nl8/Ev7FbJk9gScSQlbcN7u5TbJq5Le', 'employee', 'Engineering', 'Senior Engineer');

-- Sample new employee  (password: Admin@1234, mentor = Budi)
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `division`, `position`, `mentor_id`, `onboarding_day`) VALUES
('Rina Maharani', 'rina.maharani@perusahaan.id',
 '$2y$12$YKqJ3eH0RQ3kQzL9sU2p8.Nl8/Ev7FbJk9gScSQlbcN7u5TbJq5Le', 'employee', 'Engineering', 'Junior Developer', 2, 1);

-- Knowledge Base seed
INSERT INTO `knowledge_base` (`category`, `title`, `content`, `tags`) VALUES
('SOP', 'Jam Kerja & Absensi',
 'Jam kerja resmi adalah Senin–Jumat, pukul 08.00–17.00 WIB. Absensi dilakukan melalui aplikasi HRIS setiap hari. Toleransi keterlambatan adalah 15 menit. Lebih dari itu dianggap terlambat dan akan dicatat.',
 'jam kerja, absensi, keterlambatan'),
('Fasilitas', 'Area Kantor & Fasilitas Umum',
 'Kantor terdiri dari 3 lantai. Lantai 1: Resepsionis & Ruang Meeting. Lantai 2: Operasional & HR. Lantai 3: Engineering & Product. Tersedia ruang makan, musala, dan area santai di setiap lantai.',
 'kantor, fasilitas, lantai, ruang'),
('Budaya', 'Nilai & Budaya Perusahaan',
 'Perusahaan menjunjung tinggi nilai ICARE: Integrity, Collaboration, Adaptability, Respect, Excellence. Setiap karyawan diharapkan mencerminkan nilai-nilai ini dalam pekerjaan sehari-hari.',
 'nilai, budaya, icare, etika'),
('Administratif', 'Pengajuan Cuti & Izin',
 'Pengajuan cuti dilakukan melalui portal HRIS minimal 3 hari kerja sebelumnya. Cuti tahunan berjumlah 12 hari per tahun (dapat digunakan setelah 3 bulan masa kerja). Izin mendadak diberitahukan ke atasan langsung via WhatsApp atau email.',
 'cuti, izin, hris, libur'),
('Onboarding', 'Checklist Hari Pertama',
 'Hari pertama onboarding mencakup: (1) Lapor ke resepsionis dan ambil ID card. (2) Pertemuan orientasi dengan HR pukul 09.00. (3) Pengenalan tim dan setup perangkat kerja. (4) Tur singkat fasilitas kantor. (5) Sesi makan siang dengan mentor.',
 'hari pertama, checklist, orientasi, id card');

-- Checklist templates
INSERT INTO `checklist_templates` (`day`, `title`, `description`, `sort_order`) VALUES
(1, 'Ambil ID Card di Resepsionis',      'Tunjukkan surat penerimaan kerja ke resepsionis untuk mendapatkan ID Card sementara.', 1),
(1, 'Hadiri Orientasi HR (09.00 WIB)',   'Sesi orientasi awal bersama tim HR di Ruang Meeting Lantai 1.',                       2),
(1, 'Setup Perangkat Kerja',             'Hubungi IT Support (ext. 101) untuk aktivasi laptop, email, dan akses sistem.',       3),
(1, 'Tur Fasilitas Kantor',              'Mentor akan menemanimu berkeliling area kantor.',                                     4),
(1, 'Makan Siang Bersama Mentor',        'Kesempatan berkenalan lebih dekat dengan mentor di kantin lantai 2.',                  5),
(2, 'Perkenalan dengan Tim Divisi',      'Hadir pada stand-up meeting pagi bersama seluruh anggota divisi.',                    1),
(2, 'Pelajari Alur Kerja & Tools',       'Mentor akan menjelaskan workflow, tools (Jira, Confluence, Slack), dan standar tim.', 2),
(2, 'Akses Portal HRIS',                 'Login ke portal HRIS (hris.perusahaan.id) untuk verifikasi data diri.',               3),
(3, 'Review SOP Divisi',                 'Baca dan pahami dokumen SOP divisi yang dibagikan oleh mentor.',                      1),
(3, 'Sesi 1-on-1 dengan Atasan',         'Meeting singkat (30 menit) bersama manajer divisi untuk penyesuaian ekspektasi.',     2);
