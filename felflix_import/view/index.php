<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: signup.php"); exit(); }

include(__DIR__ . "/../config/database.php");
include(__DIR__ . "/../controller/traitement.php");

// Recherche ou liste complète
$search = trim($_GET['search'] ?? '');
$genre  = trim($_GET['genre']  ?? '');

if ($search) {
    $films = searchFilms($cnx, $search);
} elseif ($genre) {
    $films = getFilmsByGenre($cnx, $genre);
} else {
    $films = getAllFilms($cnx, 40);
}

$total = countFilms($cnx);

// Liste des genres pour le filtre
$genres_list = ['Action','Adventure','Animation','Comedy','Crime',
                'Documentary','Drama','Family','Fantasy','Horror',
                'Music','Mystery','Romance','Science Fiction','Thriller','War'];

define('IMG', 'https://image.tmdb.org/t/p/w300');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Felflix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#141414; color:#fff; }
        .navbar-brand { color:#e50914 !important; font-weight:900; font-size:1.8rem; }
        .film-card { background:#1f1f1f; border:none; border-radius:8px; transition:transform .2s; height:100%; }
        .film-card:hover { transform:scale(1.04); z-index:10; position:relative; }
        .film-img { height:280px; object-fit:cover; border-radius:8px 8px 0 0; width:100%; }
        .badge-genre { background:#e50914; font-size:.7rem; }
        .rating-star { color:#f5c518; }
        .search-bar { background:#2a2a2a; border:1px solid #444; color:#fff; }
        .search-bar:focus { background:#2a2a2a; color:#fff; border-color:#e50914; box-shadow:none; }
        .btn-red { background:#e50914; border:none; color:#fff; }
        .btn-red:hover { background:#c40812; color:#fff; }
        .genre-pill { background:#2a2a2a; color:#ccc; border:1px solid #444; border-radius:20px;
                      padding:4px 12px; font-size:.8rem; cursor:pointer; text-decoration:none; }
        .genre-pill:hover, .genre-pill.active { background:#e50914; color:#fff; border-color:#e50914; }
        .stat-badge { background:#e50914; color:#fff; padding:4px 10px; border-radius:20px; font-size:.85rem; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-4 py-3" style="background:#0d0d0d;">
    <a class="navbar-brand">🎬 FELFLIX</a>
    <div class="d-flex gap-2 align-items-center">
        <span class="stat-badge"><?= number_format($total) ?> films</span>
        <a href="user_list.php" class="btn btn-outline-light btn-sm">Admin</a>
        <span class="text-muted small">👤 <?= htmlspecialchars($_SESSION['nom']) ?></span>
        <a href="Logout.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container-fluid px-4 py-4">

    <!-- BARRE DE RECHERCHE -->
    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width:600px; margin:0 auto;">
            <input type="text" name="search" class="form-control search-bar"
                   placeholder="🔍 Rechercher un film, genre..."
                   value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-red">Chercher</button>
            <?php if ($search || $genre): ?>
                <a href="index.php" class="btn btn-outline-secondary">✕ Reset</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- FILTRES PAR GENRE -->
    <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
        <?php foreach ($genres_list as $g): ?>
            <a href="?genre=<?= urlencode($g) ?>"
               class="genre-pill <?= $genre === $g ? 'active' : '' ?>">
                <?= $g ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- TITRE DE SECTION -->
    <h5 class="mb-3 text-muted">
        <?php if ($search): ?>
            Résultats pour "<strong class="text-white"><?= htmlspecialchars($search) ?></strong>" — <?= count($films) ?> films trouvés
        <?php elseif ($genre): ?>
            Genre : <strong class="text-white"><?= htmlspecialchars($genre) ?></strong>
        <?php else: ?>
            🔥 Films populaires
        <?php endif; ?>
    </h5>

    <!-- GRILLE DE FILMS -->
    <?php if (empty($films)): ?>
        <div class="text-center py-5 text-muted">Aucun film trouvé.</div>
    <?php else: ?>
    <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
        <?php foreach ($films as $film):
            $poster = $film['poster']
                ? IMG . $film['poster']
                : 'https://via.placeholder.com/300x420/1f1f1f/666?text=No+Image';
        ?>
        <div class="col">
            <div class="film-card">
                <a href="film_detail.php?id=<?= $film['id'] ?>" class="text-decoration-none text-white">
                    <img src="<?= htmlspecialchars($poster) ?>"
                         alt="<?= htmlspecialchars($film['title']) ?>"
                         class="film-img"
                         loading="lazy">
                    <div class="p-2">
                        <p class="mb-1 small fw-bold" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($film['title']) ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="rating-star small">★ <?= number_format($film['rating'], 1) ?></span>
                            <span class="text-muted" style="font-size:.75rem;">
                                <?= substr($film['release_date'] ?? '', 0, 4) ?>
                            </span>
                        </div>
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
