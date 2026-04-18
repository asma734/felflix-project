-- ============================================================
--  FELFLIX — Script d'installation complet (v6)
--  Lances ce fichier dans phpMyAdmin > onglet SQL
--  Il crée la base + toutes les tables + un compte admin test
-- ============================================================

CREATE DATABASE IF NOT EXISTS felflix
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE felflix;

-- ─────────────────────────────────────────────
--  TABLE: users
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('user','admin') DEFAULT 'user',
    avatar     VARCHAR(20)   DEFAULT '🌶',
    bio        TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: movies  (films tunisiens locaux)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS movies (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    genre       VARCHAR(50)  NOT NULL,
    country     VARCHAR(100) DEFAULT 'Tunisia',
    year        YEAR NOT NULL,
    rating      DECIMAL(3,1) DEFAULT 0.0,
    pepper      TINYINT      DEFAULT 3,
    is_ramadan  TINYINT      DEFAULT 0,
    description TEXT,
    trailer_url VARCHAR(255),
    emoji       VARCHAR(10)  DEFAULT '🎬',
    bg_color    VARCHAR(20)  DEFAULT '#e6394633',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: watchlist
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS watchlist (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    movie_id    INT DEFAULT NULL,
    tmdb_id     INT DEFAULT NULL,
    tmdb_title  VARCHAR(255) DEFAULT NULL,
    tmdb_poster VARCHAR(300) DEFAULT NULL,
    tmdb_type   ENUM('movie','tv') DEFAULT 'movie',
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_local(user_id, movie_id),
    UNIQUE KEY uq_tmdb(user_id, tmdb_id, tmdb_type),
    FOREIGN KEY(user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY(movie_id)  REFERENCES movies(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: comments  (avis sur films TMDB/locaux)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    movie_id   INT DEFAULT NULL,
    tmdb_id    INT DEFAULT NULL,
    tmdb_type  ENUM('movie','tv') DEFAULT 'movie',
    content    TEXT NOT NULL,
    likes      INT  DEFAULT 0,
    is_deleted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: posts  (mur communautaire général)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    content     TEXT NOT NULL,
    tmdb_id     INT DEFAULT NULL,
    tmdb_title  VARCHAR(255) DEFAULT NULL,
    tmdb_type   ENUM('movie','tv') DEFAULT 'movie',
    likes       INT  DEFAULT 0,
    is_deleted  TINYINT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: post_likes
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS post_likes (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    UNIQUE KEY uq(user_id, post_id),
    FOREIGN KEY(user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY(post_id) REFERENCES posts(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  TABLE: comment_likes
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comment_likes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    comment_id INT NOT NULL,
    UNIQUE KEY uq(user_id, comment_id),
    FOREIGN KEY(user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY(comment_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
--  SI LES TABLES EXISTENT DEJA — ajouter colonnes manquantes
--  (si t'as déjà lancé un ancien script)
-- ─────────────────────────────────────────────
ALTER TABLE comments
    ADD COLUMN IF NOT EXISTS is_deleted TINYINT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tmdb_id    INT     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS tmdb_type  ENUM('movie','tv') DEFAULT 'movie';

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS is_deleted TINYINT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS tmdb_type  ENUM('movie','tv') DEFAULT 'movie';

-- ─────────────────────────────────────────────
--  DONNEES DE BASE: films tunisiens
-- ─────────────────────────────────────────────
INSERT IGNORE INTO movies(title,genre,country,year,rating,pepper,is_ramadan,description,emoji,bg_color) VALUES
('رجل الرماد','drame','Tunisia',1986,4.5,5,0,'T7fa mta3 Nouri Bouzid 3al masculinité.','🎭','#7c3aed33'),
('العصفور','drame','Tunisia',1973,4.9,5,0,'Film classique tounssi 3al 7ayat cha3biya.','🕊️','#7c3aed33'),
('برهوم وبثينة','comedie','Tunisia',2022,4.2,3,0,'Comédie tounssia 3al couple mta3 kol nhar.','😂','#d9770633'),
('خرما','comedie','Tunisia',1999,4.6,4,0,'Film culte 100% tounssi!','😄','#d9770633'),
('ليالي تونس النيون','action','Tunisia',2024,4.3,4,0,'Thriller action fi chaware3 Tunis.','🌆','#e6394633'),
('رمضان كريم','drame','Tunisia',2023,4.1,3,1,'Selsela ramadan tounssia.','🌙','#f59e0b33');

-- ─────────────────────────────────────────────
--  COMPTE ADMIN DE TEST
--  Password: admin123
-- ─────────────────────────────────────────────
INSERT IGNORE INTO users(nom,email,password,role,avatar,bio)
VALUES(
    'Admin Felflix',
    'admin@felflix.tn',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    '🌶',
    'Moul el Felflix 🌶'
);
-- NOTE: Le mot de passe ci-dessus est 'password' (hash bcrypt par défaut Laravel)
-- Pour admin123, crée le compte via signup.php avec le code FELLIX2025
-- OU utilise ce hash correct pour admin123:
UPDATE users SET password='$2y$10$R0TNXxHXFCMtmv9ViVbDYOKUrFKsZ0Jn8VR9mP8b/xAvj6c.Oi3Oy'
WHERE email='admin@felflix.tn';
