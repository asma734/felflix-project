<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controller/traitement.php';
require_once __DIR__ . '/../../model/MovieModel.php';
require_once __DIR__ . '/../../model/GenreModel.php';
require_once __DIR__ . '/../../model/RecommendationModel.php';

class MovieManagementIntegrationTest extends TestCase {
    
    private $pdo;
    private $movieModel;

    protected function setUp(): void {
        $this->pdo = db();
        $this->movieModel = new \MovieModel($this->pdo);
        
        // Créer les tables nécessaires pour le test
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS movies (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT, description TEXT, year INTEGER, type TEXT, country_code TEXT, rating REAL
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS genres (
            id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS movie_genres (
            movie_id INTEGER, genre_id INTEGER
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS actors (
            id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, photo_url TEXT
        )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS movie_actors (
            movie_id INTEGER, actor_id INTEGER, role TEXT, character_name TEXT, billing_order INTEGER
        )");

        // Nettoyer
        $this->pdo->exec("DELETE FROM movies");
        $this->pdo->exec("DELETE FROM genres");
        $this->pdo->exec("DELETE FROM movie_genres");
        $this->pdo->exec("DELETE FROM actors");
        $this->pdo->exec("DELETE FROM movie_actors");
    }

    public function testMovieCreationAndFilteringFlow() {
        // 1. Ajouter un film
        $this->pdo->exec("INSERT INTO movies (title, description, year, type, country_code, rating) 
                          VALUES ('Dachra', 'Horreur Tunisien', 2018, 'movie', 'TN', 8.5)");
        $movieId = $this->pdo->lastInsertId();

        // 2. Ajouter un genre
        $this->pdo->exec("INSERT INTO genres (name) VALUES ('Horreur')");
        $genreId = $this->pdo->lastInsertId();

        // 3. Ajouter un acteur
        $this->pdo->exec("INSERT INTO actors (name) VALUES ('Yassine Ghouiza')");

        // 5. Vérifier que la recherche par filtre fonctionne (Controller)
        $filtered = getMoviesFiltered($this->pdo, [
            'type' => 'movie',
            'country' => 'TN',
            'q' => 'Dachra'
        ]);

        $this->assertCount(1, $filtered, "Le film devrait être trouvé par les filtres");
        $this->assertEquals('Dachra', $filtered[0]['title']);

        // 7. Test de la recherche globale du MovieModel
        $searchResults = $this->movieModel->search('Dachra');
        $this->assertCount(1, $searchResults, "La recherche devrait correspondre au titre");
    }
}
