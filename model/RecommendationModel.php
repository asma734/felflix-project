<?php
// ============================================================
//  MODEL : Recommendation AI (Breadth-First Search - BFS)
// ============================================================

class RecommendationModel {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? db();
    }

    /**
     * Recommande des films en utilisant l'algorithme BFS pour explorer
     * les connexions (genres, acteurs) à partir d'un film de départ.
     * 
     * @param string $startImdbId ID IMDb du film de départ
     * @param int $maxResults Nombre de recommandations à retourner
     * @param int $maxDepth Profondeur d'exploration (par défaut 2)
     * @return array Liste des films recommandés
     */
    public function getRecommendationsBFS(string $startImdbId, int $maxResults = 10, int $maxDepth = 2): array {
        $queue = [[$startImdbId, 0]]; // [imdb_id, current_depth]
        $visited = [$startImdbId => true];
        $recommendations = [];

        while (!empty($queue) && count($recommendations) < $maxResults) {
            [$currentId, $depth] = array_shift($queue);

            if ($depth >= $maxDepth) continue;

            // 1. Trouver les voisins via les GENRES
            $neighbors = $this->getNeighborsByGenre($currentId);
            foreach ($neighbors as $neighborId) {
                if (!isset($visited[$neighborId])) {
                    $visited[$neighborId] = true;
                    $queue[] = [$neighborId, $depth + 1];
                    
                    // Récupérer les détails du film pour la reco
                    $movie = $this->getMovieDetails($neighborId);
                    if ($movie) {
                        $recommendations[] = $movie;
                        if (count($recommendations) >= $maxResults) break 2;
                    }
                }
            }

            // 2. Trouver les voisins via les ACTEURS (si on n'a pas assez de résultats)
            if (count($recommendations) < $maxResults) {
                $actorNeighbors = $this->getNeighborsByActors($currentId);
                foreach ($actorNeighbors as $neighborId) {
                    if (!isset($visited[$neighborId])) {
                        $visited[$neighborId] = true;
                        $queue[] = [$neighborId, $depth + 1];
                        
                        $movie = $this->getMovieDetails($neighborId);
                        if ($movie) {
                            $recommendations[] = $movie;
                            if (count($recommendations) >= $maxResults) break 2;
                        }
                    }
                }
            }
        }

        return $recommendations;
    }

    /**
     * Trouve les films partageant au moins un genre, triés par note
     */
    private function getNeighborsByGenre(string $imdbId): array {
        $st = $this->pdo->prepare("
            SELECT DISTINCT tg2.imdb_id 
            FROM title_genres tg1
            JOIN title_genres tg2 ON tg1.genre_id = tg2.genre_id
            JOIN titles t ON tg2.imdb_id = t.imdb_id
            WHERE tg1.imdb_id = ? AND tg2.imdb_id <> ?
            AND t.imdb_rating >= 6.0
            ORDER BY t.imdb_rating DESC
            LIMIT 20
        ");
        $st->execute([$imdbId, $imdbId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Trouve les films partageant au moins un acteur
     */
    private function getNeighborsByActors(string $imdbId): array {
        $st = $this->pdo->prepare("
            SELECT DISTINCT tp2.imdb_id 
            FROM title_people tp1
            JOIN title_people tp2 ON tp1.person_id = tp2.person_id
            JOIN titles t ON tp2.imdb_id = t.imdb_id
            WHERE tp1.imdb_id = ? AND tp2.imdb_id <> ?
            AND tp1.role = 'actor' AND tp2.role = 'actor'
            AND t.imdb_rating >= 6.0
            ORDER BY t.imdb_rating DESC
            LIMIT 20
        ");
        $st->execute([$imdbId, $imdbId]);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getMovieDetails(string $imdbId): ?array {
        $st = $this->pdo->prepare("SELECT * FROM titles WHERE imdb_id = ?");
        $st->execute([$imdbId]);
        return $st->fetch() ?: null;
    }
}
