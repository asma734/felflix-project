<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

require_once __DIR__ . '/../../controller/traitement.php';
require_once __DIR__ . '/../../model/User.php';

class TraitementTest extends TestCase {
    private $pdo;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
    }

    public function testAddUser_EmailExists() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['test@example.com']);
        $stmt->method('fetch')->willReturn(['id' => 1]);
        
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $result = addUser($this->pdo, ['email' => 'test@example.com']);
        $this->assertFalse($result['success']);
        $this->assertEquals('Cet email est déjà utilisé!', $result['message']);
    }

    public function testAddUser_Success() {
        $stmtFind = $this->createMock(PDOStatement::class);
        $stmtFind->method('execute');
        $stmtFind->method('fetch')->willReturn(false);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $this->pdo->method('prepare')->willReturnCallback(function($sql) use ($stmtFind, $stmtInsert) {
            if (strpos($sql, 'SELECT id') !== false) return $stmtFind;
            return $stmtInsert;
        });
        
        $this->pdo->method('lastInsertId')->willReturn('99');
        
        $result = addUser($this->pdo, [
            'nom' => 'Test',
            'email' => 'new@example.com',
            'password' => 'secret',
            'role' => 'user'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(99, $result['id']);
    }

    public function testLoginUser_NotFound() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(false);
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $result = loginUser($this->pdo, 'wrong@example.com', 'pass');
        $this->assertFalse($result['success']);
    }

    public function testGetAllUsers() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([
            ['id' => 1, 'nom' => 'A', 'email' => 'a@a.com', 'password' => '...', 'role' => 'user', 'avatar' => '1', 'bio' => '']
        ]);
        $this->pdo->method('query')->willReturn($stmt);
        
        $users = getAllUsers($this->pdo);
        $this->assertCount(1, $users);
        $this->assertInstanceOf(\User::class, $users[0]);
    }

    public function testSearchUsers() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['%John%', '%John%']);
        $stmt->method('fetchAll')->willReturn([]);
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $users = searchUsers($this->pdo, 'John');
        $this->assertIsArray($users);
    }

    public function testGetUserById() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->with([1]);
        $stmt->method('fetch')->willReturn(['id' => 1, 'nom' => 'John']);
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $user = getUserById($this->pdo, 1);
        $this->assertEquals('John', $user['nom']);
    }

    public function testGetMoviesFiltered() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['movie', 'TN']);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'title' => 'TN Movie']]);
        
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $result = getMoviesFiltered($this->pdo, [
            'type' => 'movie',
            'country' => 'TN'
        ]);
        
        $this->assertCount(1, $result);
    }

    public function testUpdateUser() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['Updated', 'u@example.com', 'avatar.jpg', 'my bio', 1]);
        $this->pdo->method('prepare')->willReturn($stmt);
        updateUser($this->pdo, 1, 'Updated', 'u@example.com', 'avatar.jpg', 'my bio');
        $this->assertTrue(true);
    }

    public function testDeleteUser() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1]);
        $this->pdo->method('prepare')->willReturn($stmt);
        deleteUser($this->pdo, 1);
        $this->assertTrue(true);
    }

    public function testCountUsers() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(42);
        $this->pdo->method('query')->willReturn($stmt);
        $this->assertEquals(42, countUsers($this->pdo));
    }

    public function testCountMovies() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(10);
        $this->pdo->method('query')->willReturn($stmt);
        $this->assertEquals(10, countMovies($this->pdo));
    }

    public function testGetTunisianMovies() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'title' => 'TN']]);
        $this->pdo->method('query')->willReturn($stmt);
        $this->assertCount(1, getTunisianMovies($this->pdo));
    }

    public function testGetTunisianByType() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['series']);
        $stmt->method('fetchAll')->willReturn([['id' => 1]]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->assertCount(1, getTunisianByType($this->pdo, 'series'));
    }

    public function testGetAllGenres() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([['id' => 1, 'name' => 'Comedy']]);
        $this->pdo->method('query')->willReturn($stmt);
        $this->assertCount(1, getAllGenres($this->pdo));
    }

    public function testGetGenresByMovie() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1]);
        $stmt->method('fetchAll')->willReturn([['id' => 2]]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->assertCount(1, getGenresByMovie($this->pdo, 1));
    }

    public function testSyncMovieGenres() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->exactly(3))->method('execute'); // 1 delete, 2 inserts
        $this->pdo->method('prepare')->willReturn($stmt);
        syncMovieGenres($this->pdo, 1, [2, 3]);
        $this->assertTrue(true);
    }

    public function testGetActorsByMovie() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1]);
        $stmt->method('fetchAll')->willReturn([['id' => 1]]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->assertCount(1, getActorsByMovie($this->pdo, 1));
    }

    public function testGetDirectorsByMovie() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1]);
        $stmt->method('fetchAll')->willReturn([['id' => 1]]);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->assertCount(1, getDirectorsByMovie($this->pdo, 1));
    }

    public function testCreateActorIfNotExists() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->with(['Will Smith']);
        $stmt->method('fetchColumn')->willReturn(5); // Existing
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->assertEquals(5, createActorIfNotExists($this->pdo, 'Will Smith'));
    }

    public function testAttachActorToMovie() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([1, 2, 'actor', 'Hero', 1]);
        $this->pdo->method('prepare')->willReturn($stmt);
        attachActorToMovie($this->pdo, 1, 2, 'actor', 'Hero', 1);
        $this->assertTrue(true);
    }

    public function testCountPostsException() {
        $this->pdo->method('query')->willThrowException(new \PDOException());
        $this->assertEquals(0, countPosts($this->pdo));
    }

    public function testCountMoviesException() {
        $this->pdo->method('query')->willThrowException(new \PDOException());
        $this->assertEquals(0, countMovies($this->pdo));
    }

    public function testGetTunisianMoviesException() {
        $this->pdo->method('query')->willThrowException(new \PDOException());
        $this->assertEquals([], getTunisianMovies($this->pdo));
    }

    public function testGetTunisianByTypeException() {
        $this->pdo->method('prepare')->willThrowException(new \PDOException());
        $this->assertEquals([], getTunisianByType($this->pdo));
    }

    public function testGetAllGenresException() {
        $this->pdo->method('query')->willThrowException(new \PDOException());
        $this->assertEquals([], getAllGenres($this->pdo));
    }

    public function testGetGenresByMovieException() {
        $this->pdo->method('prepare')->willThrowException(new \PDOException());
        $this->assertEquals([], getGenresByMovie($this->pdo, 1));
    }

    public function testGetActorsByMovieException() {
        $this->pdo->method('prepare')->willThrowException(new \PDOException());
        $this->assertEquals([], getActorsByMovie($this->pdo, 1));
    }

    public function testGetDirectorsByMovieException() {
        $this->pdo->method('prepare')->willThrowException(new \PDOException());
        $this->assertEquals([], getDirectorsByMovie($this->pdo, 1));
    }
}
