<?php
// ================================================================
//  FELFLIX — controller/update_user.php  (bug md5 → password_hash corrigé)
// ================================================================
include(__DIR__ . "/../config/database.php");
include(__DIR__ . "/../model/User.php");

if (isset($_POST['idu'])) {
    $user           = new User($_POST['user_name'], $_POST['email'], $_POST['password']);
    $user->id       = intval($_POST['idu']);

    // Récupérer l'ancien mot de passe hashé
    $stmt = $cnx->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $user->id]);
    $old = $stmt->fetch();

    // Si le champ password est vide → garder l'ancien
    // Si l'utilisateur a tapé un nouveau mot de passe → le hasher
    if (empty($user->password)) {
        $password = $old['password'];
    } else {
        $password = password_hash($user->password, PASSWORD_DEFAULT);
    }

    $req = $cnx->prepare(
        "UPDATE users SET nom=:nom, email=:email, password=:pwd WHERE id=:id"
    );
    $ok = $req->execute([
        ':nom'   => $user->nom,
        ':email' => $user->email,
        ':pwd'   => $password,
        ':id'    => $user->id
    ]);

    header('location:../view/user_list.php?modif=' . ($ok ? 'ok' : 'error'));
    exit();
}
