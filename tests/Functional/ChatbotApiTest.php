<?php
namespace Tests\Functional;

use PHPUnit\Framework\TestCase;

class ChatbotApiTest extends TestCase {
    
    protected function setUp(): void {
        ob_start();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }
    
    protected function tearDown(): void {
        ob_end_clean();
    }

    public function testEmptyMessage() {
        $_SESSION['user'] = ['id' => 1, 'role' => 'user'];
        $_GET['action'] = 'message';
        $_GET['message'] = '';
        
        ob_start();
        require __DIR__ . '/../../controller/chatbot_api.php';
        $output = ob_get_clean();
        
        $json = json_decode($output, true);
        $this->assertEquals('Ekteb 7aja! 🌶', $json['reply']);
    }

    public function testResetAction() {
        $_SESSION['user'] = ['id' => 1, 'role' => 'user'];
        $_GET['action'] = 'reset';
        $_SESSION['chat_history'] = ['some_old_message'];
        
        ob_start();
        require __DIR__ . '/../../controller/chatbot_api.php';
        $output = ob_get_clean();
        
        $json = json_decode($output, true);
        $this->assertStringContainsString('Conv jedida!', $json['reply']);
        $this->assertEmpty($_SESSION['chat_history']);
    }

    public function testChatbotUnauthenticatedUser() {
        $_GET['action'] = 'message';
        $_GET['message'] = 'Matrix';
        $_SESSION = []; // No logged-in user

        ob_start();
        require __DIR__ . '/../../controller/chatbot_api.php';
        $output = ob_get_clean();

        $json = json_decode($output, true);
        $this->assertStringContainsString('Vous devez être inscrit', $json['reply']);
    }

    public function testValidMessageSuccessWithTmdbMovieAndTvMocked() {
        $_GET['action'] = 'message';
        $_GET['message'] = 'Matrix';
        // Populate history to > 30 items to hit history looping and slicing
        $_SESSION['chat_history'] = [];
        for ($i = 0; $i < 32; $i++) {
            $_SESSION['chat_history'][] = ['role' => 'user', 'content' => "Msg $i"];
        }
        $_SESSION['user'] = ['id' => 1, 'role' => 'user'];

        // Mock TMDB responses
        $GLOBALS['mock_tmdb_responses'] = [
            'search/multi' => [
                'results' => [
                    ['id' => 101, 'media_type' => 'movie'],
                    ['id' => 202, 'media_type' => 'tv']
                ]
            ],
            'movie/101' => [
                'id' => 101,
                'title' => 'The Matrix',
                'overview' => 'Awesome movie',
                'vote_average' => 8.7,
                'credits' => ['cast' => [['name' => 'Keanu']]]
            ],
            'tv/202' => [
                'id' => 202,
                'name' => 'Matrix TV Series',
                'overview' => 'Awesome series',
                'vote_average' => 7.5,
                'credits' => ['cast' => [['name' => 'Laurence']]]
            ]
        ];

        // Mock Groq response
        $GLOBALS['mock_groq_response'] = json_encode([
            'choices' => [
                ['message' => ['content' => 'Mocked Groq Answer 🌶']]
            ]
        ]);
        $GLOBALS['mock_groq_code'] = 200;
        $GLOBALS['mock_groq_err'] = '';

        // Setup SQLite for emotional profile
        global $cnx;
        $cnx = db();
        $cnx->exec("CREATE TABLE IF NOT EXISTS watch_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, tmdb_id INTEGER, tmdb_type TEXT, tmdb_title TEXT, mood_id INTEGER, added_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $cnx->exec("CREATE TABLE IF NOT EXISTS moods (
            id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, tone TEXT
        )");
        $cnx->exec("DELETE FROM watch_history");
        $cnx->exec("DELETE FROM moods");
        $cnx->exec("INSERT INTO moods (id, name, tone) VALUES (1, 'romansi', 'romantic')");
        $cnx->exec("INSERT INTO watch_history (user_id, tmdb_id, mood_id) VALUES (1, 123, 1)");

        ob_start();
        require __DIR__ . '/../../controller/chatbot_api.php';
        $output = ob_get_clean();

        $json = json_decode($output, true);
        $this->assertEquals('Mocked Groq Answer 🌶', $json['reply']);
        
        // Cleanup globals
        unset($GLOBALS['mock_tmdb_responses']);
        unset($GLOBALS['mock_groq_response']);
    }

    public function testTmdbFallbackAndGroqConnectionError() {
        $_SESSION['user'] = ['id' => 1, 'role' => 'user'];
        $_GET['action'] = 'message';
        $_GET['message'] = 'UnknownMovie';
        $_SESSION['chat_history'] = [];

        // Mock TMDB search yielding nothing in French but something in English
        // And French detail yields empty ID first (both movie and tv)
        $GLOBALS['mock_tmdb_responses'] = [
            'search/multi?api_key=19ec8eebb867ed533ce9bde4c160b437&language=fr-FR' => [
                'results' => []
            ],
            'search/multi?api_key=19ec8eebb867ed533ce9bde4c160b437&language=en-US' => [
                'results' => [
                    ['id' => 303, 'media_type' => 'movie'],
                    ['id' => 404, 'media_type' => 'tv']
                ]
            ],
            'movie/303?api_key=19ec8eebb867ed533ce9bde4c160b437&language=fr-FR' => [
                'id' => null
            ],
            'movie/303?api_key=19ec8eebb867ed533ce9bde4c160b437&language=en-US' => [
                'id' => 303,
                'title' => 'English Movie Only',
                'overview' => 'Eng'
            ],
            'tv/404?api_key=19ec8eebb867ed533ce9bde4c160b437&language=fr-FR' => [
                'id' => null
            ],
            'tv/404?api_key=19ec8eebb867ed533ce9bde4c160b437&language=en-US' => [
                'id' => 404,
                'name' => 'English TV Only',
                'overview' => 'Eng TV'
            ]
        ];

        // Mock Groq connection error
        $GLOBALS['mock_groq_response'] = '';
        $GLOBALS['mock_groq_code'] = 500;
        $GLOBALS['mock_groq_err'] = 'Network Down';

        ob_start();
        require __DIR__ . '/../../controller/chatbot_api.php';
        $output = ob_get_clean();

        $json = json_decode($output, true);
        $this->assertStringContainsString('Chkoun ma3andoch connexion', $json['reply']);

        unset($GLOBALS['mock_tmdb_responses']);
        unset($GLOBALS['mock_groq_response']);
    }

    public function testEmotionalProfileExceptionTriggered() {
        $_GET['action'] = 'message';
        $_GET['message'] = 'Hello';
        $_SESSION['user'] = ['id' => 1, 'role' => 'user'];
        $_SESSION['chat_history'] = [];

        // Nullify $cnx to force an Exception in the emotional profile query
        global $cnx;
        $originalCnx = $cnx;
        $cnx = null;

        $GLOBALS['mock_groq_response'] = json_encode([
            'choices' => [['message' => ['content' => 'Response without DB context']]]
        ]);
        $GLOBALS['mock_groq_code'] = 200;
        $GLOBALS['mock_groq_err'] = '';

        $GLOBALS['mock_tmdb_responses'] = [
            'search/multi' => ['results' => []]
        ];

        ob_start();
        require __DIR__ . '/../../controller/chatbot_api.php';
        $output = ob_get_clean();

        $json = json_decode($output, true);
        $this->assertEquals('Response without DB context', $json['reply']);

        $cnx = $originalCnx;
        unset($GLOBALS['mock_groq_response']);
        unset($GLOBALS['mock_tmdb_responses']);
    }
}
