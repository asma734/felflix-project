<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

require_once __DIR__ . '/../../model/PersonModel.php';

class PersonModelTest extends TestCase {
    private $pdo;
    private $model;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
        $this->model = new \PersonModel($this->pdo);
    }

    public function testFindById() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([10]);
        $stmt->method('fetch')->willReturn(['id' => 10, 'name' => 'Will Smith']);
        
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $result = $this->model->findById(10);
        $this->assertEquals('Will Smith', $result['name']);
    }

    public function testGetByTitleAndRole() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['tt123', 'actor']);
        $stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'Actor 1'],
            ['id' => 2, 'name' => 'Actor 2']
        ]);
        
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $result = $this->model->getByTitleAndRole('tt123', 'actor');
        $this->assertCount(2, $result);
        $this->assertEquals('Actor 1', $result[0]['name']);
    }

    public function testGetActorsByTitle() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['tt999', 'actor']);
        $stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'Actor A']
        ]);
        
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $result = $this->model->getActorsByTitle('tt999');
        $this->assertCount(1, $result);
        $this->assertEquals('Actor A', $result[0]['name']);
    }
}
