-- ============================================================
--  FELFLIX v7 — Script d'upgrade complet
--  Exécuter dans phpMyAdmin > onglet SQL
--  Ajoute les nouvelles tables et colonnes manquantes
-- ============================================================

USE felflix;

-- ─────────────────────────────────────────────
--  TABLE: moods (si pas déjà créée)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS moods (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(50)  NOT NULL UNIQUE,
    name_fr             VARCHAR(50)  DEFAULT NULL,
    icon                VARCHAR(20)  NOT NULL,
    color               VARCHAR(20)  NOT NULL,
    tone                VARCHAR(50)  DEFAULT 'neutral',
    emotional_intensity TINYINT      DEFAULT 5,
    pace                ENUM('slow','medium','fast') DEFAULT 'medium',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: watch_history (si pas déjà créée)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS watch_history (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    tmdb_id    INT DEFAULT NULL,
    tmdb_type  ENUM('movie','tv') DEFAULT 'movie',
    tmdb_title VARCHAR(255) DEFAULT NULL,
    movie_id   INT DEFAULT NULL,
    mood_id    INT DEFAULT NULL,
    added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY(mood_id)  REFERENCES moods(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: emotional_profiles
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS emotional_profiles (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL UNIQUE,
    romantic_pct     FLOAT DEFAULT 0,
    nostalgic_pct    FLOAT DEFAULT 0,
    dark_pct         FLOAT DEFAULT 0,
    action_pct       FLOAT DEFAULT 0,
    happy_pct        FLOAT DEFAULT 0,
    sad_pct          FLOAT DEFAULT 0,
    anxious_pct      FLOAT DEFAULT 0,
    diversity_score  FLOAT DEFAULT 0,
    balance_score    FLOAT DEFAULT 0,
    dominant_mood    VARCHAR(50) DEFAULT NULL,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: chatbot_logs
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chatbot_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT DEFAULT NULL,
    user_message TEXT NOT NULL,
    bot_reply    TEXT NOT NULL,
    mood_tags    VARCHAR(255) DEFAULT NULL,
    session_id   VARCHAR(100) DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: scenes (recommandations par scène)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS scenes (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    tmdb_id           INT DEFAULT NULL,
    tmdb_type         ENUM('movie','tv') DEFAULT 'movie',
    title             VARCHAR(255) NOT NULL,
    scene_description TEXT NOT NULL,
    scene_emotion     VARCHAR(50) NOT NULL,
    timestamp_sec     INT DEFAULT NULL,
    mood_id           INT DEFAULT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(mood_id) REFERENCES moods(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: friendships (social features)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS friendships (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    friend_id  INT NOT NULL,
    status     ENUM('pending','accepted','blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq(user_id, friend_id),
    FOREIGN KEY(user_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(friend_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: post_comments (comments sur posts)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS post_comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT NOT NULL,
    user_id    INT NOT NULL,
    content    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(post_id) REFERENCES posts(id)  ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  ADD MISSING COLUMNS to existing tables
-- ─────────────────────────────────────────────

-- watchlist: add category_name if missing
ALTER TABLE watchlist
    ADD COLUMN IF NOT EXISTS category_name VARCHAR(100) DEFAULT 'My List';

-- users: add cover_image if missing
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS cover_image VARCHAR(300) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS followers_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS following_count INT DEFAULT 0;

-- posts: add image_url if missing
ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS image_url VARCHAR(300) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS tmdb_poster VARCHAR(300) DEFAULT NULL;

-- ─────────────────────────────────────────────
--  DONNÉES MOODS (les 9 piments)
-- ─────────────────────────────────────────────
INSERT IGNORE INTO moods(name, name_fr, icon, color, tone, emotional_intensity, pace) VALUES
('7zin',          'Triste / Déprimé',  '🫙', '#4a4a8a', 'dark',        3, 'slow'),
('te3ben',        'Fatigué',           '😮‍💨', '#6b5b7b', 'calm',        2, 'slow'),
('far7an',        'Heureux',           '🌶️', '#22c55e', 'light',       7, 'medium'),
('heyej',         'Excité',            '⚡', '#eab308', 'intense',     9, 'fast'),
('motive',        'Motivé',            '🔥', '#f97316', 'intense',     8, 'fast'),
('metghachchech', 'Énervé / En colère','💢', '#ef4444', 'chaotic',     8, 'fast'),
('tfakkart',      'Nostalgique',       '🌅', '#f59e0b', 'reflective',  6, 'slow'),
('5ayef',         'Anxieux',           '💫', '#06b6d4', 'tense',       7, 'medium'),
('romansi',       'Romantique',        '💖', '#ec4899', 'warm',        6, 'slow');

-- ─────────────────────────────────────────────
--  DONNÉES SCÈNES d'exemple
-- ─────────────────────────────────────────────
INSERT IGNORE INTO scenes(tmdb_id, tmdb_type, title, scene_description, scene_emotion, mood_id) VALUES
(278,  'movie', 'The Shawshank Redemption', 'Andy joue de la musique sur haut-parleurs de la prison — liberté absolue', 'motivated',  (SELECT id FROM moods WHERE name='motive')),
(238,  'movie', 'The Godfather',            'Don Corleone parle à son fils Sonny dans le jardin — sagesse silencieuse', 'reflective', (SELECT id FROM moods WHERE name='tfakkart')),
(680,  'movie', 'Pulp Fiction',             'La scène de danse entre Vincent et Mia', 'excited',    (SELECT id FROM moods WHERE name='heyej')),
(13,   'movie', 'Forrest Gump',             'Forrest court à travers les USA — courir pour oublier', 'nostalgic',  (SELECT id FROM moods WHERE name='tfakkart')),
(19404,'movie', 'Dilwale Dulhania Le Jayenge', 'Raj étend la main dans le train — amour qui survit', 'romantic',   (SELECT id FROM moods WHERE name='romansi'));
