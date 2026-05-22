<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

require_once __DIR__ . '/../../model/RecommendationModel.php';

class RecommendationModelTest extends TestCase {
    private $pdo;
    private $model;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
        $this->model = new \RecommendationModel($this->pdo);
    }

    public function testGetRecommendationsBFS_Empty() {
        // Return no neighbors
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);
        
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $results = $this->model->getRecommendationsBFS('tt123', 5, 2);
        $this->assertEmpty($results);
    }

    public function testGetRecommendationsBFS_WithNeighbors() {
        $stmtNeighbors = $this->createMock(PDOStatement::class);
        $stmtNeighbors->method('fetchAll')->willReturn(['tt456']);
        
        $stmtDetails = $this->createMock(PDOStatement::class);
        $stmtDetails->method('fetch')->willReturn(['imdb_id' => 'tt456', 'title' => 'Recommended Movie']);
        
        $this->pdo->method('prepare')->willReturnCallback(function ($query) use ($stmtNeighbors, $stmtDetails) {
            if (strpos($query, 'SELECT DISTINCT tg2.imdb_id') !== false || strpos($query, 'SELECT DISTINCT tp2.imdb_id') !== false) {
                return $stmtNeighbors;
            }
            if (strpos($query, 'SELECT * FROM titles WHERE imdb_id = ?') !== false) {
                return $stmtDetails;
            }
            return $this->createMock(PDOStatement::class);
        });
        
        $results = $this->model->getRecommendationsBFS('tt123', 1, 2);
        
        $this->assertCount(1, $results);
        $this->assertEquals('tt456', $results[0]['imdb_id']);
        $this->assertEquals('Recommended Movie', $results[0]['title']);
    }

    public function testGetRecommendationsBFS_WithActorNeighbors() {
        $stmtGenreNeighbors = $this->createMock(PDOStatement::class);
        $stmtGenreNeighbors->method('fetchAll')->willReturn([]); // No genre neighbors

        $stmtActorNeighbors = $this->createMock(PDOStatement::class);
        $stmtActorNeighbors->method('fetchAll')->willReturn(['tt789']); // Has actor neighbor

        $stmtDetails = $this->createMock(PDOStatement::class);
        $stmtDetails->method('fetch')->willReturn(['imdb_id' => 'tt789', 'title' => 'Actor Movie']);
        
        $this->pdo->method('prepare')->willReturnCallback(function ($query) use ($stmtGenreNeighbors, $stmtActorNeighbors, $stmtDetails) {
            if (strpos($query, 'SELECT DISTINCT tg2.imdb_id') !== false) {
                return $stmtGenreNeighbors;
            }
            if (strpos($query, 'SELECT DISTINCT tp2.imdb_id') !== false) {
                return $stmtActorNeighbors;
            }
            if (strpos($query, 'SELECT * FROM titles WHERE imdb_id = ?') !== false) {
                return $stmtDetails;
            }
            return $this->createMock(PDOStatement::class);
        });
        
        $results = $this->model->getRecommendationsBFS('tt123', 1, 2);
        
        $this->assertCount(1, $results);
        $this->assertEquals('tt789', $results[0]['imdb_id']);
    }
}
