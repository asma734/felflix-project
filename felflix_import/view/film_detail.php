<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: signup.php"); exit(); }

include(__DIR__ . "/../config/database.php");
include(__DIR__ . "/../controller/traitement.php");
include(__DIR__ . "/../ai/recommender.php");

$id   = intval($_GET['id'] ?? 0);
$film = getFilmById($cnx, $id);
if (!$film) { header("Location: index.php"); exit(); }

// Gérer ajout/suppression favori
if (isset($_POST['toggle_fav'])) {
    if (isFavorite($cnx, $_SESSION['user_id'], $id)) {
        removeFavorite($cnx, $_SESSION['user_id'], $id);
    } else {
        addFavorite($cnx, $_SESSION['user_id'], $id);
    }
    header("Location: film_detail.php?id=$id");
    exit();
}

$is_fav        = isFavorite($cnx, $_SESSION['user_id'], $id);
$recommandations = recommend_films($cnx, $id, 8);

define('IMG',   'https://image.tmdb.org/t/p/w300');
define('IMG_BG','https://image.tmdb.org/t/p/w780');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Felflix — <?= htmlspecialchars($film['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#141414; color:#fff; }
        .navbar-brand { color:#e50914 !important; font-weight:900; font-size:1.8rem; }
        .hero { background:linear-gradient(to right, #141414 40%, transparent),
                url('<?= $film['poster'] ? IMG_BG . $film['poster'] : '' ?>') center/cover no-repeat;
                min-height:400px; display:flex; align-items:center; padding:40px; }
        .badge-genre { background:#333; color:#ccc; margin-right:5px; font-size:.8rem; }
        .rating-star { color:#f5c518; font-size:1.2rem; }
        .film-card { background:#1f1f1f; border:none; border-radius:8px; transition:transform .2s; }
        .film-card:hover { transform:scale(1.05); }
        .film-img { height:220px; object-fit:cover; width:100%; border-radius:8px 8px 0 0; }
        .score-badge { background:#27ae60; position:absolute; top:8px; right:8px;
                       border-radius:12px; padding:3px 8px; font-size:.75rem; font-weight:bold; }
        .btn-red { background:#e50914; border:none; color:#fff; }
        .btn-red:hover { background:#c40812; color:#fff; }
        .section-title { color:#e50914; border-left:4px solid #e50914; padding-left:12px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-4 py-2" style="background:#0d0d0d;">
    <a href="index.php" class="navbar-brand">🎬 FELFLIX</a>
    <div class="d-flex gap-2">
        <a href="recommendations.php" class="btn btn-outline-light btn-sm">Mes recommandations</a>
        <a href="Logout.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div style="max-width:600px;">
        <h1 class="fw-bold mb-2"><?= htmlspecialchars($film['title']) ?></h1>
        <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
            <span class="rating-star">★ <?= number_format($film['rating'], 1) ?>/10</span>
            <span class="text-muted"><?= number_format($film['votes']) ?> votes</span>
            <span class="text-muted"><?= substr($film['release_date'] ?? '', 0, 4) ?></span>
            <?php if ($film['duration']): ?>
                <span class="text-muted"><?= $film['duration'] ?> min</span>
            <?php endif; ?>
        </div>

        <!-- Genres -->
        <?php foreach (genre_to_array($film['genre'] ?? '') as $g): ?>
            <a href="index.php?genre=<?= urlencode($g) ?>" class="badge badge-genre text-decoration-none">
                <?= htmlspecialchars($g) ?>
            </a>
        <?php endforeach; ?>

        <p class="mt-3" style="max-width:500px;color:#ccc;">
            <?= htmlspecialchars($film['description'] ?? '') ?>
        </p>

        <!-- Boutons -->
        <div class="d-flex gap-2 mt-3 flex-wrap">
            <?php if ($film['trailer']): ?>
                <a href="<?= htmlspecialchars($film['trailer']) ?>" target="_blank" class="btn btn-red">
                    ▶ Voir la bande-annonce
                </a>
            <?php endif; ?>

            <form method="POST">
                <button type="submit" name="toggle_fav"
                        class="btn <?= $is_fav ? 'btn-warning' : 'btn-outline-light' ?>">
                    <?= $is_fav ? '❤️ Retirer des favoris' : '🤍 Ajouter aux favoris' ?>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- RECOMMANDATIONS IA -->
<div class="container-fluid px-4 py-4">

    <?php if (!empty($recommandations)): ?>
    <h4 class="section-title mb-4">🤖 Films similaires recommandés par l'IA</h4>

    <div class="row row-cols-2 row-cols-md-4 row-cols-lg-8 g-3">
        <?php foreach ($recommandations as $rec):
            $f      = $rec['film'];
            $pct    = round($rec['score'] * 100, 0);
            $poster = $f['poster'] ? IMG . $f['poster'] : 'https://via.placeholder.com/300x420/1f1f1f/666?text=No+Image';
        ?>
        <div class="col">
            <div class="film-card position-relative">
                <span class="score-badge"><?= $pct ?>%</span>
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
