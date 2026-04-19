<?php
// ================================================================
//  FELFLIX — controller/delete_user.php  (bug injection SQL corrigé)
// ================================================================
include(__DIR__ . "/../config/database.php");

if (isset($_GET['idu'])) {
    $id = intval($_GET['idu']);
    $stmt = $cnx->prepare("DELETE FROM users WHERE id = :id");
    $ok   = $stmt->execute([':id' => $id]);
    header('location:../view/user_list.php?delete=' . ($ok ? 'ok' : 'error'));
    exit();
}
