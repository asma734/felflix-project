<?php
$db_server   = "127.0.0.1";
$db_username = "root";
$db_pwd      = "";
$db_name     = "felflix";

try {
    $cnx = new PDO(
        "mysql:host=$db_server;port=3307;dbname=$db_name;charset=utf8mb4",
        $db_username,
        $db_pwd,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die("Erreur BDD : " . $e->getMessage());
}
?>
