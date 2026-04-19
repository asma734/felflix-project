<?php
include(__DIR__ . "/../config/database.php");
include(__DIR__ . "/../controller/traitement.php");
session_start();

// Déjà connecté → rediriger vers accueil
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if (!empty($_POST)) {

    // ── LOGIN ────────────────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $user = ConnectUser($cnx, $_POST);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['email']   = $user['email'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }

    // ── INSCRIPTION ──────────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'signup') {
        if (AddUser($cnx, $_POST)) {
            $error = "✅ Compte créé ! Vous pouvez vous connecter.";
        } else {
            $error = "❌ Erreur : email déjà utilisé ou champ manquant.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Felflix — Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#141414; color:#fff; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .logo { color:#e50914; font-size:2.5rem; font-weight:900; letter-spacing:2px; }
        .card { background:#1f1f1f; border:none; border-radius:12px; }
        .btn-netflix { background:#e50914; border:none; color:#fff; width:100%; padding:10px; border-radius:6px; }
        .btn-netflix:hover { background:#c40812; color:#fff; }
        .nav-tabs .nav-link { color:#aaa; }
        .nav-tabs .nav-link.active { background:#e50914; color:#fff; border-color:#e50914; }
    </style>
</head>
<body>
<div style="width:100%;max-width:420px;padding:20px;">
    <div class="text-center mb-4">
        <div class="logo">🎬 FELFLIX</div>
        <small class="text-muted">Recommandations intelligentes de films</small>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-info py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card p-4">
        <!-- Onglets Login / Sign Up -->
        <ul class="nav nav-tabs mb-4" id="authTabs">
            <li class="nav-item w-50">
                <button class="nav-link active w-100" data-bs-toggle="tab" data-bs-target="#loginTab">Connexion</button>
            </li>
            <li class="nav-item w-50">
                <button class="nav-link w-100" data-bs-toggle="tab" data-bs-target="#signupTab">Inscription</button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- CONNEXION -->
            <div class="tab-pane fade show active" id="loginTab">
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control bg-dark text-white border-secondary"
                               placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control bg-dark text-white border-secondary"
                               placeholder="Mot de passe" required>
                    </div>
                    <button class="btn btn-netflix">Se connecter</button>
                </form>
            </div>

            <!-- INSCRIPTION -->
            <div class="tab-pane fade" id="signupTab">
                <form method="POST">
                    <input type="hidden" name="action" value="signup">
                    <div class="mb-3">
                        <input type="text" name="user_name" class="form-control bg-dark text-white border-secondary"
                               placeholder="Nom d'utilisateur" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control bg-dark text-white border-secondary"
                               placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control bg-dark text-white border-secondary"
                               placeholder="Mot de passe" required>
                    </div>
                    <button class="btn btn-netflix">Créer un compte</button>
                </form>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
