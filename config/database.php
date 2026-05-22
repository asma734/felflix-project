<?php
// ============================================================
//  FELFLIX 🌶 — Configuration BDD unifiée
//  Fusion Felflix + Nextflix (omdb_website)
// ============================================================
define('DB_HOST',    '127.0.0.1');
define('DB_PORT',    '3307');           // Port 3307 utilisé par le USER pour MySQL
define('DB_NAME',    'omdb_website');   // Base principale avec les données IMDb
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO partagée
 */
if (!function_exists('db')) {
    function db(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            try {
                $dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset='.DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                die('<div style="font-family:sans-serif;padding:40px;background:#0d0a14;color:#fff;min-height:100vh">'
                  . '<h2 style="color:#e63946">🌶 Felflix — Connexion BDD échouée</h2>'
                  . '<p>Vérifie tes identifiants dans <code>config/database.php</code> et assure-toi que la base <code>omdb_website</code> est bien importée.</p>'
                  . '<p style="color:#888;font-size:13px">'.htmlspecialchars($e->getMessage()).'</p></div>');
            }
        }
        return $pdo;
    }
}

// Compatibilité avec l'ancien code : variable globale $cnx
$cnx = db();

/**
 * Sécurise l'affichage HTML
 */
if (!function_exists('h')) {
    function h(string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Formate une note sur 10
 */
function formatRating($val): string {
    return ($val !== null && $val !== '') ? number_format((float)$val, 1) : 'N/A';
}
?>
