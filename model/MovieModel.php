<?php
// ============================================================
//  MODEL : MovieModel
//  Gère toutes les requêtes SQL liées aux films locaux (table movies),
//  leurs genres (table genres + movie_genres),
//  et leurs acteurs/réalisateurs (table actors + movie_actors).
//
//  Inspiré de l'architecture Nextflix (modèles séparés + tables pivots).
//  N'utilise PAS les mêmes noms de tables que Nextflix pour ne pas
//  casser l'existant Felflix.
// ============================================================

class MovieModel {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        // On injecte la connexion PDO depuis config/database.php ($cnx)
        $this->pdo = $pdo;
    }


    // ──────────────────────────────────────────────────────────
    //  SECTION 1 — Récupération des films
    // ──────────────────────────────────────────────────────────

    /**
     * Retourne tous les films tunisiens, triés du plus récent au plus ancien.
     */
    public function getAll(): array {
        return $this->pdo
            ->query("SELECT * FROM movies ORDER BY year DESC, id DESC")
            ->fetchAll();
    }

    /**
     * Retourne un film par son ID, ou null s'il n'existe pas.
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Retourne les films d'un genre donné (via l'ID du genre).
     * Utilise la table pivot movie_genres.
     */
    public function getByGenre(int $genreId, int $limit = 20): array {
        $stmt = $this->pdo->prepare(
            "SELECT m.*
             FROM movies m
             JOIN movie_genres mg ON m.id = mg.movie_id
             WHERE mg.genre_id = ?
             ORDER BY m.rating DESC, m.year DESC
             LIMIT $limit"
        );
        $stmt->execute([$genreId]);
        return $stmt->fetchAll();
    }

    /**
     * Recherche de films par titre (recherche partielle LIKE).
     */
    public function search(string $query, int $limit = 20): array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM movies
             WHERE title LIKE ?
             ORDER BY rating DESC
             LIMIT $limit"
        );
        $stmt->execute(['%' . $query . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Retourne les films de Ramadan uniquement.
     */
    public function getRamadanMovies(int $limit = 20): array {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM movies
             WHERE is_ramadan = 1
             ORDER BY rating DESC
             LIMIT $limit"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }


    // ──────────────────────────────────────────────────────────
    //  SECTION 2 — Gestion des genres
    // ──────────────────────────────────────────────────────────

    /**
     * Retourne tous les genres disponibles, triés alphabétiquement.
     */
    public function getAllGenres(): array {
        return $this->pdo
            ->query("SELECT * FROM genres ORDER BY name")
            ->fetchAll();
    }

    /**
     * Retourne les genres associés à un film donné.
     */
    public function getGenresByMovie(int $movieId): array {
        $stmt = $this->pdo->prepare(
            "SELECT g.id, g.name, g.name_ar, g.icon
             FROM genres g
             JOIN movie_genres mg ON g.id = mg.genre_id
             WHERE mg.movie_id = ?
             ORDER BY g.name"
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    }

    /**
     * Associe un genre à un film (table pivot movie_genres).
     * INSERT IGNORE évite les doublons sans erreur.
     */
    public function attachGenre(int $movieId, int $genreId): void {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO movie_genres (movie_id, genre_id) VALUES (?, ?)"
        );
        $stmt->execute([$movieId, $genreId]);
    }

    /**
     * Supprime tous les genres d'un film (utile avant de ré-associer).
     */
    public function detachAllGenres(int $movieId): void {
        $stmt = $this->pdo->prepare(
            "DELETE FROM movie_genres WHERE movie_id = ?"
        );
        $stmt->execute([$movieId]);
    }

    /**
     * Remplace tous les genres d'un film par une nouvelle liste d'IDs.
     * Usage : syncGenres($movieId, [1, 3, 5])
     */
    public function syncGenres(int $movieId, array $genreIds): void {
        $this->detachAllGenres($movieId);
        foreach ($genreIds as $genreId) {
            $this->attachGenre($movieId, (int)$genreId);
        }
    }


    // ──────────────────────────────────────────────────────────
    //  SECTION 3 — Gestion des acteurs
    // ──────────────────────────────────────────────────────────

    /**
     * Retourne les acteurs d'un film, triés par billing_order.
     */
    public function getActorsByMovie(int $movieId): array {
        $stmt = $this->pdo->prepare(
            "SELECT a.id, a.name, a.photo_url, ma.character_name, ma.billing_order
             FROM actors a
             JOIN movie_actors ma ON a.id = ma.actor_id
             WHERE ma.movie_id = ? AND ma.role = 'actor'
             ORDER BY ma.billing_order ASC
             LIMIT 20"
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    }

    /**
     * Retourne les réalisateurs d'un film.
     */
    public function getDirectorsByMovie(int $movieId): array {
        $stmt = $this->pdo->prepare(
            "SELECT a.id, a.name, a.photo_url, ma.billing_order
             FROM actors a
             JOIN movie_actors ma ON a.id = ma.actor_id
             WHERE ma.movie_id = ? AND ma.role = 'director'
             ORDER BY ma.billing_order ASC"
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    }

    /**
     * Retourne la filmographie d'un acteur (tous ses films dans la BDD locale).
     */
    public function getMoviesByActor(int $actorId): array {
        $stmt = $this->pdo->prepare(
            "SELECT m.*, ma.character_name, ma.role
             FROM movies m
             JOIN movie_actors ma ON m.id = ma.movie_id
             WHERE ma.actor_id = ?
             ORDER BY m.year DESC"
        );
        $stmt->execute([$actorId]);
        return $stmt->fetchAll();
    }

    /**
     * Ajoute un acteur à un film avec son rôle et son ordre d'affichage.
     * INSERT IGNORE évite les doublons sur la clé primaire composite.
     */
    public function attachActor(
        int    $movieId,
        int    $actorId,
        string $role          = 'actor',
        string $characterName = '',
        int    $billingOrder  = 99
    ): void {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO movie_actors
             (movie_id, actor_id, role, character_name, billing_order)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$movieId, $actorId, $role, $characterName, $billingOrder]);
    }

    /**
     * Supprime tous les acteurs d'un film (utile avant de ré-associer).
     */
    public function detachAllActors(int $movieId): void {
        $stmt = $this->pdo->prepare(
            "DELETE FROM movie_actors WHERE movie_id = ?"
        );
        $stmt->execute([$movieId]);
    }


    // ──────────────────────────────────────────────────────────
    //  SECTION 4 — Gestion des acteurs (table actors)
    // ──────────────────────────────────────────────────────────

    /**
     * Trouve un acteur par son ID.
     */
    public function findActorById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM actors WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Crée un nouvel acteur et retourne son ID.
     * Si un acteur avec le même nom existe déjà, retourne son ID existant.
     */
    public function createActor(string $name, ?string $photoUrl = null, ?string $bio = null, ?int $birthYear = null): int {
        // Vérifie si l'acteur existe déjà (évite les doublons)
        $stmt = $this->pdo->prepare("SELECT id FROM actors WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            return (int) $existing;
        }

        // Création d'un nouvel acteur
        $stmt = $this->pdo->prepare(
            "INSERT INTO actors (name, photo_url, bio, birth_year)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $photoUrl, $bio, $birthYear]);
        return (int) $this->pdo->lastInsertId();
    }
}
