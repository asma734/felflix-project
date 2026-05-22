<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/MovieModel.php';
require_once __DIR__ . '/../../model/TitleModel.php';
require_once __DIR__ . '/../../model/GenreModel.php';
require_once __DIR__ . '/../../model/RecommendationModel.php';
require_once __DIR__ . '/../../controller/traitement.php';
require_once __DIR__ . '/../../controller/tmdb.php';

/**
 * Tests de robustesse — Valeurs limites, cas extrêmes, données nulles/vides/invalides
 */
class RobustnessTest extends TestCase {

    private PDO $pdo;
    private PDOStatement $emptyStmt;

    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
        $this->emptyStmt = $this->createMock(PDOStatement::class);
        $this->emptyStmt->method('execute')->willReturn(true);
        $this->emptyStmt->method('fetchAll')->willReturn([]);
        $this->emptyStmt->method('fetch')->willReturn(false);
        $this->emptyStmt->method('fetchColumn')->willReturn(0);
        $this->pdo->method('prepare')->willReturn($this->emptyStmt);
        $this->pdo->method('query')->willReturn($this->emptyStmt);
    }

    // ── ENTRÉES VIDES ─────────────────────────────────────────────

    public function testSearchWithEmptyString() {
        $model = new \MovieModel($this->pdo);
        $result = $model->search('');
        $this->assertIsArray($result);
    }

    public function testSearchWithWhitespaceOnly() {
        $model = new \MovieModel($this->pdo);
        $result = $model->search('   ');
        $this->assertIsArray($result);
    }

    public function testGetMoviesFilteredWithEmptyFilters() {
        $result = getMoviesFiltered($this->pdo, []);
        $this->assertIsArray($result);
    }

    public function testAddUserWithMissingFields() {
        // Missing email → should either fail gracefully or PHP uses null
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(false);
        $stmt->method('fetch')->willReturn(false);
        $this->pdo->method('prepare')->willReturn($stmt);

        $result = addUser($this->pdo, ['nom' => 'Alice']);
        // Should return false, not crash
        $this->assertArrayHasKey('success', $result);
    }

    public function testLoginWithEmptyEmail() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(false);
        $this->pdo->method('prepare')->willReturn($stmt);

        $result = loginUser($this->pdo, '', 'password');
        $this->assertFalse($result['success']);
    }

    public function testLoginWithEmptyPassword() {
        $hash = password_hash('realpassword', PASSWORD_BCRYPT);
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(['id' => 1, 'email' => 'u@test.com', 'password' => $hash]);
        $this->pdo->method('prepare')->willReturn($stmt);

        $result = loginUser($this->pdo, 'u@test.com', '');
        $this->assertFalse($result['success']);
    }

    // ── VALEURS LIMITES ───────────────────────────────────────────

    public function testGetAllMoviesReturnsEmptyWhenNone() {
        $model = new \MovieModel($this->pdo);
        $result = $model->getAll();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetByGenreWithInvalidId() {
        $model = new \MovieModel($this->pdo);
        $result = $model->getByGenre(-1);
        $this->assertIsArray($result);
    }

    public function testCountUsersReturnsZeroWhenEmpty() {
        $this->assertEquals(0, countUsers($this->pdo));
    }

    public function testCountMoviesReturnsZeroWhenEmpty() {
        $this->assertEquals(0, countMovies($this->pdo));
    }

    public function testSyncMovieGenresWithEmptyList() {
        // With empty genre list → only the DELETE prepare() should be called; INSERT prepare is still called but never executed
        $localPdo  = $this->createMock(PDO::class);
        $stmtDel   = $this->createMock(PDOStatement::class);
        $stmtIns   = $this->createMock(PDOStatement::class);

        $stmtDel->expects($this->once())->method('execute'); // DELETE fires once
        $stmtIns->expects($this->never())->method('execute');  // INSERT never fires (empty array)

        $localPdo->method('prepare')->willReturnCallback(function ($sql) use ($stmtDel, $stmtIns) {
            return (str_contains($sql, 'DELETE')) ? $stmtDel : $stmtIns;
        });

        syncMovieGenres($localPdo, 42, []);
        $this->assertTrue(true);
    }

    public function testCreateActorWithLongName() {
        $longName = str_repeat('A', 1000);
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetchColumn')->willReturn(false);
        $this->pdo->method('prepare')->willReturn($stmt);
        $this->pdo->method('lastInsertId')->willReturn('1');

        $id = createActorIfNotExists($this->pdo, $longName);
        $this->assertEquals(1, $id);
    }

    // ── DONNÉES NULLES ────────────────────────────────────────────

    public function testGetUserByIdNotFound() {
        $result = getUserById($this->pdo, 99999);
        $this->assertFalse($result);
    }

    public function testGetActorsByMovieWithNoActors() {
        $model = new \MovieModel($this->pdo);
        $result = $model->getActorsByMovie(999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetGenresByMovieWithNoGenres() {
        $model = new \MovieModel($this->pdo);
        $result = $model->getGenresByMovie(999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetTunisianMoviesReturnsEmptyArray() {
        $result = getTunisianMovies($this->pdo);
        $this->assertIsArray($result);
    }

    // ── TMDB SUMMARY — CAS LIMITES ────────────────────────────────

    public function testTmdbSummaryWithMinimalData() {
        $movie = ['title' => 'Test'];
        $summary = tmdbSummary($movie);
        $this->assertIsString($summary);
        $this->assertStringContainsString('Test', $summary);
    }

    public function testTmdbSummaryWithEmptyOverview() {
        $movie = ['title' => 'Film', 'overview' => '', 'vote_average' => 0];
        $summary = tmdbSummary($movie);
        $this->assertIsString($summary);
    }

    public function testTmdbSummaryWithNullFields() {
        $movie = [
            'title' => 'Film',
            'release_date' => null,
            'vote_average' => null,
            'overview' => null,
            'credits' => [],
            'videos' => []
        ];
        $summary = tmdbSummary($movie);
        $this->assertIsString($summary);
    }

    public function testTmdbSummaryWithNoTrailer() {
        $movie = [
            'title' => 'Film',
            'videos' => ['results' => [['type' => 'Clip', 'site' => 'YouTube', 'key' => 'abc']]]
        ];
        $summary = tmdbSummary($movie);
        $this->assertStringNotContainsString('youtu.be', $summary);
    }

    public function testTmdbSummaryWithManyActors() {
        $movie = [
            'title' => 'Film',
            'credits' => [
                'cast' => array_map(fn($i) => ['name' => "Actor $i"], range(1, 50))
            ]
        ];
        $summary = tmdbSummary($movie);
        // Only first 5 actors should be listed
        $this->assertStringContainsString('Actor 1', $summary);
        $this->assertStringNotContainsString('Actor 6', $summary);
    }

    // ── BFS — CAS EXTRÊMES ────────────────────────────────────────

    public function testBfsWithZeroLimit() {
        $model = new \RecommendationModel($this->pdo);
        $result = $model->getRecommendationsBFS('tt123', 0, 2);
        $this->assertEmpty($result);
    }

    public function testBfsWithNonExistentStart() {
        $model = new \RecommendationModel($this->pdo);
        $result = $model->getRecommendationsBFS('', 5, 2);
        $this->assertIsArray($result);
    }

    // ── XSS — CHAÎNES EXTRÊMES ────────────────────────────────────

    public function testHWithVeryLongString() {
        $long = str_repeat('<script>x</script>', 1000);
        $safe = h($long);
        $this->assertStringNotContainsString('<script>', $safe);
    }

    public function testHWithUnicodeCharacters() {
        $unicode = 'مرحبا بالعالم 🌶 Bonjour';
        $safe = h($unicode);
        $this->assertEquals($unicode, $safe); // No HTML chars to escape
    }

    public function testHWithEmptyString() {
        $this->assertEquals('', h(''));
    }

    public function testHWithNumericString() {
        $this->assertEquals('42', h('42'));
    }

    // ── FILTRES AVANCÉS — COMBINAISONS EXTRÊMES ──────────────────

    public function testGetMoviesFilteredAllFilters() {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetchAll')->willReturn([]);
        $this->pdo->method('prepare')->willReturn($stmt);

        $result = getMoviesFiltered($this->pdo, [
            'type'       => 'movie',
            'country'    => 'TN',
            'season'     => 'summer',
            'age'        => '18+',
            'year_from'  => '2000',
            'year_to'    => '2024',
            'genre_id'   => '5',
            'q'          => 'Test',
            'tunisian_only' => true,
        ]);
        $this->assertIsArray($result);
    }

    public function testUpdateUserWithoutAvatarAndBio() {
        // Fresh mock — setUp already configured $this->pdo so we use a local one
        $localPdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['Alice', 'a@a.com', 1]);
        $localPdo->expects($this->once())->method('prepare')->willReturn($stmt);
        updateUser($localPdo, 1, 'Alice', 'a@a.com'); // avatar=null, bio=null → short branch
        $this->assertTrue(true);
    }
}
