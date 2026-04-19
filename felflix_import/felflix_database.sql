-- ================================================================
--  FELFLIX — Base de données complète
--  COMMENT IMPORTER :
--    1. Ouvrir phpMyAdmin → http://localhost/phpmyadmin
--    2. Cliquer sur "Importer" (onglet en haut)
--    3. Choisir ce fichier
--    4. Cliquer "Exécuter"
--  C'est tout ! Les 4 tables seront créées automatiquement.
-- ================================================================

-- Créer la base si elle n'existe pas encore
CREATE DATABASE IF NOT EXISTS felflix
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Utiliser cette base
USE felflix;

-- ================================================================
-- TABLE 1 : users
-- Stocke les comptes des utilisateurs (code de la prof)
-- ================================================================
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS films;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    created_at DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 2 : films
-- Stocke les films importés depuis TMDB (structure de la prof)
-- ================================================================
CREATE TABLE films (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255)  NOT NULL,
    description  TEXT,
    poster       VARCHAR(255),
    release_date DATE,
    duration     INT           DEFAULT 0,
    genre        VARCHAR(500),
    origine      VARCHAR(10),
    rating       DECIMAL(3,1)  DEFAULT 0,
    votes        INT           DEFAULT 0,
    trailer      VARCHAR(500),
    imported_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,

    -- Index pour accélérer les recherches
    INDEX idx_title   (title),
    INDEX idx_rating  (rating),
    INDEX idx_genre   (genre(100)),
    INDEX idx_origine (origine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 3 : favorites
-- Un utilisateur peut sauvegarder des films favoris
-- ================================================================
CREATE TABLE favorites (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL,
    film_id  INT NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY no_doublon (user_id, film_id),
    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (film_id) REFERENCES films(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- TABLE 4 : ratings
-- Un utilisateur peut noter un film (1 à 5 étoiles)
-- ================================================================
CREATE TABLE ratings (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT     NOT NULL,
    film_id  INT     NOT NULL,
    note     TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
    rated_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY une_note_par_film (user_id, film_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (film_id) REFERENCES films(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- Vérification finale
-- ================================================================
SELECT
    table_name    AS 'Table créée',
    table_rows    AS 'Lignes',
    create_time   AS 'Créée le'
FROM information_schema.tables
WHERE table_schema = 'felflix'
ORDER BY table_name;
