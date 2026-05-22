<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/TitleModel.php';

class SearchIndexingIntegrationTest extends TestCase {
    
    private $pdo;
    private $titleModel;

    protected function setUp(): void {
        $this->pdo = db();
        $this->titleModel = new \TitleModel($this->pdo);
        
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS titles (
            imdb_id TEXT PRIMARY KEY,
            title TEXT, original_title TEXT, description TEXT, year INTEGER, type TEXT, imdb_rating REAL,
            content_rating TEXT, poster_url TEXT, imdb_votes INTEGER
        )");

        $this->pdo->exec("DELETE FROM titles");
    }

    public function testSearchIndexingAndFiltering() {
        // Insérer un lot de données pour l'indexation
        $this->pdo->exec("INSERT INTO titles (imdb_id, title, original_title, description, year, type, imdb_rating, content_rating, poster_url, imdb_votes) 
                          VALUES ('tt01', 'Matrix', 'The Matrix', 'Hackers fighting machines', 1999, 'movie', 8.7, 'R', 'matrix.jpg', 10000)");
        
        $this->pdo->exec("INSERT INTO titles (imdb_id, title, original_title, description, year, type, imdb_rating, content_rating, poster_url, imdb_votes) 
                          VALUES ('tt02', 'Matrix Reloaded', 'The Matrix Reloaded', 'More hackers', 2003, 'movie', 7.2, 'R', 'reloaded.jpg', 5000)");
                          
        $this->pdo->exec("INSERT INTO titles (imdb_id, title, original_title, description, year, type, imdb_rating, content_rating, poster_url, imdb_votes) 
                          VALUES ('tt03', 'Breaking Bad', 'Breaking Bad', 'Chemistry teacher', 2008, 'tv', 9.5, 'TV-MA', 'bb.jpg', 20000)");

        // Test de l'indexation de recherche (Recherche par mot clé dans le modèle TitleModel)
        $filters = ['q' => 'Matrix', 'type' => 'movie'];
        
        // Vérification de la fonction de comptage filtré
        $countResult = $this->titleModel->countFiltered($filters);
        $this->assertEquals(2, $countResult[0], "L'indexation de recherche doit trouver 2 films Matrix");
        
        // Vérification de la récupération filtrée (Pagination et tri)
        $sqlWhere = $countResult[1];
        $params = $countResult[2];
        $results = $this->titleModel->getFiltered($sqlWhere, $params, 'imdb_rating DESC', 10, 0);
        
        $this->assertCount(2, $results);
        // Doit être trié par rating décroissant : Matrix (8.7) en premier
        $this->assertEquals('Matrix', $results[0]['title']);
        $this->assertEquals('Matrix Reloaded', $results[1]['title']);
    }

    public function testTfidfBooleanSearch() {
        // Insérer les documents du corpus Python de l'utilisateur pour valider son exactitude
        $this->pdo->exec("INSERT INTO titles (imdb_id, title, description, type, imdb_rating) 
                          VALUES ('doc1', 'Recherche d information', 'La recherche d information est un domaine fascinant. Elle est souvent abordée en utilisant Python recherche algorithmes.', 'movie', 8.0)");
        $this->pdo->exec("INSERT INTO titles (imdb_id, title, description, type, imdb_rating) 
                          VALUES ('doc2', 'Python et programmation', 'Python est un langage de programmation très populaire en data science, et il sert à construire des systèmes d indexation algorithmes.', 'movie', 8.5)");
        $this->pdo->exec("INSERT INTO titles (imdb_id, title, description, type, imdb_rating) 
                          VALUES ('doc3', 'Indexation et algorithmes', 'Les systèmes d indexation reposent sur des algorithmes sophistiqués. Ce domaine est la base du web 3.0.', 'movie', 9.0)");
        
        // Test 1 : Modèle Booléen OR (Fuzzy MAX des scores TF-IDF)
        $filtersOr = ['q' => 'Python or index', 'type' => 'movie'];
        $countOr = $this->titleModel->countFiltered($filtersOr);
        $this->assertGreaterThan(0, $countOr[0]);

        $resultsOr = $this->titleModel->getFiltered($countOr[1], $countOr[2], 'imdb_rating DESC', 10, 0);
        $foundIds = array_column($resultsOr, 'imdb_id');
        // 'doc2' a à la fois 'python' et 'index', il doit ressortir premier ou parmi les résultats
        $this->assertContains('doc2', $foundIds);

        // Test 2 : Modèle Booléen AND (Fuzzy MIN des scores TF-IDF)
        $filtersAnd = ['q' => 'Python and index', 'type' => 'movie'];
        $countAnd = $this->titleModel->countFiltered($filtersAnd);
        
        $resultsAnd = $this->titleModel->getFiltered($countAnd[1], $countAnd[2], 'imdb_rating DESC', 10, 0);
        $foundAndIds = array_column($resultsAnd, 'imdb_id');
        // Seul doc2 contient à la fois 'python' et 'index'
        $this->assertContains('doc2', $foundAndIds);
    }
}
