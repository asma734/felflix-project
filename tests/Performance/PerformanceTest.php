<?php
namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

require_once __DIR__ . '/../../model/RecommendationModel.php';
require_once __DIR__ . '/../../model/MovieModel.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controller/traitement.php';

class PerformanceTest extends TestCase {

    // ── BFS ALGORITHM ─────────────────────────────────────────────

    public function testBfsMaxDepthIsRespected() {
        $pdo = $this->createMock(PDO::class);
        
        $stmtNeighbors = $this->createMock(PDOStatement::class);
        $stmtNeighbors->method('fetchAll')->willReturn(['id1', 'id2', 'id3', 'id4', 'id5']);
        
        $stmtDetails = $this->createMock(PDOStatement::class);
        $stmtDetails->method('fetch')->willReturn(['imdb_id' => 'dummy', 'title' => 'Dummy']);
        
        $pdo->method('prepare')->willReturnCallback(function ($query) use ($stmtNeighbors, $stmtDetails) {
            if (strpos($query, 'SELECT DISTINCT') !== false) return $stmtNeighbors;
            return $stmtDetails;
        });
        
        $model = new \RecommendationModel($pdo);
        
        $startTime = microtime(true);
        $results = $model->getRecommendationsBFS('tt123', 100, 1);
        $elapsed = microtime(true) - $startTime;
        
        $this->assertLessThanOrEqual(10, count($results), 'BFS should stop at depth limit before reaching 100 results');
        $this->assertLessThan(0.5, $elapsed, 'BFS with shallow depth should be very fast');
    }

    public function testBfsDoesNotVisitSameNodeTwice() {
        $pdo = $this->createMock(PDO::class);
        $callCount = 0;
        
        // Every node connects back to the origin — creates a cycle
        $stmtNeighbors = $this->createMock(PDOStatement::class);
        $stmtNeighbors->method('fetchAll')->willReturnCallback(function() use (&$callCount) {
            $callCount++;
            return ['tt123', 'tt456']; // tt123 is origin — will loop forever if not handled
        });
        
        $stmtDetails = $this->createMock(PDOStatement::class);
        $stmtDetails->method('fetch')->willReturn(['imdb_id' => 'tt456', 'title' => 'Movie']);
        
        $pdo->method('prepare')->willReturnCallback(function ($q) use ($stmtNeighbors, $stmtDetails) {
            if (strpos($q, 'SELECT DISTINCT') !== false) return $stmtNeighbors;
            return $stmtDetails;
        });
        
        $model = new \RecommendationModel($pdo);
        
        $startTime = microtime(true);
        $results = $model->getRecommendationsBFS('tt123', 5, 2);
        $elapsed = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $elapsed, 'BFS with cycles should not loop forever');
        $this->assertCount(1, $results, 'Only distinct unseen nodes should be returned');
    }

    // ── BULK OPERATIONS ────────────────────────────────────────────

    public function testBulkUserSearchPerformance() {
        $pdo = db();
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, nom TEXT, email TEXT, password TEXT, role TEXT, avatar TEXT, bio TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("DELETE FROM users");
        
        // Insert 100 users
        for ($i = 1; $i <= 100; $i++) {
            $pdo->exec("INSERT INTO users (nom, email, password, role) VALUES ('User$i', 'user$i@test.com', 'hash', 'user')");
        }
        
        $start = microtime(true);
        $results = searchUsers($pdo, 'User');
        $elapsed = microtime(true) - $start;
        
        $this->assertCount(100, $results, '100 users should be returned');
        $this->assertLessThan(0.5, $elapsed, 'Searching 100 users should complete in under 500ms');
    }

    public function testBulkMovieFilterPerformance() {
        $pdo = db();
        $pdo->exec("CREATE TABLE IF NOT EXISTS movies (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, description TEXT, year INTEGER, type TEXT, country_code TEXT, rating REAL)");
        $pdo->exec("DELETE FROM movies");
        
        for ($i = 1; $i <= 200; $i++) {
            $pdo->exec("INSERT INTO movies (title, type, country_code, rating, year) VALUES ('Movie$i', 'movie', 'TN', 7.$i, 2020)");
        }
        
        $start = microtime(true);
        $results = getMoviesFiltered($pdo, ['type' => 'movie', 'country' => 'TN']);
        $elapsed = microtime(true) - $start;
        
        $this->assertCount(200, $results, '200 movies should be returned');
        $this->assertLessThan(0.5, $elapsed, 'Filtering 200 movies should complete in under 500ms');
    }

    public function testTmdbSummaryGenerationSpeed() {
        require_once __DIR__ . '/../../controller/tmdb.php';
        
        $movie = [
            'title' => 'Inception', 'release_date' => '2010-07-15', 'vote_average' => 8.8,
            'overview' => str_repeat('a', 500), // Long overview
            'credits' => [
                'crew' => array_fill(0, 20, ['name' => 'Director', 'job' => 'Director']),
                'cast' => array_fill(0, 20, ['name' => 'Actor'])
            ],
            'videos' => ['results' => [['type' => 'Trailer', 'site' => 'YouTube', 'key' => 'abc123']]]
        ];
        
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            tmdbSummary($movie);
        }
        $elapsed = microtime(true) - $start;
        
        $this->assertLessThan(1.0, $elapsed, '1000 summary generations should complete in under 1 second');
    }
}
