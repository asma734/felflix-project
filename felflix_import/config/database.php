<?php
// ================================================================
//  FELFLIX — config/database.php
//  Connexion à la base de données MySQL
//  Ce fichier est inclus par tous les autres fichiers PHP
// ================================================================

$db_server   = "127.0.0.1";
$db_username = "root";
$db_pwd      = "";          // Vide par défaut dans XAMPP
$db_name     = "felflix";
$db_port     = 3306;        // ← Changer en 3307 si votre XAMPP utilise ce port

try {
    $cnx = new PDO(
        "mysql:host=$db_server;port=$db_port;dbname=$db_name;charset=utf8mb4",
        $db_username,
        $db_pwd
    );
    $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $cnx->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Erreur connexion BDD : " . $e->getMessage());
}
