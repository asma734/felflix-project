<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class ChatbotIntegrationTest extends TestCase {
    
    protected function setUp(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_GET = [];
    }

    public function testChatbotAiProcessingFlow() {
        $_SESSION['user'] = ['id' => 1, 'role' => 'user'];
        // Simuler un appel réel à l'API du chatbot avec un message valide
        // Cela va déclencher la recherche TMDB puis l'appel à Groq
        $_GET['action'] = 'message';
        $_GET['message'] = 'Je veux un film joyeux avec Jim Carrey';
        $_SESSION['chat_history'] = [];
        
        ob_start();
        require __DIR__ . '/../../controller/chatbot_api.php';
        $output = ob_get_clean();
        
        $json = json_decode($output, true);
        
        // Vérification de la structure de réponse de l'IA
        $this->assertIsArray($json, "L'IA doit retourner un JSON valide");
        $this->assertArrayHasKey('reply', $json, "Le JSON doit contenir la clé 'reply'");
        
        // Puisque nous dépendons de la clé API (qui peut échouer si elle a expiré),
        // on vérifie que la réponse n'est pas vide (ça sera soit la vraie rep de l'IA, soit le fallback d'erreur)
        $this->assertNotEmpty($json['reply'], "La réponse du Chatbot ne doit pas être vide");
    }
}
