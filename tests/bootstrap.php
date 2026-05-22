<?php
require_once __DIR__ . '/../vendor/autoload.php';
define('PHPUNIT_TEST_SUITE', true);

// Define the mock DB function before database.php is loaded
if (!function_exists('db')) {
    function db(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            // In memory sqlite DB for tests
            $pdo = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Create necessary schemas to avoid crash
            $pdo->exec("CREATE TABLE IF NOT EXISTS watchlist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                tmdb_id INTEGER,
                tmdb_title TEXT,
                tmdb_poster TEXT,
                tmdb_type TEXT,
                category_name TEXT,
                added_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS watch_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                tmdb_id INTEGER,
                tmdb_type TEXT,
                tmdb_title TEXT,
                mood_id INTEGER,
                added_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS moods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                tone TEXT
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS emotional_profiles (
                user_id INTEGER PRIMARY KEY,
                romantic_pct REAL, nostalgic_pct REAL, dark_pct REAL,
                action_pct REAL, happy_pct REAL, sad_pct REAL, anxious_pct REAL,
                diversity_score REAL, balance_score REAL, dominant_mood TEXT
            )");
        }
        return $pdo;
    }
}
