<?php
namespace Tests\Functional;

use PHPUnit\Framework\TestCase;
use PDO;
use Exception;
use RuntimeException;
use TypeError;

/**
 * Suite de démonstration d'erreurs — Conçue pour générer délibérément
 * différents niveaux d'anomalies (fatale, critique, majeure, mineure, cosmétique).
 * 
 * NOTE : Ces tests sont faits pour ÉCHOUER afin de valider les rapports PHPUnit.
 */
class IntentionalErrorsTest extends TestCase {

    // ── 1. ERREUR FATALE (Fatal / Error) ──────────────────────────────
    // Empêche l'exécution normale du code au niveau le plus bas
    public function testFatalErrorSimulation() {
        // Pas de expectException pour laisser éclater l'erreur fatale dans le rapport !
        $obj = null;
        $obj->nonExistentMethod();
    }

    // ── 2. ERREUR CRITIQUE (Critical / Database / Crash) ────────────────
    // Une exception majeure non gérée (ex: crash base de données)
    public function testCriticalDatabaseCrashSimulation() {
        // Simule un crash critique lors d'une requête SQL sur une base de données corrompue ou table inexistante
        $pdo = new PDO('sqlite::memory:');
        
        // Requête sur une table qui n'existe pas sans bloc try/catch
        $pdo->query("SELECT * FROM non_existent_table_that_crashes_system");
    }

    // ── 3. ERREUR MAJEURE (Major / Business Logic Exception) ────────────
    // Une exception de logique métier non capturée (ex: limite de requêtes chatbot)
    public function testMajorLogicExceptionSimulation() {
        throw new RuntimeException("Erreur de logique métier : L'utilisateur a dépassé la limite journalière de 50 requêtes pour le Chatbot Felflix !");
    }

    // ── 4. ERREUR MINEURE / AVERTISSEMENT (Minor / Warning) ──────────────
    // Avertissement PHP (Warning, Notice, Deprecated)
    public function testMinorWarningSimulation() {
        // Déclenche un warning PHP (division par zéro ou fichier inexistant)
        $file = file_get_contents('non_existent_file_warning.txt');
        $this->assertFalse($file);
    }

    // ── 5. ERREUR COSMÉTIQUE (Cosmetic / Assertion Failure) ─────────────
    // L'application tourne parfaitement, mais un détail visuel ou textuel ne correspond pas à l'attente
    public function testCosmeticAssertionFailureSimulation() {
        $expectedTitle = "Felflix - Vos films préférés en streaming 🌶";
        $actualTitle   = "Felflix - Vos films preferes en streaming"; // Erreur d'accents / émoji manquant

        $this->assertEquals($expectedTitle, $actualTitle, "Échec Cosmétique : Les accents ou l'émoji piment sont absents du titre de la page d'accueil !");
    }
}
