<?php
// ============================================================
//  DEBUG LOGIN — Supprime ce fichier après avoir résolu !
// ============================================================
session_start();
require_once '../config/database.php';

$email = $_GET['email'] ?? 'ton_email@test.com';  // Remplace par ton email
$password = $_GET['pwd'] ?? 'ton_mot_de_passe';    // Remplace par ton mot de passe

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;font-size:14px'>";
echo "=== FELFLIX LOGIN DEBUG ===\n\n";

// 1. Test connexion BDD
echo "1. Connexion BDD: ";
try {
    $test = $cnx->query("SELECT 1")->fetchColumn();
    echo "OK ✅ (port: " . (strpos($cnx->getAttribute(PDO::ATTR_SERVER_INFO), "") !== false ? "connecté" : "?") . ")\n";
} catch(Exception $e) {
    echo "ERREUR ❌ " . $e->getMessage() . "\n";
}

// 2. Cherche l'utilisateur
echo "\n2. Recherche email: '$email'\n";
$r = $cnx->prepare("SELECT id, nom, email, password, role FROM users WHERE email=?");
$r->execute([$email]);
$row = $r->fetch();

if (!$row) {
    echo "   RÉSULTAT: ❌ Aucun utilisateur trouvé avec cet email!\n";
    echo "   → Problème: l'email n'existe pas dans la base.\n";
} else {
    echo "   RÉSULTAT: ✅ Utilisateur trouvé!\n";
    echo "   - ID: " . $row['id'] . "\n";
    echo "   - Nom: " . $row['nom'] . "\n";
    echo "   - Role: " . $row['role'] . "\n";
    echo "   - Hash stocké: " . substr($row['password'], 0, 30) . "...\n";
    echo "   - Longueur hash: " . strlen($row['password']) . " chars\n";
    
    // 3. Test password_verify
    echo "\n3. Vérification mot de passe '$password':\n";
    $ok = password_verify($password, $row['password']);
    echo "   password_verify() = " . ($ok ? "TRUE ✅" : "FALSE ❌") . "\n";
    
    if (!$ok) {
        echo "\n   DIAGNOSTIC:\n";
        // Vérifie si c'est un hash bcrypt valide
        if (substr($row['password'], 0, 4) === '$2y$') {
            echo "   - Hash est bien bcrypt ($2y$) ✅\n";
            echo "   - Le mot de passe entré ne correspond pas au hash.\n";
            echo "   - Vérifie si tu as tapé le bon mot de passe.\n";
        } elseif (strlen($row['password']) === 32) {
            echo "   - Hash semble être MD5 (32 chars) ⚠️\n";
            echo "   - L'ancien code utilisait MD5, le nouveau utilise bcrypt!\n";
        } else {
            echo "   - Hash format inconnu (longueur: " . strlen($row['password']) . ")\n";
        }
        
        // Test avec hash direct
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        echo "   - Nouveau hash généré: " . substr($newHash, 0, 30) . "...\n";
    }
}

// 4. Affiche tous les utilisateurs
echo "\n4. Tous les utilisateurs dans la base:\n";
$all = $cnx->query("SELECT id, nom, email, role, LEFT(password,20) as pwd_preview FROM users")->fetchAll();
if (empty($all)) {
    echo "   ❌ AUCUN utilisateur! La table est vide.\n";
    echo "   → Tu fais le signup mais les données vont peut-être dans une autre base!\n";
} else {
    foreach ($all as $u) {
        echo "   [{$u['id']}] {$u['nom']} | {$u['email']} | {$u['role']} | hash: {$u['pwd_preview']}...\n";
    }
}

// 5. Nom de la base connectée
echo "\n5. Base de données active: ";
echo $cnx->query("SELECT DATABASE()")->fetchColumn() . "\n";

echo "\n=== FIN DEBUG ===\n";
echo "</pre>";
echo "<hr/><p style='color:#aaa;font-size:12px'>⚠️ Supprime debug_login.php après le diagnostic!</p>";
echo "<form style='margin:20px'>";
echo "<label>Email: <input name='email' value='" . htmlspecialchars($email) . "' style='width:250px;margin:5px'></label><br/>";
echo "<label>Mot de passe: <input name='pwd' value='" . htmlspecialchars($password) . "' style='width:250px;margin:5px'></label><br/>";
echo "<button type='submit'>Tester</button>";
echo "</form>";
