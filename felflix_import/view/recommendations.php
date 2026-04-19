<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: signup.php"); exit(); }

include(__DIR__ . "/../config/database.php");
include(__DIR__ . "/../controller/traitement.php");
include(__DIR__ . "/../ai/recommender.php");

$recs     = recommend_for_user($cnx, $_SESSION['user_id'], 20);
$favoris  = getUserFavorites($cnx, $_SESSION['user_id']);

define('IMG', 'https://image.tmdb.org/t/p/w300');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Felflix — Mes Recommandations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#141414; color:#fff; }
        .navbar-brand { color:#e50914 !important; font-weight:900; font-size:1.8rem; }
        .film-card { background:#1f1f1f; border:none; border-radius:8px; transition:transform .2s; }
        .film-card:hover { transform:scale(1.05); position:relative; z-index:10; }
        .film-img { height:250px; object-fit:cover; width:100%; border-radius:8px 8px 0 0; }
        .score-badge { background:#27ae60; position:absolute; top:8px; right:8px;
                       border-radius:12px; padding:3px 8px; font-size:.75rem; font-weight:bold; }
        .section-title { color:#e50914; border-left:4px solid #e50914; padding-left:12px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark px-4 py-3" style="background:#0d0d0d;">
    <a href="index.php" class="navbar-brand">🎬 FELFLIX</a>
    <div class="d-flex gap-2">
        <span class="text-muted small">👤 <?= htmlspecialchars($_SESSION['nom']) ?></span>
        <a href="Logout.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container-fluid px-4 py-4">

    <!-- RECOMMANDATIONS PERSONNALISÉES -->
    <h4 class="section-title mb-4">🤖 Recommandations personnalisées pour vous</h4>

    <?php if (empty($favoris)): ?>
        <div class="alert" style="background:#1f1f1f; color:#aaa; border:1px solid #333;">
            Vous n'avez pas encore de films favoris.<br>
            <a href="index.php" style="color:#e50914;">Explorez les films</a> et ajoutez-en à vos favoris pour recevoir des recommandations personnalisées !
        </div>
        <h5 class="text-muted mb-3">En attendant, voici les films les mieux notés :</h5>
    <?php endif; ?>

    <div class="row row-cols-2 row-cols-md-4 row-cols-lg-5 g-3 mb-5">
        <?php foreach ($recs as $rec):
            $f   = $rec['film'];
            $pct = $rec['score'] !== null ? round($rec['score'] * 100, 0) . '%' : null;
            $poster = $f['poster'] ? IMG . $f['poster'] : 'https://via.placeholder.com/300x420/1f1f1f/666?text=No+Image';
        ?>
        <div class="col">
            <div class="film-card position-relative">
                <?php if ($pct): ?>
                    <span class="score-badge"><?= $pct ?> match</span>
                <?php endif; ?>
                <a href="film_detail.php?id=<?= $f['id'] ?>" class="text-decoration-none text-white">
                    <img src="<?= htmlspecialchars($poster) ?>"
                         alt="<?= htmlspecialchars($f['title']) ?>"
                         class="film-img" loading="lazy">
                    <div class="p-2">
                        <p class="mb-0 small fw-bold" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($f['title']) ?>
                        </p>
                        <small class="text-muted">★ <?= number_format($f['rating'], 1) ?></small>
                    </div>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- MES FAVORIS -->
    <?php if (!empty($favoris)): ?>
    <h4 class="section-title mb-4">❤️ Mes films favoris (<?= count($favoris) ?>)</h4>
    <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
        <?php foreach ($favoris as $f):
            $poster = $f['poster'] ? IMG . $f['poster'] : 'https://via.placeholder.com/300x420/1f1f1f/666?text=No+Image';
        ?>
        <div class="col">
            <div class="film-card">
                <a href="film_detail.php?id=<?= $f['id'] ?>" class="text-decoration-none text-white">
                    <img src="<?= htmlspecialchars($poster) ?>"
                         alt="<?= htmlspecialchars($f['title']) ?>"
                         class="film-img" loading="lazy">
                    <div class="p-2">
                        <p class="mb-0 small fw-bold" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($f['title']) ?>
                        </p>
                        <small class="text-muted">★ <?= number_format($f['rating'], 1) ?></small>
                    </div>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
