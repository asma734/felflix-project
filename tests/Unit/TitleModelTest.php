<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

require_once __DIR__ . '/../../model/TitleModel.php';

class TitleModelTest extends TestCase {
    private $pdo;
    private $model;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
        $this->model = new \TitleModel($this->pdo);
    }

    public function testGetFeatured() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['imdb_id' => 'tt123', 'title' => 'Featured']]);
        $this->pdo->method('query')->willReturn($stmt);
        $result = $this->model->getFeatured();
        $this->assertEquals('Featured', $result['title']);
    }

    public function testGetTopMovies() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['imdb_id' => 'tt123', 'title' => 'Top Movie']]);
        $this->pdo->method('query')->willReturn($stmt);
        $result = $this->model->getTopMovies(10);
        $this->assertCount(1, $result);
    }

    public function testGetTopSeries() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['imdb_id' => 'tt456', 'title' => 'Top Series']]);
        $this->pdo->method('query')->willReturn($stmt);
        $result = $this->model->getTopSeries(10);
        $this->assertCount(1, $result);
    }

    public function testGetByGenreId() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5]);
        $stmt->method('fetchAll')->willReturn([['imdb_id' => 'tt789']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getByGenreId(5);
        $this->assertCount(1, $result);
    }

    public function testFindById() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['tt123']);
        $stmt->method('fetch')->willReturn(['imdb_id' => 'tt123', 'title' => 'Found Movie']);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->findById('tt123');
        $this->assertEquals('Found Movie', $result['title']);
    }

    public function testGetRelated() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5, 'tt123']);
        $stmt->method('fetchAll')->willReturn([['imdb_id' => 'tt456']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getRelated(5, 'tt123');
        $this->assertCount(1, $result);
    }

    public function testCountFiltered() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['%matrix%', 'movie']);
        $stmt->method('fetchColumn')->willReturn(5);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->countFiltered(['q' => 'matrix', 'type' => 'movie']);
        $this->assertEquals(5, $result[0]);
    }

    public function testGetFiltered() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['movie']);
        $stmt->method('fetchAll')->willReturn([['imdb_id' => 'tt123']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getFiltered('WHERE type=?', ['movie'], 'imdb_rating DESC', 10, 0);
        $this->assertCount(1, $result);
    }
}
