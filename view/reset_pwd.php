<?php
// ============================================================
//  FELFLIX — Script de réinitialisation/test des mots de passe
//  Lance: http://localhost/felflix/view/reset_pwd.php
//  SUPPRIME CE FICHIER APRÈS UTILISATION !
// ============================================================
session_start();
require_once '../config/database.php';

$msg = '';

// Action: reset mot de passe d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_email'])) {
    $email   = trim($_POST['reset_email']);
    $newPwd  = trim($_POST['new_pwd']);
    if ($email && $newPwd && strlen($newPwd) >= 4) {
        $hash = password_hash($newPwd, PASSWORD_BCRYPT);
        $r = $cnx->prepare("UPDATE users SET password=? WHERE email=?");
        $r->execute([$hash, $email]);
        if ($r->rowCount() > 0) {
            $msg = "✅ Mot de passe mis à jour pour '$email'! Tu peux maintenant te connecter.";
        } else {
            $msg = "❌ Email '$email' introuvable dans la base!";
        }
    } else {
        $msg = "⚠️ Remplis email et mot de passe (min 4 caractères)";
    }
}

// Vérifie aussi la longueur de la colonne password
$colInfo = $cnx->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch();
$colType = $colInfo['Type'] ?? 'inconnu';
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Reset Password — Debug</title>
<style>
body{font-family:monospace;background:#0d0d0d;color:#e0e0e0;padding:30px;max-width:600px;margin:0 auto}
h2{color:#e63946}input{width:100%;padding:8px;margin:6px 0;background:#222;border:1px solid #444;color:#fff;border-radius:6px}
button{background:#e63946;color:#fff;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;margin-top:10px;font-size:15px}
.ok{background:#1a3a1a;border:1px solid #2a6a2a;padding:12px;border-radius:8px;color:#6fdc6f;margin-bottom:16px}
.err{background:#3a1a1a;border:1px solid #6a2a2a;padding:12px;border-radius:8px;color:#dc6f6f;margin-bottom:16px}
table{width:100%;border-collapse:collapse;margin-top:16px}
td,th{padding:8px;border:1px solid #333;font-size:12px}th{background:#1a1a1a;color:#e63946}
</style></head><body>
<h2>🌶 Felflix — Debug Mot de Passe</h2>

<?php if ($msg): ?>
<div class="<?= strpos($msg,'✅')!==false ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<p style="color:#aaa;font-size:12px">Type colonne password en BDD: <strong style="color:#fde047"><?= htmlspecialchars($colType) ?></strong>
<?php if (strpos($colType,'255')===false && strpos($colType,'text')===false): ?>
<span style="color:#f87171">⚠️ TROP COURT pour bcrypt! Doit être VARCHAR(255)</span>
<?php else: ?>
<span style="color:#6fdc6f">✅ OK</span>
<?php endif; ?></p>

<hr style="border-color:#333;margin:20px 0"/>
<h3 style="color:#fde047">Réinitialiser un mot de passe</h3>
<form method="POST">
    <label>Email du compte:</label>
    <input type="email" name="reset_email" required placeholder="example@email.com"/>
    <label>Nouveau mot de passe:</label>
    <input type="password" name="new_pwd" required placeholder="Min 4 caractères..."/>
    <button type="submit">🔧 Réinitialiser le mot de passe</button>
</form>

<hr style="border-color:#333;margin:20px 0"/>
<h3 style="color:#fde047">Tous les utilisateurs</h3>
<table>
<tr><th>ID</th><th>Nom</th><th>Email</th><th>Role</th><th>Hash (début)</th></tr>
<?php
$users = $cnx->query("SELECT id,nom,email,role,password FROM users ORDER BY id DESC")->fetchAll();
if (empty($users)) {
    echo "<tr><td colspan='5' style='color:#f87171;text-align:center'>❌ Table vide — aucun utilisateur!</td></tr>";
} else {
    foreach ($users as $u) {
        $hashOk = substr($u['password'],0,4)==='$2y$' ? '✅bcrypt' : '⚠️autre';
        echo "<tr>
            <td>{$u['id']}</td>
            <td>".htmlspecialchars($u['nom'])."</td>
            <td>".htmlspecialchars($u['email'])."</td>
            <td>{$u['role']}</td>
            <td>$hashOk &nbsp;".htmlspecialchars(substr($u['password'],0,25))."...</td>
        </tr>";
    }
}
?>
</table>

<p style="margin-top:30px;color:#555;font-size:11px">⚠️ SUPPRIME reset_pwd.php dès que tu as fini!</p>
</body></html>
