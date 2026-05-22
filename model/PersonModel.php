<?php
// ============================================================
//  MODEL : Personnes (Acteurs, Réalisateurs...)
// ============================================================
class PersonModel {
    private PDO $pdo;
    public function __construct(?PDO $pdo = null) { $this->pdo = $pdo ?? db(); }

    public function findById(int $id): ?array {
        $st=$this->pdo->prepare("SELECT * FROM people WHERE id=?");
        $st->execute([$id]); return $st->fetch() ?: null;
    }

    /**
     * Récupère les personnes liées à un film par leur rôle
     */
    public function getByTitleAndRole(string $imdbId, string $role, int $limit=20): array {
        $st=$this->pdo->prepare(
            "SELECT p.id, p.name 
             FROM people p 
             JOIN title_people tp ON p.id=tp.person_id 
             WHERE tp.imdb_id=? AND tp.role=? 
             ORDER BY tp.billing_order LIMIT $limit");
        $st->execute([$imdbId,$role]); return $st->fetchAll();
    }

    public function getActorsByTitle(string $imdbId): array { 
        return $this->getByTitleAndRole($imdbId, 'actor', 5); // Limité à 5 pour l'affichage rapide
    }
}