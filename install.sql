-- ─────────────────────────────────────────────────────────────
-- The Final Chapter – Datenbankschema
-- Kompatibel mit: MySQL 8.0.16+ / MariaDB 10.3+
-- Import: mysql -u USER -p DATENBANKNAME < install.sql
-- ─────────────────────────────────────────────────────────────

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────
-- Kategorien
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id   INT UNSIGNED NOT NULL DEFAULT 0,
  name        VARCHAR(120) NOT NULL,
  slug        VARCHAR(120) NOT NULL UNIQUE,
  description TEXT,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Artikel
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS articles (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(255) NOT NULL,
  slug            VARCHAR(255) NOT NULL UNIQUE,
  content         LONGTEXT,
  excerpt         TEXT,
  category_id     INT UNSIGNED NOT NULL DEFAULT 1,
  author          VARCHAR(120) NOT NULL DEFAULT 'Redaktion',
  featured_image  VARCHAR(500),
  status          ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  archived_from_status ENUM('draft','published') NULL,
  archived_at     DATETIME NULL,
  views           INT UNSIGNED NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_articles_archive_state CHECK (
    (status = 'archived' AND archived_from_status IS NOT NULL AND archived_at IS NOT NULL)
    OR
    (status <> 'archived' AND archived_from_status IS NULL AND archived_at IS NULL)
  ),
  INDEX idx_articles_status (status),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Benutzer (Redakteure)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(80) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,  -- bcrypt hash
  email      VARCHAR(180),
  role       ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Öffentliche Autorenprofile (getrennt von Login-Konten)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS author_profiles (
  slug         VARCHAR(120) NOT NULL PRIMARY KEY,
  display_name VARCHAR(120) NOT NULL,
  role_label   VARCHAR(120) NOT NULL DEFAULT 'Redaktion',
  bio          TEXT NULL,
  image_path   VARCHAR(500) NULL,
  is_visible   TINYINT(1) NOT NULL DEFAULT 1,
  sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 100,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_author_profiles_visible_sort (is_visible, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ─────────────────────────────────────────────────────────────
-- Demo-Daten
-- ─────────────────────────────────────────────────────────────

-- Standard-Kategorien
INSERT IGNORE INTO categories (name, slug, description) VALUES
  ('News',                   'news',          'Aktuelle News aus der Metal-Welt'),
  ('Liveberichte/Festivals', 'festivals',     'Liveberichte und Festival-Rückblicke'),
  ('Reviews',                'reviews',       'Album-, CD- und DVD-Reviews'),
  ('Festivalnews',           'festival-news', 'Interne Mutterkategorie für festivalbezogene News');

-- Aus Sicherheitsgründen wird kein Standard-Admin angelegt.
-- Das erste Konto muss mit individuellen Zugangsdaten separat erstellt werden.

-- Öffentliche Autorenprofile
INSERT IGNORE INTO author_profiles (slug, display_name, role_label, sort_order) VALUES
  ('michael-jakob', 'Michael Jakob', 'Redaktion', 10),
  ('thomas-schwarz', 'Thomas Schwarz', 'Redaktion', 20),
  ('kay-herzer', 'Kay Herzer', 'Redaktion', 30),
  ('matthias-eichhorn', 'Dr. med. Matthias Eichhorn', 'Redaktion', 40),
  ('alexander-goehring', 'Alexander Göhring', 'Redaktion', 50);

-- Beispiel-Artikel
INSERT IGNORE INTO articles (title, slug, content, excerpt, category_id, author, status) VALUES
  ('Willkommen bei The Final Chapter',
   'willkommen',
   '<p>The Final Chapter ist zurück – mit neuem CMS, frischem Design und dem gleichen Feuer seit 2003.</p><p>Wir berichten wieder über Konzerte, Alben und alles, was die Metal-Welt bewegt. Stay heavy! 🤘</p>',
   'The Final Chapter ist zurück – mit neuem CMS, frischem Design und dem gleichen Feuer seit 2003.',
   1, 'Redaktion', 'published'),

  ('Iron Maiden kündigen neue Welttournee an',
   'iron-maiden-welttournee-2026',
   '<p>Iron Maiden haben eine neue Welttournee für 2026 angekündigt. Die Legende aus London wird ab Sommer durch Europa touren – natürlich mit Eddie im Gepäck.</p>',
   'Iron Maiden kündigen riesige Welttournee für 2026 an. Europa-Termine im Sommer.',
   1, 'Redaktion', 'published'),

  ('Amon Amarth – Berserker II (Review)',
   'amon-amarth-berserker-ii-review',
   '<p>Die schwedischen Wikinger legen nach. <em>Berserker II</em> ist kompromissloses Death Metal Handwerk mit eingängigen Refrains und Johan Heggs mächtigem Growl.</p><p><strong>Wertung: 9/10</strong></p>',
   'Amon Amarth liefern mit Berserker II ein kompromissloses Death-Metal-Album. Review.',
   2, 'Redaktion', 'published');
