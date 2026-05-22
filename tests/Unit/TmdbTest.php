<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../controller/tmdb.php';

class TmdbTest extends TestCase {
    
    public function testTmdbTitle() {
        $this->assertEquals('Matrix', tmdbTitle(['title' => 'Matrix']));
        $this->assertEquals('Matrix', tmdbTitle(['name' => 'Matrix']));
        $this->assertEquals('Matrix', tmdbTitle(['original_title' => 'Matrix']));
        $this->assertEquals('Matrix', tmdbTitle(['original_name' => 'Matrix']));
        $this->assertEquals('Sans titre', tmdbTitle([]));
    }

    public function testTmdbYear() {
        $this->assertEquals('1999', tmdbYear(['release_date' => '1999-03-31']));
        $this->assertEquals('2000', tmdbYear(['first_air_date' => '2000-01-01']));
        $this->assertEquals('', tmdbYear([]));
    }

    public function testTmdbPoster() {
        $this->assertEquals('https://image.tmdb.org/t/p/w300/path.jpg', tmdbPoster('/path.jpg'));
        $this->assertEquals('https://image.tmdb.org/t/p/original/path.jpg', tmdbPoster('/path.jpg', 'original'));
        $this->assertNull(tmdbPoster(''));
        $this->assertNull(tmdbPoster(null));
    }

    public function testTmdbGenreId() {
        $this->assertEquals(28, tmdbGenreId('action'));
        $this->assertEquals(35, tmdbGenreId('comedie'));
        $this->assertEquals(10759, tmdbGenreId('action', 'tv')); // TV action
        $this->assertEquals(28, tmdbGenreId('unknown_slug')); // fallback to 28
    }

    public function testTmdbSummary() {
        $movie = [
            'title' => 'Inception',
            'release_date' => '2010-07-15',
            'vote_average' => 8.8,
            'overview' => 'A thief who steals corporate secrets.',
            'credits' => [
                'crew' => [
                    ['name' => 'Christopher Nolan', 'job' => 'Director']
                ],
                'cast' => [
                    ['name' => 'Leonardo DiCaprio'],
                    ['name' => 'Joseph Gordon-Levitt']
                ]
            ],
            'videos' => [
                'results' => [
                    ['type' => 'Trailer', 'site' => 'YouTube', 'key' => '8hP9D6kZseM']
                ]
            ]
        ];

        $summary = tmdbSummary($movie);
        
        $this->assertStringContainsString('Inception (2010)', $summary);
        $this->assertStringContainsString('Note: 8.8/10', $summary);
        $this->assertStringContainsString('Réalisateur/Créateur: Christopher Nolan', $summary);
        $this->assertStringContainsString('Avec: Leonardo DiCaprio, Joseph Gordon-Levitt', $summary);
        $this->assertStringContainsString('A thief who steals corporate secrets.', $summary);
        $this->assertStringContainsString('Trailer: https://youtu.be/8hP9D6kZseM', $summary);
    }

    public function testTmdbMovies() {
        $res = tmdbMovies(1, 'fr-FR');
        $this->assertIsArray($res);
    }

    public function testTmdbTV() {
        $res = tmdbTV(1, 'fr-FR');
        $this->assertIsArray($res);
    }

    public function testTmdbTrending() {
        $res = tmdbTrending('all', 'fr-FR');
        $this->assertIsArray($res);
    }

    public function testTmdbSearch() {
        $res = tmdbSearch('Matrix', 'fr-FR');
        $this->assertIsArray($res);
    }

    public function testTmdbMovieDetail() {
        $res = tmdbMovieDetail(603, 'fr-FR'); // Matrix ID
        $this->assertIsArray($res);
    }

    public function testTmdbTVDetail() {
        $res = tmdbTVDetail(1399, 'fr-FR'); // Game of Thrones ID
        $this->assertIsArray($res);
    }

    public function testTmdbDiscover() {
        $res = tmdbDiscover(28, 'movie', 'fr-FR', 1);
        $this->assertIsArray($res);
    }

    public function testTmdbFindById() {
        $res = tmdbFindById('tt0133093', 'imdb_id');
        $this->assertTrue(is_array($res) || is_null($res));
        $res2 = tmdbFindById('tt0944947', 'imdb_id'); // GoT
        $this->assertTrue(is_array($res2) || is_null($res2));
    }
}
