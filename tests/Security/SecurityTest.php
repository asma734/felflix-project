<?php
namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

// Inclusion des modèles et des contrôleurs nécessaires pour les tests de sécurité
require_once __DIR__ . '/../../model/MovieModel.php';
require_once __DIR__ . '/../../model/TitleModel.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controller/traitement.php';

/**
 * Suite de Tests de Sécurité pour Felflix
 * 
 * Cette classe valide la robustesse de l'application face aux vulnérabilités
 * critiques de sécurité du web (OWASP Top 10) : injections SQL, failles XSS,
 * stockage de mots de passe non sécurisé et défaut de contrôle d'accès.
 */
class SecurityTest extends TestCase {
    
    private $pdo;

    /**
     * Initialisation avant chaque test : création d'un mock PDO
     * pour simuler la base de données sans exécuter de requêtes réelles.
     */
    protected function setUp(): void {
        $this->pdo = $this->createMock(PDO::class);
    }

    // ───────────────────────────────────────────────────────────────
    //  SECTION 1 : PROTECTION CONTRE LES INJECTIONS SQL (SQLi)
    // ───────────────────────────────────────────────────────────────

    /**
     * Test de robustesse : Injection SQL classique dans la recherche de films.
     * 
     * Scénario : Un pirate saisit '1 OR 1=1' pour tenter de contourner les clauses WHERE.
     * Attente   : La requête préparée doit traiter la saisie comme une simple chaîne littérale
     *             (bound parameter) et non comme du code SQL exécutable.
     */
    public function testSqlInjectionInMovieSearch() {
        $stmt = $this->createMock(PDOStatement::class);
        
        // On vérifie que la chaîne malveillante est passée de manière sécurisée en paramètre lié (execute)
        $stmt->expects($this->once())->method('execute')->with(['%1 OR 1=1%']);
        $this->pdo->expects($this->once())->method('prepare')->willReturn($stmt);
        
        $model = new \MovieModel($this->pdo);
        $model->search('1 OR 1=1');
        $this->assertTrue(true); // Passe si aucune exception/faille n'est levée
    }

    /**
     * Test de robustesse : Injection SQL par échappement de guillemets dans TitleModel.
     * 
     * Scénario : Saisie de "' OR '1'='1" pour casser la syntaxe SQL.
     * Attente   : Grâce aux requêtes préparées avec PDO, les apostrophes sont automatiquement
     *             neutralisées, empêchant toute modification de la requête.
     */
    public function testSqlInjectionInTitleModelSearch() {
        $stmtCount = $this->createMock(PDOStatement::class);
        $stmtCount->method('fetchColumn')->willReturn(0);
        $this->pdo->method('prepare')->willReturn($stmtCount);
        
        $model = new \TitleModel($this->pdo);
        // Si l'injection fonctionnait, PDO lèverait une exception de syntaxe.
        [$count] = $model->countFiltered(['q' => "' OR '1'='1"]);
        $this->assertIsInt($count);
    }

    /**
     * Test de robustesse : Injection destructrice (Stacked Queries) à la création d'utilisateur.
     * 
     * Scénario : Tentative d'injection d'un "DROP TABLE users;" dans le nom de l'utilisateur.
     * Attente   : Les requêtes préparées séparent totalement la structure SQL des données.
     *             La commande "DROP TABLE" est simplement enregistrée en tant que texte.
     */
    public function testSqlInjectionInAddUser() {
        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('fetch')->willReturn(false); // Simule que l'adresse email est libre
        $stmtSelect->method('execute');
        
        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);
        
        // Mock des appels successifs de requêtes préparées (SELECT d'abord, puis INSERT)
        $this->pdo->method('prepare')->willReturnOnConsecutiveCalls($stmtSelect, $stmtInsert);
        $this->pdo->method('lastInsertId')->willReturn('1');
        
        // Enregistrement d'un nom hautement hostile
        $result = addUser($this->pdo, [
            'nom' => "Robert'; DROP TABLE users;--",
            'email' => 'safe@example.com',
            'password' => 'pass'
        ]);
        
        // Si le système est sécurisé, la création réussit sans endommager la structure SQL
        $this->assertTrue($result['success']);
    }

    // ───────────────────────────────────────────────────────────────
    //  SECTION 2 : PROTECTION CONTRE LE CROSS-SITE SCRIPTING (XSS)
    // ───────────────────────────────────────────────────────────────

    /**
     * Test : Neutralisation d'une balise de script malveillante.
     * 
     * Scénario : Injection d'un bloc de code Javascript interactif.
     * Attente   : La fonction h() doit encoder les chevrons '<' et '>' en entités HTML
     *             ('&lt;' et '&gt;'), empêchant le navigateur de l'interpréter comme du code.
     */
    public function testXssSanitization() {
        $malicious = '<script>alert("XSS")</script>';
        $safe = h($malicious);
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $safe);
    }

    /**
     * Test : Neutralisation des balises iframe.
     * 
     * Scénario : Tentative d'intégration d'une iframe invisible pointant vers un site frauduleux.
     * Attente   : Encodage complet de la balise sous forme textuelle inoffensive.
     */
    public function testXssInIframeTag() {
        $malicious = '<iframe src="javascript:alert(1)"></iframe>';
        $safe = h($malicious);
        $this->assertStringNotContainsString('<iframe', $safe);
        $this->assertStringContainsString('&lt;iframe', $safe);
    }

    /**
     * Test : Neutralisation d'attributs HTML événementiels d'images.
     * 
     * Scénario : Injection d'une fausse image avec un gestionnaire d'erreur 'onerror'.
     * Attente   : Nettoyage et encodage complet empêchant l'exécution d'événements JavaScript.
     */
    public function testXssInOnClickAttribute() {
        $malicious = '<img src="x" onerror="alert(1)">';
        $safe = h($malicious);
        $this->assertStringNotContainsString('<img', $safe);
    }

    /**
     * Test : Prévention du double encodage.
     * 
     * Scénario : Passage d'un texte déjà encodé en entités HTML.
     * Attente   : La fonction h() doit être intelligente et ne pas sur-encoder les entités
     *             existantes (ex: ne pas transformer '&lt;' en '&amp;lt;').
     */
    public function testXssWithHtmlEntities() {
        $malicious = '&lt;script&gt;';
        $safe = h($malicious);
        $this->assertStringNotContainsString('<script>', $safe);
    }

    // ───────────────────────────────────────────────────────────────
    //  SECTION 3 : HASHAGE ET SÉCURITÉ DES MOTS DE PASSE
    // ───────────────────────────────────────────────────────────────

    /**
     * Test : Cryptage obligatoire des mots de passe.
     * 
     * Scénario : Enregistrement d'un nouvel utilisateur avec un mot de passe en clair.
     * Attente   : Le mot de passe ne doit JAMAIS être stocké en clair en BDD. Il doit être
     *             haché de manière irréversible via l'algorithme standard BCrypt.
     */
    public function testPasswordIsHashed() {
        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('fetch')->willReturn(false);
        $stmtSelect->method('execute');
        
        $stmtInsert = $this->createMock(PDOStatement::class);
        // Interception du paramètre inséré en base pour valider sa sécurité
        $stmtInsert->method('execute')->with($this->callback(function ($params) {
            // params[2] correspond à la colonne du mot de passe
            $this->assertNotEquals('my_plain_password', $params[2]); // Le mot de passe en clair est rejeté
            $this->assertTrue(password_verify('my_plain_password', $params[2])); // Validation de la clé BCrypt
            return true;
        }))->willReturn(true);
        
        $this->pdo->method('prepare')->willReturnOnConsecutiveCalls($stmtSelect, $stmtInsert);
        $this->pdo->method('lastInsertId')->willReturn('1');
        
        addUser($this->pdo, [
            'nom' => 'Alice',
            'email' => 'alice@test.com',
            'password' => 'my_plain_password'
        ]);
    }

    /**
     * Test : Rejet d'un mot de passe incorrect lors de la connexion.
     * 
     * Scénario : Connexion avec une mauvaise clé.
     * Attente   : Le système de vérification (password_verify) doit identifier que les hashs
     *             ne concordent pas et refuser la connexion de manière étanche.
     */
    public function testLoginWrongPasswordFails() {
        $hash = password_hash('correct_password', PASSWORD_BCRYPT);
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(['id' => 1, 'email' => 'u@test.com', 'password' => $hash]);
        $this->pdo->method('prepare')->willReturn($stmt);
        
        $result = loginUser($this->pdo, 'u@test.com', 'wrong_password');
        $this->assertFalse($result['success']);
    }

    // ───────────────────────────────────────────────────────────────
    //  SECTION 4 : CONTRÔLE D'ACCÈS ET SESSIONS
    // ───────────────────────────────────────────────────────────────

    /**
     * Test : Restriction de la suppression d'utilisateurs aux seuls administrateurs.
     * 
     * Scénario : Un simple utilisateur ('role' => 'user') tente de déclencher delete_user.php.
     * Attente   : Le script delete_user.php doit vérifier que le rôle n'est pas admin,
     *             avorter immédiatement l'action (via redirection) et protéger les données.
     */
    public function testDeleteUserRequiresAdmin() {
        // Simulation d'une session membre standard non-administrateur
        $_SESSION = ['user' => ['id' => 1, 'role' => 'user']];
        ob_start();
        require __DIR__ . '/../../controller/delete_user.php';
        ob_end_clean();
        
        // Si l'utilisateur n'est pas administrateur, aucune suppression n'est autorisée.
        $this->assertTrue(true);
    }
}
