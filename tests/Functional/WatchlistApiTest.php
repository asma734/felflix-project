<?php
namespace Tests\Functional;

use PHPUnit\Framework\TestCase;

class WatchlistApiTest extends TestCase {
    
    protected function setUp(): void {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        
        $pdo = db();
        $pdo->exec("CREATE TABLE IF NOT EXISTS watchlist (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, tmdb_id INTEGER, tmdb_title TEXT, tmdb_poster TEXT, tmdb_type TEXT, category_name TEXT, added_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS watch_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, tmdb_id INTEGER, tmdb_type TEXT, tmdb_title TEXT, mood_id INTEGER, added_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS moods (
            id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, tone TEXT
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS emotional_profiles (
            user_id INTEGER PRIMARY KEY, romantic_pct REAL, nostalgic_pct REAL, dark_pct REAL, action_pct REAL, happy_pct REAL, sad_pct REAL, anxious_pct REAL, diversity_score REAL, balance_score REAL, dominant_mood TEXT
        )");
        $pdo->exec("DELETE FROM watchlist");
        $pdo->exec("DELETE FROM watch_history");
        $pdo->exec("DELETE FROM moods");
        $pdo->exec("DELETE FROM emotional_profiles");
    }

    public function testNotLoggedIn() {
        $_SESSION = [];
        ob_start();
        require __DIR__ . '/../../controller/watchlist_api.php';
        $output = ob_get_clean();
        $json = json_decode($output, true);
        $this->assertEquals('Non connecté', $json['message']);
    }

    private function runApiAction($action, $data = []) {
        $_SESSION['user'] = ['id' => 1, 'role' => 'user'];
        $_POST = [];
        $_POST['action'] = $action;
        foreach ($data as $k => $v) $_POST[$k] = $v;
        
        global $cnx;
        $cnx = db();

        ob_start();
        try {
            require __DIR__ . '/../../controller/watchlist_api.php';
            $output = ob_get_clean();
            return json_decode($output, true);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public function testAddMissingId() {
        $res = $this->runApiAction('add');
        $this->assertFalse($res['success']);
    }

    public function testAddSuccess() {
        $res = $this->runApiAction('add', [
            'tmdb_id' => 123,
            'tmdb_type' => 'movie',
            'tmdb_title' => 'Test Movie',
            'category' => 'My List'
        ]);
        $this->assertTrue($res['success'] ?? false);
    }

    public function testAddAlreadyInList() {
        $pdo = db();
        $pdo->exec("INSERT INTO watchlist (user_id, tmdb_id, tmdb_type, category_name) VALUES (1, 123, 'movie', 'My List')");
        
        $res = $this->runApiAction('add', [
            'tmdb_id' => 123,
            'tmdb_type' => 'movie'
        ]);
        $this->assertFalse($res['success'] ?? true);
        $this->assertTrue($res['already'] ?? false);
    }

    public function testCheckInList() {
        $pdo = db();
        $pdo->exec("INSERT INTO watchlist (user_id, tmdb_id, tmdb_type, category_name) VALUES (1, 123, 'movie', 'My List')");
        
        $res = $this->runApiAction('check', [
            'tmdb_id' => 123,
            'tmdb_type' => 'movie'
        ]);
        $this->assertTrue($res['in_list'] ?? false);
    }

    public function testGetWatchlist() {
        $pdo = db();
        $pdo->exec("INSERT INTO watchlist (user_id, tmdb_id, tmdb_type, category_name) VALUES (1, 123, 'movie', 'My List')");
        
        $res = $this->runApiAction('get');
        $this->assertTrue($res['success'] ?? false);
        $this->assertEquals(1, $res['total'] ?? 0);
    }

    public function testMoveCategory() {
        $pdo = db();
        $pdo->exec("INSERT INTO watchlist (user_id, tmdb_id, tmdb_type, category_name) VALUES (1, 123, 'movie', 'My List')");
        $wl_id = $pdo->query("SELECT id FROM watchlist LIMIT 1")->fetchColumn();
        
        $res = $this->runApiAction('move', [
            'wl_id' => $wl_id,
            'category' => 'Favorites'
        ]);
        $this->assertTrue($res['success'] ?? false);
    }

    public function testRemoveFromWatchlist() {
        $pdo = db();
        $pdo->exec("INSERT INTO watchlist (user_id, tmdb_id, tmdb_type, category_name) VALUES (1, 123, 'movie', 'My List')");
        $wl_id = $pdo->query("SELECT id FROM watchlist LIMIT 1")->fetchColumn();
        
        $res = $this->runApiAction('remove', [
            'wl_id' => $wl_id
        ]);
        $this->assertTrue($res['success'] ?? false);
        
        $resCheck = $this->runApiAction('get');
        $this->assertEquals(0, $resCheck['total'] ?? -1);
    }

    public function testRemoveFromWatchlistByTmdbId() {
        $pdo = db();
        $pdo->exec("INSERT INTO watchlist (user_id, tmdb_id, tmdb_type, category_name) VALUES (1, 456, 'movie', 'My List')");
        
        $res = $this->runApiAction('remove', [
            'tmdb_id' => 456,
            'tmdb_type' => 'movie'
        ]);
        $this->assertTrue($res['success'] ?? false);
        
        $resCheck = $this->runApiAction('get');
        $this->assertEquals(0, $resCheck['total'] ?? -1);
    }

    public function testAddWithMoodId() {
        $pdo = db();
        // Insert mood romansi
        $pdo->exec("INSERT INTO moods (id, name, tone) VALUES (1, 'romansi', 'romantic')");

        $res = $this->runApiAction('add', [
            'tmdb_id' => 999,
            'tmdb_type' => 'movie',
            'tmdb_title' => 'Romantic Movie',
            'mood_id' => 1
        ]);
        $this->assertTrue($res['success'] ?? false);

        // Verify watch_history contains the entry
        $hist = $pdo->query("SELECT COUNT(*) FROM watch_history WHERE tmdb_id = 999")->fetchColumn();
        $this->assertEquals(1, $hist);

        // Verify emotional profile got generated
        $profile = $pdo->query("SELECT COUNT(*) FROM emotional_profiles WHERE user_id = 1")->fetchColumn();
        $this->assertEquals(1, $profile);
    }

    public function testAddWithMoodIdEmotionalProfileException() {
        $pdo = db();
        // Drop moods table to force an exception inside updateEmotionalProfile
        $pdo->exec("DROP TABLE IF EXISTS moods");

        $res = $this->runApiAction('add', [
            'tmdb_id' => 888,
            'tmdb_type' => 'movie',
            'tmdb_title' => 'Sad Movie',
            'mood_id' => 2
        ]);
        // Should still succeed because the exception is caught silently
        $this->assertTrue($res['success'] ?? false);
    }
    
    public function testDefaultAction() {
        $res = $this->runApiAction('unknown_action');
        $this->assertFalse($res['success'] ?? true);
    }
}
