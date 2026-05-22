<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

require_once __DIR__ . '/../../model/GenreModel.php';

class GenreModelTest extends TestCase {
    private $pdo;
    private $model;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
        $this->model = new \GenreModel($this->pdo);
    }

    public function testGetAllGenres() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'name' => 'Action']]);
        $this->pdo->method('query')->willReturn($stmt);
        $result = $this->model->getAllGenres();
        $this->assertCount(1, $result);
        $this->assertEquals('Action', $result[0]['name']);
    }

    public function testGetAllCountries() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'name' => 'USA']]);
        $this->pdo->method('query')->willReturn($stmt);
        $result = $this->model->getAllCountries();
        $this->assertCount(1, $result);
    }

    public function testGetAllLanguages() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'name' => 'English']]);
        $this->pdo->method('query')->willReturn($stmt);
        $result = $this->model->getAllLanguages();
        $this->assertCount(1, $result);
    }

    public function testGetByTitle() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['tt123']);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'name' => 'Action']]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->getByTitle('tt123');
        $this->assertCount(1, $result);
    }

    public function testFindIdByName() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['Action']);
        $stmt->method('fetch')->willReturn(['id' => 1]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $result = $this->model->findIdByName('Action');
        $this->assertEquals(1, $result);
    }
}
