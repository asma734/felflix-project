<?php
// ============================================================
//  Vue : Section Tunisienne — Films & Séries
//  Filtres : genre, type, saison, tranche d'âge, année
// ============================================================
session_start();
require_once '../controller/traitement.php';

// ── Paramètres de filtrage ────────────────────────────────────
$type     = in_array($_GET['type']   ?? '', ['movie','series','']) ? ($_GET['type'] ?? '') : '';
$genreId  = (int)($_GET['genre_id'] ?? 0);
$season   = in_array($_GET['season'] ?? '', ['spring','summer','autumn','winter','']) ? ($_GET['season'] ?? '') : '';
$age      = in_array($_GET['age']    ?? '', ['all','7+','13+','16+','18+','']) ? ($_GET['age'] ?? '') : '';
$yearFrom = (int)($_GET['year_from'] ?? 0);
$yearTo   = (int)($_GET['year_to']   ?? 0);
$q        = trim($_GET['q'] ?? '');

// ── Chargement des genres depuis la BDD ──────────────────────
$allGenres = getAllGenres($cnx);

// ── Films filtrés ─────────────────────────────────────────────
$filters = ['tunisian_only' => true];
if ($type)     $filters['type']      = $type;
if ($genreId)  $filters['genre_id']  = $genreId;
if ($season)   $filters['season']    = $season;
if ($age)      $filters['age']       = $age;
if ($yearFrom) $filters['year_from'] = $yearFrom;
if ($yearTo)   $filters['year_to']   = $yearTo;
if ($q)        $filters['q']         = $q;

$movies = getMoviesFiltered($cnx, $filters);

// ── Labels des filtres ────────────────────────────────────────
$SEASONS = [
    'spring' => ['label'=>'🌸 Printemps', 'color'=>'#22c55e'],
    'summer' => ['label'=>'☀️ Été',        'color'=>'#f59e0b'],
    'autumn' => ['label'=>'🍂 Automne',    'color'=>'#f97316'],
    'winter' => ['label'=>'❄️ Hiver',      'color'=>'#0ea5e9'],
];
$AGES = ['all'=>'👶 Tout âge','7+'=>'7+','13+'=>'13+','16+'=>'16+','18+'=>'🔞 18+'];

$pageTitle  = 'Cinéma Tunisien — Felflix 🌶';
$activePage = 'tunisian';
require_once '_header.php';
?>

<style>
.filter-bar{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px 24px;margin-bottom:28px}
.filter-section{margin-bottom:16px}
.filter-label{font-size:.72rem;font-weight:700;color:var(--dim);letter-spacing:2px;text-transform:uppercase;margin-bottom:8px}
.fpill{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:20px;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;background:var(--glass);border:1px solid var(--border);color:var(--muted);margin:3px}
.fpill:hover,.fpill.active{background:rgba(230,57,70,.14);border-color:rgba(230,57,70,.5);color:#fff}
.fpill.season-spring.active{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.5);color:#86efac}
.fpill.season-summer.active{background:rgba(245,158,11,.15);border-color:rgba(245,158,11,.5);color:#fde68a}
.fpill.season-autumn.active{background:rgba(249,115,22,.15);border-color:rgba(249,115,22,.5);color:#fed7aa}
.fpill.season-winter.active{background:rgba(14,165,233,.15);border-color:rgba(14,165,233,.5);color:#bae6fd}
.results-count{color:var(--dim);font-size:.82rem;margin-bottom:16px}
.actor-pill{background:var(--glass);border:1px solid var(--border);border-radius:20px;padding:3px 10px;font-size:.72rem;color:var(--muted);text-decoration:none;transition:all .15s;display:inline-block;margin:2px}
.actor-pill:hover{border-color:var(--red);color:#fff}
.mcard-type-badge{position:absolute;top:8px;right:8px;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);border-radius:6px;padding:3px 8px;font-size:.65rem;font-weight:700;color:#fff}
.movie-badge{background:rgba(230,57,70,.8)}
.series-badge{background:rgba(124,58,237,.8)}
</style>

<div class="wrap" style="padding-top:90px;padding-bottom:60px">

  <!-- Titre -->
  <div class="sec-head" style="margin-bottom:6px">
    <div class="sec-title">🇹🇳 <span class="accent">Cinéma Tunisien</span></div>
  </div>
  <p style="color:var(--muted);font-size:.88rem;margin-bottom:24px">Films & Séries made in Tunisia 🌶</p>

  <!-- ── BARRE DE FILTRES ── -->
  <form method="GET" class="filter-bar">

    <!-- Recherche -->
    <div class="filter-section" style="display:flex;gap:10px;margin-bottom:20px">
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>"
             placeholder="🔍 Cherche un film, une série, un acteur..."
             style="flex:1;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 14px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;font-size:.88rem"
             onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
      <button type="submit" class="btn-hero btn-sm">Rechercher</button>
      <?php if ($q||$type||$genreId||$season||$age||$yearFrom): ?>
        <a href="tunisian.php" class="btn-ghost btn-sm">✕ Reset</a>
      <?php endif; ?>
    </div>

    <!-- Type : Film / Série -->
    <div class="filter-section">
      <div class="filter-label">Type</div>
      <a href="?<?= http_build_query(array_merge($_GET,['type'=>''])) ?>" class="fpill <?= !$type ? 'active' : '' ?>">🎬 Tout</a>
      <a href="?<?= http_build_query(array_merge($_GET,['type'=>'movie'])) ?>" class="fpill <?= $type==='movie' ? 'active' : '' ?>">🎥 Films</a>
      <a href="?<?= http_build_query(array_merge($_GET,['type'=>'series'])) ?>" class="fpill <?= $type==='series' ? 'active' : '' ?>">📺 Séries</a>
    </div>

    <!-- Genres (28 genres) -->
    <div class="filter-section">
      <div class="filter-label">Genre</div>
      <a href="?<?= http_build_query(array_merge($_GET,['genre_id'=>''])) ?>" class="fpill <?= !$genreId ? 'active' : '' ?>">Tous</a>
      <?php foreach ($allGenres as $g): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['genre_id'=>$g['id']])) ?>"
           class="fpill <?= $genreId==$g['id'] ? 'active' : '' ?>">
          <?= htmlspecialchars($g['icon']??'') ?> <?= htmlspecialchars($g['name']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Saison -->
    <div class="filter-section">
      <div class="filter-label">Saison / Ambiance</div>
      <a href="?<?= http_build_query(array_merge($_GET,['season'=>''])) ?>" class="fpill <?= !$season ? 'active' : '' ?>">🌍 Toutes</a>
      <?php foreach ($SEASONS as $k=>$v): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['season'=>$k])) ?>"
           class="fpill season-<?= $k ?> <?= $season===$k ? 'active' : '' ?>">
          <?= $v['label'] ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Tranche d'âge -->
    <div class="filter-section">
      <div class="filter-label">Tranche d'âge</div>
      <a href="?<?= http_build_query(array_merge($_GET,['age'=>''])) ?>" class="fpill <?= !$age ? 'active' : '' ?>">Tout public</a>
      <?php foreach ($AGES as $k=>$v): ?>
        <a href="?<?= http_build_query(array_merge($_GET,['age'=>$k])) ?>"
           class="fpill <?= $age===$k ? 'active' : '' ?>">
          <?= htmlspecialchars($v) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Année (range) -->
    <div class="filter-section" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <div class="filter-label" style="margin-bottom:0;width:100%">Année</div>
      <label style="color:var(--muted);font-size:.78rem">De :</label>
      <select name="year_from" style="background:var(--card2);border:1px solid var(--border);color:#fff;padding:6px 12px;border-radius:8px;font-size:.8rem;outline:none">
        <option value="">—</option>
        <?php for ($y=1960; $y<=2024; $y++): ?>
          <option value="<?= $y ?>" <?= $yearFrom==$y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <label style="color:var(--muted);font-size:.78rem">À :</label>
      <select name="year_to" style="background:var(--card2);border:1px solid var(--border);color:#fff;padding:6px 12px;border-radius:8px;font-size:.8rem;outline:none">
        <option value="">—</option>
        <?php for ($y=1960; $y<=2024; $y++): ?>
          <option value="<?= $y ?>" <?= $yearTo==$y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button type="submit" class="btn-ghost btn-sm" style="font-size:.78rem">Filtrer</button>
    </div>

  </form>

  <!-- Nombre de résultats -->
  <p class="results-count">
    <?= count($movies) ?> résultat(s)
    <?php if ($type): ?> · <?= $type==='movie' ? '🎥 Films' : '📺 Séries' ?><?php endif; ?>
    <?php if ($season && isset($SEASONS[$season])): ?> · <?= $SEASONS[$season]['label'] ?><?php endif; ?>
    <?php if ($age): ?> · <?= htmlspecialchars($AGES[$age] ?? $age) ?><?php endif; ?>
    <?php if ($q): ?> · "<?= htmlspecialchars($q) ?>"<?php endif; ?>
  </p>

  <!-- Grille de films/séries -->
  <?php if (empty($movies)): ?>
    <div class="empty-s">
      <span class="ei">🇹🇳</span>
      <p>Mafammach résultats — essaie d'autres filtres 🌶</p>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($movies as $m):
        $mGenres  = getGenresByMovie($cnx, $m['id']);
        $mActors  = getActorsByMovie($cnx, $m['id']);
        $isSeries = ($m['type'] ?? 'movie') === 'series';
      ?>
        <a href="detail_local.php?id=<?= $m['id'] ?>" class="mcard">
          <div class="mcard-poster">
            <?php if (!empty($m['poster_url'])): ?>
              <img src="<?= htmlspecialchars($m['poster_url']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" loading="lazy">
            <?php else: ?>
              <div class="mcard-poster-empty"
                   style="background:<?= htmlspecialchars($m['bg_color']??'#e6394633') ?>;font-size:3.5rem">
                <?= htmlspecialchars($m['emoji']??'🎬') ?>
              </div>
            <?php endif; ?>
            <!-- Badge type -->
            <span class="mcard-type-badge <?= $isSeries ? 'series-badge' : 'movie-badge' ?>">
              <?= $isSeries ? '📺 Série' : '🎥 Film' ?>
            </span>
            <!-- Badge saison -->
            <?php if (!empty($m['season']) && $m['season'] !== 'all' && isset($SEASONS[$m['season']])): ?>
              <span style="position:absolute;bottom:8px;left:8px;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);border-radius:6px;padding:2px 7px;font-size:.6rem;color:#fff">
                <?= $SEASONS[$m['season']]['label'] ?>
              </span>
            <?php endif; ?>
            <!-- Badge âge -->
            <?php if (!empty($m['age_rating']) && $m['age_rating'] !== 'all'): ?>
              <span style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.7);border-radius:6px;padding:2px 7px;font-size:.6rem;font-weight:700;color:#fff">
                <?= htmlspecialchars($m['age_rating']) ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="mcard-body">
            <div class="mcard-title"><?= htmlspecialchars($m['title']) ?></div>
            <div class="mcard-meta">
              <span class="mcard-rating">⭐ <?= $m['rating'] ?></span>
              <span class="mcard-year"><?= $m['year'] ?></span>
              <?php if ($isSeries && !empty($m['nb_seasons'])): ?>
                <span style="font-size:.65rem;color:var(--dim)"><?= $m['nb_seasons'] ?> saison(s)</span>
              <?php endif; ?>
            </div>
            <!-- Genres -->
            <?php if (!empty($mGenres)): ?>
              <div style="display:flex;gap:3px;flex-wrap:wrap;margin-bottom:5px">
                <?php foreach (array_slice($mGenres, 0, 2) as $g): ?>
                  <span style="font-size:.6rem;background:rgba(255,255,255,.06);border-radius:5px;padding:1px 6px;color:var(--dim)">
                    <?= htmlspecialchars($g['icon']??'') ?> <?= htmlspecialchars($g['name']) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <!-- Acteurs cliquables -->
            <?php if (!empty($mActors)): ?>
              <div style="display:flex;gap:3px;flex-wrap:wrap">
                <?php foreach (array_slice($mActors, 0, 2) as $a): ?>
                  <a href="actor.php?id=<?= $a['id'] ?>" class="actor-pill"
                     onclick="event.preventDefault();event.stopPropagation();window.location='actor.php?id=<?= $a['id'] ?>'">
                    <?= htmlspecialchars($a['name']) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <button class="mcard-btn" style="margin-top:8px">Voir + 🌶</button>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once '_footer.php'; ?>
