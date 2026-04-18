<?php
require 'config/database.php';

echo "=== FIX FK CONSTRAINTS ===\n\n";

// 1. Montre les FK actuelles
$fks = $cnx->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME")->fetchAll();

echo "Foreign Keys actuelles:\n";
foreach ($fks as $fk) {
    echo "  [{$fk['TABLE_NAME']}] {$fk['CONSTRAINT_NAME']}: {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
}

// 2. Fix comments table
echo "\n--- Fix comments table ---\n";

// Disable FK checks temporairement
$cnx->exec("SET FOREIGN_KEY_CHECKS=0");

// Supprimer les contraintes FK problematiques sur movie_id
$fksToDrop = ['comments_ibfk_1','comments_ibfk_2','comments_ibfk_3'];
foreach ($fksToDrop as $fkName) {
    try {
        $cnx->exec("ALTER TABLE comments DROP FOREIGN KEY $fkName");
        echo "  ✅ Supprimé FK: $fkName\n";
    } catch(PDOException $e) {
        echo "  ⚠️  $fkName n'existe pas (OK)\n";
    }
}

// Re-allouer movie_id comme NULL par défaut (sans FK stricte)
try {
    $cnx->exec("ALTER TABLE comments MODIFY COLUMN movie_id INT DEFAULT NULL");
    echo "  ✅ movie_id: DEFAULT NULL OK\n";
} catch(PDOException $e) {
    echo "  ❌ " . $e->getMessage() . "\n";
}

// Re-enable FK checks
$cnx->exec("SET FOREIGN_KEY_CHECKS=1");

// Garde uniquement la FK user_id (important)
echo "\n--- Fix posts table (même problème potentiel) ---\n";
$cnx->exec("SET FOREIGN_KEY_CHECKS=0");
$postFks = ['posts_ibfk_1','posts_ibfk_2'];
foreach ($postFks as $fkName) {
    try {
        $cnx->exec("ALTER TABLE posts DROP FOREIGN KEY $fkName");
        echo "  ✅ Supprimé FK: $fkName\n";
    } catch(PDOException $e) {
        echo "  ⚠️  $fkName n'existe pas (OK)\n";
    }
}
$cnx->exec("SET FOREIGN_KEY_CHECKS=1");

// 3. Test final: INSERT comment sans movie_id
echo "\n--- Test INSERT comment ---\n";
try {
    $stmt = $cnx->prepare("INSERT INTO comments(user_id,tmdb_id,tmdb_type,content) VALUES(?,?,?,?)");
    $stmt->execute([1, 99999, 'movie', 'TEST - supprime moi']);
    $testId = $cnx->lastInsertId();
    echo "  ✅ INSERT OK! (id=$testId)\n";
    // Nettoyer le test
    $cnx->exec("DELETE FROM comments WHERE id=$testId");
    echo "  ✅ Test nettoyé\n";
} catch(PDOException $e) {
    echo "  ❌ ERREUR: " . $e->getMessage() . "\n";
}

// 4. Montre les FK restantes
echo "\n--- FK restantes ---\n";
$fks2 = $cnx->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME")->fetchAll();
foreach ($fks2 as $fk) {
    echo "  [{$fk['TABLE_NAME']}] {$fk['CONSTRAINT_NAME']}: {$fk['COLUMN_NAME']}\n";
}

echo "\nDone!\n";
