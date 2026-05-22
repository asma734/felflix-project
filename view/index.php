<?php
// ============================================================
//  VUE : Accueil (Fusion Felflix + omdb_website)
// ============================================================
session_start();
require_once '../config/database.php';
require_once '../controller/traitement.php';
require_once '../model/TitleModel.php';

$titleModel = new TitleModel();

// Récupération des données depuis la base omdb_website
$featured    = $titleModel->getFeatured();
$topMovies   = $titleModel->getTopMovies(8);
$topSeries   = $titleModel->getTopSeries(4);
$totalTitles = $cnx->query("SELECT COUNT(*) FROM titles")->fetchColumn();

// Films tunisiens locaux (table movies)
$localMovies = getTunisianMovies($cnx);

$pageTitle  = 'Felflix 🌶 — Premier site tunisien de films & séries';
$activePage = 'home';
require_once '_header.php';

// Genres mapping pour l'affichage
$GENRES = [
    'Action'    => '🔥 Action',
    'Horror'    => '👻 Horreur',
    'Sci-Fi'    => '🚀 Sci-Fi',
    'Comedy'    => '😂 Comédie',
    'Romance'   => '💕 Romance',
    'Drama'     => '🎭 Drame',
    'Animation' => '🌟 Animation',
    'Thriller'  => '🕵️ Thriller'
];
?>

<section class="hero">
  <div class="zellige-bg" style="position:absolute; inset:0; opacity: var(--zellige-opacity); pointer-events:none; z-index:0;"></div>
  <?php if($featured): ?>
    <div class="hero-bg" style="background-image: linear-gradient(rgba(var(--bg-rgb),0.7), rgba(var(--bg-rgb),0.9)), url('<?= h($featured['poster_url']) ?>'); background-size: cover; background-position: center; position: absolute; inset: 0; opacity: 0.3;"></div>
  <?php endif; ?>
  <div style="position:relative; z-index:1; text-align:center; max-width:850px">
    <div class="hero-tag">🌶 Marhbe bik fi Felflix &nbsp;|&nbsp; Premier site tunisien 🇹🇳</div>
    <h1 class="hero-title">Cinéma m7ar7ir <span class="hero-pepper">🌶</span><br/>kil felfil!</h1>
    <p class="hero-sub">L'expérience cinématique 100% tunisienne. <br/>Découvre, note et partage tes films préférés en derja.</p>
    <div class="hero-actions">
      <a href="search.php" class="btn-hero">🔍 Cherche un film</a>
      <a href="community.php" class="btn-ghost">💬 Rejoins la communauté</a>
    </div>
  </div>
</section>

<!-- TENDANCES (omdb_website) -->
<div class="wrap section animate-fade-up">
  <div class="sec-head">
    <div class="sec-title">🔥 <span class="accent">Tendances</span> (Top Aflam)</div>
    <a href="search.php" class="sec-more">Voir tout →</a>
  </div>
  <div class="sec-divider"></div>
  <div class="grid">
    <?php foreach($topMovies as $m): ?>
        <?php include __DIR__ . '/shared/_card.php'; ?>
    <?php endforeach; ?>
  </div>
</div>

<!-- FILMS TUNISIENS LOCAUX -->
<?php if (!empty($localMovies)): ?>
<div class="wrap section animate-fade-up">
  <div class="sec-head">
    <div class="sec-title">🇹🇳 <span class="accent">Films Tunisiens</span></div>
    <a href="tunisian.php" class="sec-more">Voir tout →</a>
  </div>
  <div class="sec-divider"></div>
  <div class="grid">
    <?php foreach (array_slice($localMovies, 0, 6) as $m): ?>
        <?php include __DIR__ . '/shared/_card.php'; ?>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- TENDANCES (omdb_website) -->

<!-- TENDANCES (omdb_website) -->
<div class="wrap section animate-fade-up">
  <div class="sec-head">
    <div class="sec-title">📺 <span class="accent">Mosalsalat</span> populaires</div>
    <a href="search.php?type=series" class="sec-more">Toutes les séries →</a>
  </div>
  <div class="sec-divider"></div>
  <div class="grid">
    <?php foreach($topSeries as $m): ?>
        <?php include __DIR__ . '/shared/_card.php'; ?>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once '_footer.php'; ?>
