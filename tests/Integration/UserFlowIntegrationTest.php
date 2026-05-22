<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controller/traitement.php';
require_once __DIR__ . '/../../model/MovieModel.php';

class UserFlowIntegrationTest extends TestCase {
    
    private $pdo;

    protected function setUp(): void {
        // Obtenir la connexion SQLite mockée de bootstrap.php
        $this->pdo = db();
        
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT, email TEXT, password TEXT, role TEXT, avatar TEXT, bio TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Nettoyer les tables pour avoir un environnement vierge
        $this->pdo->exec("DELETE FROM users");
        $this->pdo->exec("DELETE FROM watchlist");
    }

    public function testUserRegistrationAndWatchlistFlow() {
        // 1. Inscription d'un nouvel utilisateur
        $userData = [
            'nom' => 'Alice',
            'email' => 'alice@integration.test',
            'password' => 'secret123',
            'role' => 'user'
        ];
        
        $addResult = addUser($this->pdo, $userData);
        $this->assertTrue($addResult['success'], "L'inscription devrait réussir");
        $userId = $addResult['id'];

        // 2. Connexion de l'utilisateur
        $loginResult = loginUser($this->pdo, 'alice@integration.test', 'secret123');
        $this->assertTrue($loginResult['success'], "La connexion devrait réussir avec le bon mot de passe");
        $this->assertEquals('Alice', $loginResult['user']['nom']);

        // 3. Ajout d'un film à la watchlist
        $stmt = $this->pdo->prepare("INSERT INTO watchlist (user_id, tmdb_id, tmdb_title, tmdb_type, category_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, 603, 'The Matrix', 'movie', 'À regarder']);
        
        // 4. Vérification de la watchlist
        $check = $this->pdo->prepare("SELECT * FROM watchlist WHERE user_id = ?");
        $check->execute([$userId]);
        $watchlist = $check->fetchAll();
        
        $this->assertCount(1, $watchlist, "L'utilisateur devrait avoir 1 film dans sa watchlist");
        $this->assertEquals('The Matrix', $watchlist[0]['tmdb_title']);
        $this->assertEquals('movie', $watchlist[0]['tmdb_type']);
        
        // 5. Suppression de l'utilisateur (Cascade simulée)
        deleteUser($this->pdo, $userId);
        $deleted = getUserById($this->pdo, $userId);
        $this->assertFalse($deleted, "L'utilisateur devrait être supprimé");
    }
}
