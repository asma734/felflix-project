<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

require_once __DIR__ . '/../../model/MovieModel.php';

class MovieModelTest extends TestCase {
    private $pdo;
    private $model;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
        $this->model = new \MovieModel($this->pdo);
    }

    public function testGetAll() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'title' => 'Test Movie']]);
        $this->pdo->method('query')->willReturn($stmt);
        $result = $this->model->getAll();
        $this->assertCount(1, $result);
    }

    public function testFindById() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1]);
        $stmt->method('fetch')->willReturn(['id' => 1, 'title' => 'Test Movie']);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->findById(1);
        $this->assertEquals('Test Movie', $result['title']);
    }

    public function testSearch() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['%query%']);
        $stmt->method('fetchAll')->willReturn([]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->search('query');
        $this->assertIsArray($result);
    }

    public function testGetByGenre() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([5]);
        $stmt->method('fetchAll')->willReturn([['id' => 10, 'title' => 'Genre Movie']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getByGenre(5);
        $this->assertCount(1, $result);
    }

    public function testGetRamadanMovies() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');
        $stmt->method('fetchAll')->willReturn([['id' => 20, 'title' => 'Ramadan Movie']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getRamadanMovies();
        $this->assertCount(1, $result);
    }

    public function testGetAllGenres() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'name' => 'Comedy']]);
        $this->pdo->method('query')->willReturn($stmt);
        $result = $this->model->getAllGenres();
        $this->assertCount(1, $result);
    }

    public function testGetGenresByMovie() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([100]);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'name' => 'Drama']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getGenresByMovie(100);
        $this->assertCount(1, $result);
    }

    public function testAttachGenre() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([100, 2]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->model->attachGenre(100, 2);
        $this->assertTrue(true);
    }

    public function testDetachAllGenres() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([100]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->model->detachAllGenres(100);
        $this->assertTrue(true);
    }

    public function testSyncGenres() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->exactly(3))->method('execute'); // 1 for detach, 2 for attach
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->model->syncGenres(100, [1, 2]);
        $this->assertTrue(true);
    }

    public function testGetActorsByMovie() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([100]);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'name' => 'Actor']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getActorsByMovie(100);
        $this->assertCount(1, $result);
    }

    public function testGetDirectorsByMovie() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([100]);
        $stmt->method('fetchAll')->willReturn([['id' => 2, 'name' => 'Director']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getDirectorsByMovie(100);
        $this->assertCount(1, $result);
    }

    public function testGetMoviesByActor() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([50]);
        $stmt->method('fetchAll')->willReturn([['id' => 100, 'title' => 'Movie']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getMoviesByActor(50);
        $this->assertCount(1, $result);
    }

    public function testAttachActor() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([100, 50, 'actor', 'Role Name', 1]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->model->attachActor(100, 50, 'actor', 'Role Name', 1);
        $this->assertTrue(true);
    }

    public function testDetachAllActors() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([100]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->model->detachAllActors(100);
        $this->assertTrue(true);
    }

    public function testFindActorById() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([50]);
        $stmt->method('fetch')->willReturn(['id' => 50, 'name' => 'Actor']);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->findActorById(50);
        $this->assertEquals('Actor', $result['name']);
    }

    public function testCreateActor_New() {
        $stmtFind = $this->createMock(PDOStatement::class);
        $stmtFind->method('execute')->with(['New Actor']);
        $stmtFind->method('fetchColumn')->willReturn(false);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->with(['New Actor', 'url', 'bio', 1990]);

        $this->pdo->method('prepare')->willReturnCallback(function($sql) use ($stmtFind, $stmtInsert) {
            if (strpos($sql, 'SELECT id') !== false) return $stmtFind;
            return $stmtInsert;
        });
        
        $this->pdo->method('lastInsertId')->willReturn('99');

        $result = $this->model->createActor('New Actor', 'url', 'bio', 1990);
        $this->assertEquals(99, $result);
    }

    public function testCreateActor_Existing() {
        $stmtFind = $this->createMock(PDOStatement::class);
        $stmtFind->method('execute')->with(['Existing Actor']);
        $stmtFind->method('fetchColumn')->willReturn(50);

        $this->pdo->method('prepare')->willReturn($stmtFind);

        $result = $this->model->createActor('Existing Actor');
        $this->assertEquals(50, $result);
    }
}
