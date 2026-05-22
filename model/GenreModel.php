<?php
// ============================================================
//  MODEL : Genres, Pays et Langues
// ============================================================
class GenreModel {
    private PDO $pdo;
    public function __construct(?PDO $pdo = null) { $this->pdo = $pdo ?? db(); }

    public function getAllGenres(): array    { return $this->pdo->query("SELECT id,name FROM genres ORDER BY name")->fetchAll(); }
    public function getAllCountries(): array { return $this->pdo->query("SELECT id,name FROM countries ORDER BY name")->fetchAll(); }
    public function getAllLanguages(): array { return $this->pdo->query("SELECT id,name FROM languages ORDER BY name")->fetchAll(); }

    public function getByTitle(string $imdbId): array {
        $st=$this->pdo->prepare("SELECT g.id,g.name FROM genres g JOIN title_genres tg ON g.id=tg.genre_id WHERE tg.imdb_id=? ORDER BY g.name");
        $st->execute([$imdbId]); return $st->fetchAll();
    }

    public function findIdByName(string $name): ?int {
        $st=$this->pdo->prepare("SELECT id FROM genres WHERE name=? LIMIT 1");
        $st->execute([$name]); $r=$st->fetch(); return $r?(int)$r['id']:null;
    }
}
