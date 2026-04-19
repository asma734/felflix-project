<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: signup.php"); exit(); }
include(__DIR__ . "/../controller/traitement.php");

$successUpdate = isset($_GET['modif'])  && $_GET['modif']  === 'ok';
$successDelete = isset($_GET['delete']) && $_GET['delete'] === 'ok';
$errorDelete   = isset($_GET['delete']) && $_GET['delete'] === 'error';

$Users = isset($_GET['search']) && $_GET['search'] !== ''
    ? searchUsers($cnx, $_GET['search'])
    : getAllUsers($cnx);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Felflix — Utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#141414; color:#fff; }
        .navbar-brand { color:#e50914 !important; font-weight:900; font-size:1.8rem; }
        .table { color:#fff; }
        .table-dark th { background:#1a1a1a; }
        .card { background:#1f1f1f; border:none; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark px-4 py-2" style="background:#0d0d0d;">
    <a href="index.php" class="navbar-brand">🎬 FELFLIX</a>
</nav>

<!-- Toasts -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <?php if ($successUpdate): ?>
        <div class="toast text-bg-success border-0 show">
            <div class="d-flex"><div class="toast-body">✅ Utilisateur modifié</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
        </div>
    <?php endif; ?>
    <?php if ($successDelete): ?>
        <div class="toast text-bg-success border-0 show">
            <div class="d-flex"><div class="toast-body">✅ Utilisateur supprimé</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
        </div>
    <?php endif; ?>
    <?php if ($errorDelete): ?>
        <div class="toast text-bg-danger border-0 show">
            <div class="d-flex"><div class="toast-body">❌ Erreur suppression</div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
        </div>
    <?php endif; ?>
</div>

<div class="container mt-5">
    <h2 class="mb-4">👥 Gestion des utilisateurs</h2>

    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width:400px;">
            <input type="text" name="search" class="form-control bg-dark text-white border-secondary"
                   placeholder="Rechercher..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button class="btn btn-outline-secondary">Chercher</button>
        </div>
    </form>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-bordered text-center">
                <thead class="table-dark">
                    <tr><th>#</th><th>Nom</th><th>Email</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php $i = 0; foreach ($Users as $user): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= htmlspecialchars($user->nom) ?></td>
                    <td><?= htmlspecialchars($user->email) ?></td>
                    <td>
                        <button class="btn btn-success btn-sm"
                                data-bs-toggle="modal" data-bs-target="#edit<?= $user->id ?>">Modifier</button>
                        <button class="btn btn-danger btn-sm"
                                data-bs-toggle="modal" data-bs-target="#del<?= $user->id ?>">Supprimer</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALS UPDATE -->
<?php foreach ($Users as $user): ?>
<div class="modal fade" id="edit<?= $user->id ?>" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content bg-dark text-white">
    <div class="modal-header"><h5>Modifier l'utilisateur</h5>
      <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <form action="../controller/update_user.php" method="POST">
        <input type="hidden" name="idu" value="<?= $user->id ?>">
        <label>Nom</label>
        <input type="text" name="user_name" class="form-control bg-dark text-white border-secondary mb-2"
               value="<?= htmlspecialchars($user->nom) ?>" required>
        <label>Email</label>
        <input type="email" name="email" class="form-control bg-dark text-white border-secondary mb-2"
               value="<?= htmlspecialchars($user->email) ?>" required>
        <label>Nouveau mot de passe <small class="text-muted">(laisser vide pour ne pas changer)</small></label>
        <input type="password" name="password" class="form-control bg-dark text-white border-secondary mb-3">
        <button class="btn btn-primary w-100">Sauvegarder</button>
      </form>
    </div>
  </div></div>
</div>
<?php endforeach; ?>

<!-- MODALS DELETE -->
<?php foreach ($Users as $user): ?>
<div class="modal fade" id="del<?= $user->id ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content bg-dark text-white">
    <div class="modal-header bg-danger"><h5>Confirmation</h5>
      <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center">
        Supprimer <strong><?= htmlspecialchars($user->nom) ?></strong> ?
    </div>
    <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <a href="../controller/delete_user.php?idu=<?= $user->id ?>" class="btn btn-danger">Supprimer</a>
    </div>
  </div></div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    setTimeout(() => document.querySelectorAll('.toast').forEach(t => t.classList.remove('show')), 3000);
    if (window.history.replaceState) {
        const u = new URL(window.location);
        u.searchParams.delete('modif'); u.searchParams.delete('delete');
        window.history.replaceState({}, '', u.pathname);
    }
</script>
</body>
</html>
