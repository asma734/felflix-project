<?php
session_start();
require_once '../config/database.php';
$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: login.php');
    exit;
}

// ── Suppression d'un piment (historique) ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_pepper_id'])) {
    $delId = (int)$_POST['delete_pepper_id'];
    $delStmt = $cnx->prepare("DELETE FROM watch_history WHERE id = ? AND user_id = ?");
    $delStmt->execute([$delId, $user['id']]);
    header('Location: mood_jar.php');
    exit;
}

// Fetch all moods
$moods = $cnx->query("SELECT * FROM moods")->fetchAll();

// User history
$historyStmt = $cnx->prepare("SELECT w.*, m.name as mood_name, m.color as mood_color, m.icon as mood_icon, m.tone, m.emotional_intensity FROM watch_history w JOIN moods m ON w.mood_id = m.id WHERE w.user_id = ? ORDER BY w.added_at ASC");
$historyStmt->execute([$user['id']]);
$history = $historyStmt->fetchAll();

// --- IA Recommendation BFS ---
require_once '../model/RecommendationModel.php';
$recoModel = new RecommendationModel();
$bfsRecos = [];
if (!empty($history)) {
    $lastMovie = end($history);
    // On utilise tmdb_id car c'est notre clé universelle (numérique ou tt...)
    if (!empty($lastMovie['tmdb_id']) && str_starts_with($lastMovie['tmdb_id'], 'tt')) {
        $bfsRecos = $recoModel->getRecommendationsBFS($lastMovie['tmdb_id'], 6);
    }
}

// Map mood names — nouveaux noms
$reactMoodsMap = [
    '7zin'       => ['id'=>'7zin',       'emoji'=>'🫙',    'image'=>'7zin.png',     'glowHsl'=>'220, 70%, 55%'],
    'te3eb'      => ['id'=>'te3eb',      'emoji'=>'😮‍💨', 'image'=>'te3ben.png',   'glowHsl'=>'240, 8%, 55%'],
    'far7an'     => ['id'=>'far7an',     'emoji'=>'🌶️',   'image'=>'far7an.png',   'glowHsl'=>'142, 70%, 50%'],
    'excited'    => ['id'=>'excited',    'emoji'=>'⚡',    'image'=>'heyej.png',    'glowHsl'=>'50, 100%, 55%'],
    'tamou7'     => ['id'=>'tamou7',     'emoji'=>'🔥',    'image'=>'motive.png',   'glowHsl'=>'25, 95%, 55%'],
    'met8achech' => ['id'=>'met8achech', 'emoji'=>'💢',    'image'=>'meta.png',     'glowHsl'=>'0, 85%, 55%'],
    'neutre'     => ['id'=>'neutre',     'emoji'=>'🌅',    'image'=>'tfakkart.png', 'glowHsl'=>'35, 85%, 60%'],
    '5ayef'      => ['id'=>'5ayef',      'emoji'=>'💫',    'image'=>'5ayef.png',    'glowHsl'=>'185, 80%, 55%'],
    'roumansi'    => ['id'=>'roumansi',    'emoji'=>'💖',    'image'=>'romansi.png',  'glowHsl'=>'330, 85%, 65%'],
];

// Normalisation anciens noms BDD → nouveaux noms
$moodNormMap = [
    'te3ben'        => 'te3eb',
    'heyej'         => 'excited',
    'motivé'        => 'tamou7',
    'motive'        => 'tamou7',
    'meta'          => 'met8achech',
    'metghachchech' => 'met8achech',
    'tfakkart'      => 'neutre',
    'romansi'        => 'roumansi',
];

$peppers = [];
$counts = [];
foreach ($moods as $m) $counts[$m['name']] = 0;

$lastWeekCount = 0;
$weekAgo = strtotime("-7 days");
$lastMonthCount = 0;
$monthAgo = strtotime("-30 days");
$lastMonthItems = [];

function formatFrenchDate($dateString) {
    if (!$dateString) return '';
    $days = ['dim.','lun.','mar.','mer.','jeu.','ven.','sam.'];
    $months = ['','janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
    $ts = strtotime($dateString);
    $w = date('w', $ts);
    $d = date('j', $ts);
    $m = date('n', $ts);
    return $days[$w] . ' ' . $d . ' ' . $months[$m];
}


foreach ($history as $h) {
    // Normalise ancien nom BDD → nouveau nom
    $logicalName = strtolower(trim($h['mood_name']));
    $logicalName = $moodNormMap[$logicalName] ?? $logicalName;

    $mapped = $reactMoodsMap[$logicalName] ?? ['id'=>'far7an', 'image'=>'far7an.png', 'glowHsl'=>'142, 70%, 50%', 'emoji'=>'🌶️'];

    if (strtotime($h['added_at']) >= $weekAgo) {
        $lastWeekCount++;
    }
    if (strtotime($h['added_at']) >= $monthAgo) {
        $lastMonthCount++;
        $lastMonthItems[] = $h;
    }

    $peppers[] = [
        'id' => $h['id'],
        'mood_id' => $mapped['id'],
        'mood_name' => $h['mood_name'],
        'icon' => $h['mood_icon'],
        'color' => $h['mood_color'],
        'image' => $mapped['image'],
        'glowHsl' => $mapped['glowHsl'],
        'emoji' => $mapped['emoji'],
        'title' => htmlspecialchars($h['tmdb_title'] ?? 'Film'),
        'date' => date('d M Y', strtotime($h['added_at'])),
        'intensity' => $h['emotional_intensity']
    ];
    $counts[$h['mood_name']]++;
}

$dominantMood = '';
if (!empty($history)) {
    arsort($counts);
    $dominantMood = array_key_first($counts);
}

$domLogical = strtolower(trim($dominantMood));
$domLogical = $moodNormMap[$domLogical] ?? $domLogical;
$domReact = $reactMoodsMap[$domLogical] ?? ['id'=>'far7an', 'image'=>'far7an.png', 'glowHsl'=>'142, 70%, 50%', 'emoji'=>'🌶️'];

// ── Mapping Mood → Genres préférés (nouveaux noms) ─────────────────
$moodGenreMap = [
    '7zin'       => ['Drama', 'Romance'],
    'te3eb'      => ['Comedy', 'Animation'],
    'far7an'     => ['Comedy', 'Adventure', 'Family'],
    'excited'    => ['Action', 'Thriller', 'Crime', 'Adventure'],
    'tamou7'     => ['Action', 'Adventure', 'Biography'],
    'met8achech' => ['Action', 'Crime', 'Thriller'],
    'neutre'     => ['Drama', 'Romance', 'History'],
    '5ayef'      => ['Horror', 'Mystery', 'Thriller'],
    'roumansi'    => ['Romance', 'Drama'],
];
$preferredGenres = $moodGenreMap[$domLogical] ?? [];
$moodEmojiIcon   = $domReact['emoji'] ?? '🎬';

// ── Enrichir BFS avec les genres + tri par mood ─────────────────
if (!empty($bfsRecos) && !empty($preferredGenres)) {
    foreach ($bfsRecos as &$r) {
        try {
            $gSt = $cnx->prepare('SELECT g.name FROM title_genres tg JOIN genres g ON tg.genre_id = g.id WHERE tg.imdb_id = ?');
            $gSt->execute([$r['imdb_id']]);
            $r['genres_list'] = $gSt->fetchAll(PDO::FETCH_COLUMN);
            $r['mood_match']  = count(array_intersect($r['genres_list'], $preferredGenres));
        } catch (Throwable $e) { $r['mood_match'] = 0; $r['genres_list'] = []; }
    }
    unset($r);
    usort($bfsRecos, fn($a, $b) => $b['mood_match'] <=> $a['mood_match']);
}

// (DFS supprimé selon ta demande, on garde uniquement BFS)

$pageTitle = "Mood Jar — Felflix 🌶";
$activePage = 'moodjar';
require_once '_header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  :root {
      --clr-background: #050308;
      --clr-card: rgba(20, 15, 25, 0.6);
      --clr-border: rgba(255, 255, 255, 0.05);
      --clr-text-muted: #a1a1aa;
      --clr-primary: #e63946;
  }
  .mj-body {
      background: radial-gradient(ellipse 60% 50% at 50% 10%, rgba(140, 20, 35, 0.35), var(--clr-background) 70%);
      min-height: 100vh;
      color: #fff;
      font-family: 'Inter', sans-serif;
      padding-top: 100px;
      padding-bottom: 80px;
  }
  .mj-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 0 20px;
  }
  
  /* Utilities */
  .mj-glass-card {
      background: var(--clr-card);
      border: 1px solid var(--clr-border);
      border-radius: 1.5rem;
      backdrop-filter: blur(16px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      padding: 1.5rem;
  }
  .mj-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 16px;
      border-radius: 9999px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--clr-text-muted);
  }
  .mj-title {
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 800;
  }
  .text-fire {
      background: linear-gradient(90deg, #ff416c, #ff4b2b);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
  }
  .text-glow { filter: drop-shadow(0 0 10px rgba(255, 65, 108, 0.6)); }

  /* Hero Section */
  .mj-hero {
      text-align: center;
      margin-bottom: 3rem;
  }
  .mj-hero h1 {
      font-size: clamp(2.2rem, 8vw, 3.5rem);
      margin: 15px 0;
      animation: fadeInUp 0.8s ease-out;
  }
  .mj-hero p {
      color: var(--clr-text-muted);
      font-size: clamp(0.9rem, 3vw, 1.1rem);
      padding: 0 15px;
  }

  /* Jar Section */
  .mj-jar-area {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 3rem;
  }
  .mj-jar-wrapper {
      position: relative;
      width: min(320px, 85vw);
      aspect-ratio: 320 / 460;
      margin: 0 auto;
  }
  .mj-jar-image {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: contain;
      pointer-events: none;
      z-index: 10;
      filter: drop-shadow(0 30px 60px rgba(230, 57, 70, 0.45));
  }
  .mj-peppers-container {
      position: absolute;
      left: 10%;
      right: 10%;
      top: 22%;
      bottom: 8%;
  }
  .mj-pepper {
      position: absolute;
      cursor: pointer;
      transition: all 0.5s ease;
      z-index: 15;
      transform-origin: center;
      display: flex;
      justify-content: center;
      align-items: center;
  }
  .mj-pepper img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      transition: transform 0.3s;
  }
  .mj-pepper:hover {
      z-index: 30;
  }
  .mj-pepper:hover img {
      transform: scale(1.5) !important;
  }
  .mj-pepper-tooltip {
      position: absolute;
      bottom: 110%;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(0,0,0,0.85);
      border: 1px solid rgba(255,255,255,0.1);
      backdrop-filter: blur(5px);
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 0.75rem;
      color: #fff;
      opacity: 0;
      pointer-events: none;
      white-space: nowrap;
      transition: opacity 0.3s;
      z-index: 40;
  }
  .mj-pepper:hover .mj-pepper-tooltip {
      opacity: 1;
  }
  .mj-pepper-tooltip-title { font-weight: 600; margin-bottom: 3px; font-size: 0.8rem; }
  .mj-pepper-tooltip-date { color: var(--clr-text-muted); font-size: 0.7rem; }

  @keyframes dropIntoJar {
      0% { transform: translateY(-400px) rotate(-180deg); opacity: 0; }
      50% { transform: translateY(15px) rotate(calc(var(--r) + 20deg)); opacity: 1; }
      75% { transform: translateY(-10px) rotate(calc(var(--r) - 10deg)); }
      100% { transform: translateY(0) rotate(var(--r)); opacity: 1; }
  }

  @keyframes floatAnim {
      0% { transform: translateY(0) rotate(var(--r)); }
      100% { transform: translateY(-15px) rotate(calc(var(--r) + 5deg)); }
  }
  @keyframes fadeInUp {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
  }
  
  /* Filter Pills */
  .mj-filters {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px;
      margin-bottom: 3rem;
  }
  .mj-filter-btn {
      padding: 8px 16px;
      border-radius: 9999px;
      font-size: 0.8rem;
      font-weight: 600;
      background: transparent;
      border: 1px solid var(--clr-border);
      color: var(--clr-text-muted);
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.3s ease;
  }
  .mj-filter-btn:hover { border-color: rgba(255,255,255,0.3); }
  .mj-filter-btn.active {
      background: rgba(255,255,255,0.1);
      border-color: rgba(255,255,255,0.5);
      color: #fff;
  }

  /* Grid Layouts */
  .mj-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
  }
  @media (max-width: 768px) {
      .mj-grid-2 { grid-template-columns: 1fr; }
      .mj-hero h1 { font-size: 2.5rem; }
  }

  /* Dominant Banner */
  .mj-dominant {
      background: linear-gradient(135deg, hsl(var(--dom-h), var(--dom-s), var(--dom-l), 0.15) 0%, rgba(20,15,25,0.8) 100%);
      border-color: hsla(var(--dom-h), var(--dom-s), var(--dom-l), 0.3);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
      padding: 2.5rem;
      border-radius: 2rem;
      margin-bottom: 24px;
  }
  .mj-dominant-content h3 {
      font-size: 2.8rem;
      margin: 0 0 10px 0;
      color: hsla(var(--dom-h), var(--dom-s), var(--dom-l), 1);
      text-shadow: 0 0 20px hsla(var(--dom-h), var(--dom-s), var(--dom-l), 0.5);
  }
  .mj-btn-ask {
      background: linear-gradient(90deg, #ff416c, #ff4b2b);
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 0 20px rgba(255, 65, 108, 0.4);
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
  }
  .mj-btn-ask:hover {
      box-shadow: 0 0 30px rgba(255, 65, 108, 0.7);
      transform: translateY(-2px);
  }
</style>

<div class="mj-body">
    <div class="mj-container">
        <!-- Hero Title -->
        <div class="mj-hero">
            <div class="mj-badge">✨ Mood Jar · ADN émotionnel</div>
            <h1 class="mj-title">Ton bocal de <span class="text-fire text-glow">piments</span></h1>
            <p>Chaque film ajouté à ta watchlist = un piment qui rejoint ton bocal.</p>
        </div>

        <!-- Jar Visualization -->
        <div class="mj-jar-area">
            <div class="mj-jar-wrapper">
                <img src="<?=$base?>/assets/img/moodjar/jar.png" class="mj-jar-image" alt="Jar Glass">
                <div class="mj-peppers-container" id="peppersContainer">
                    <!-- Injected via JS -->
                </div>
            </div>
            <div style="text-align:center; margin-top: 1rem;">
                <div class="mj-title text-fire" style="font-size:1.8rem;"><?=count($peppers)?> piments</div>
                <div style="color:var(--clr-text-muted); font-size:0.9rem;">collectés au total</div>
            </div>
        </div>

        <!-- Filters (JS connected) -->
        <div class="mj-filters" id="jarFilters">
            <button class="mj-filter-btn active" data-mood="all">Tous</button>
            <?php foreach($reactMoodsMap as $key => $m): ?>
                <button class="mj-filter-btn" data-mood="<?=$m['id']?>" style="border-color: hsl(<?=$m['glowHsl']?>); color: hsl(<?=$m['glowHsl']?>);">
                   <img src="<?=$base?>/assets/img/moodjar/<?=$m['image']?>" style="width:18px;height:18px;object-fit:contain; filter:drop-shadow(0 0 4px hsl(<?=$m['glowHsl']?>));">
                   <?=ucfirst($key)?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Recent Month Banner & History -->
        <div class="mj-glass-card" style="margin-bottom: 24px; display:flex; flex-direction:column; gap: 20px;">
            <div style="display:flex; align-items:center; gap: 15px;">
                <div style="width: 48px; height:48px; border-radius:12px; background: linear-gradient(135deg, #ff416c, #ff4b2b); display:flex; align-items:center; justify-content:center; font-size:1.5rem;">📅</div>
                <div>
                    <h2 class="mj-title" style="margin:0; font-size:1.5rem;">Ce mois-ci</h2>
                    <p style="margin:0; color:var(--clr-text-muted); font-size:0.9rem;">
                        <?=$lastMonthCount > 0 ? "{$lastMonthCount} film(s) regardé(s) ces 30 derniers jours" : "Le bocal de ce mois est vide — ajoute un film !"?>
                    </p>
                </div>
            </div>
            
            <?php if($lastMonthCount > 0): ?>
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px;">
                <?php foreach(array_reverse($lastMonthItems) as $item): 
                    $logical = strtolower(trim($item['mood_name']));
                    if ($logical === 'metghachchech') $logical = 'meta';
                    $map = $reactMoodsMap[$logical] ?? ['id'=>'far7an', 'image'=>'far7an.png', 'glowHsl'=>'142, 70%, 50%', 'emoji'=>'🌶️'];
                    $colorHsl = $map['glowHsl'];
                    $title = htmlspecialchars($item['tmdb_title'] ?? 'Film');
                    $shortTitle = mb_strlen($title) > 15 ? mb_substr($title, 0, 13).'...' : $title;
                ?>
                <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 12px; border-radius: 12px; display: flex; align-items: center; gap: 12px; min-width: 200px; flex: 1; max-width: 250px; position: relative;">
                    <div style="width: 40px; height: 40px; border-radius: 8px; background: linear-gradient(135deg, hsl(<?=$colorHsl?>), #222); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; box-shadow: 0 4px 10px hsla(<?=$colorHsl?>, 0.3);">
                        <?=$map['emoji']?>
                    </div>
                    <div style="flex:1; overflow:hidden;">
                        <div style="font-weight: 700; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff;" title="<?=$title?>"><?=$shortTitle?></div>
                        <div style="color: var(--clr-text-muted); font-size: 0.75rem; margin-top:2px;"><?=formatFrenchDate($item['added_at'])?></div>
                    </div>
                    <div style="background: hsla(<?=$colorHsl?>, 0.15); border: 1px solid hsla(<?=$colorHsl?>, 0.4); color: hsl(<?=$colorHsl?>); padding: 4px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                        <img src="<?=$base?>/assets/img/moodjar/<?=$map['image']?>" style="width:12px; height:12px; object-fit:contain; filter:drop-shadow(0 0 2px hsl(<?=$colorHsl?>));" />
                        <?=ucfirst($item['mood_name'])?>
                    </div>
                    <!-- Bouton pour supprimer le piment -->
                    <form method="post" style="margin:0; position:absolute; top:-6px; right:-6px;" onsubmit="return confirm('Tu es sûr de vouloir supprimer ce piment de ton jar ?');">
                        <input type="hidden" name="delete_pepper_id" value="<?=$item['id']?>">
                        <button type="submit" style="background:#e63946; color:#fff; border:1px solid #ff4b2b; border-radius:50%; width:24px; height:24px; cursor:pointer; font-weight:bold; display:flex; align-items:center; justify-content:center; font-size:1rem; box-shadow:0 2px 8px rgba(0,0,0,0.6); line-height: 1;" title="Supprimer ce film">×</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Charts Grid -->
        <div class="mj-grid-2">
            <div class="mj-glass-card">
                <h3 class="mj-title" style="font-size:1.2rem; margin-bottom:5px;">📈 Évolution émotionnelle</h3>
                <p style="color:var(--clr-text-muted); font-size:0.75rem; margin-bottom:20px;">Intensité 0-10 par film visionné</p>
                <canvas id="moodLineChart" height="220"></canvas>
            </div>
            <div class="mj-glass-card">
                <h3 class="mj-title" style="font-size:1.2rem; margin-bottom:5px;">🥧 Distribution des moods</h3>
                <p style="color:var(--clr-text-muted); font-size:0.75rem; margin-bottom:20px;">Répartition globale</p>
                <canvas id="moodPieChart" height="220"></canvas>
            </div>
        </div>

        <!-- ══ BFS : Recommandations niveau par niveau ══ -->
        <?php if (!empty($bfsRecos)): ?>
        <div class="mj-glass-card" style="margin-bottom: 24px;">
            <div style="display:flex; align-items:center; gap:15px; margin-bottom:18px; flex-wrap:wrap;">
                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:1.5rem;">🌐</div>
                <div style="flex:1;">
                    <h2 class="mj-title" style="margin:0;font-size:1.4rem;">BFS — <span style="color:#a5b4fc;">Exploration niveau par niveau</span></h2>
                    <p style="margin:4px 0 0;color:var(--clr-text-muted);font-size:.8rem;">
                        À partir de ton dernier film regardé · mood
                        <?php if($dominantMood): ?>
                            <strong style="color:#fff;"><?= htmlspecialchars(ucfirst($dominantMood)) ?></strong> <?= htmlspecialchars($moodEmojiIcon) ?>
                            <?php foreach($preferredGenres as $pg): ?><span style="display:inline-flex;align-items:center;padding:2px 9px;border-radius:999px;background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.4);color:#a5b4fc;font-size:.7rem;font-weight:600;margin-left:4px;"><?= htmlspecialchars($pg) ?></span><?php endforeach; ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px;">
                <?php foreach ($bfsRecos as $r):
                    $rp = $r['poster_url'] ?? '';
                    if (!$rp || $rp === 'N/A') $rp = '';
                    $isMood = ($r['mood_match'] ?? 0) > 0;
                ?>
                <a href="detail.php?id=<?= htmlspecialchars($r['imdb_id'] ?? '') ?>" style="text-decoration:none;color:inherit;display:block;background:rgba(255,255,255,0.04);border:1px solid <?= $isMood ? 'rgba(99,102,241,0.5)' : 'rgba(255,255,255,0.07)' ?>;border-radius:.85rem;overflow:hidden;transition:transform .2s,border-color .2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                    <div style="position:relative;aspect-ratio:2/3;overflow:hidden;background:#1a1025;">
                        <?php if ($rp): ?>
                            <img src="<?= htmlspecialchars($rp) ?>" alt="<?= htmlspecialchars($r['title'] ?? '') ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">🌶</div>
                        <?php endif; ?>
                        <?php if ($isMood): ?>
                            <span style="position:absolute;top:6px;right:6px;background:linear-gradient(90deg,#6366f1,#8b5cf6);color:#fff;font-size:.6rem;font-weight:700;padding:3px 7px;border-radius:999px;"><?= htmlspecialchars($moodEmojiIcon) ?> Mood</span>
                        <?php endif; ?>
                        <span style="position:absolute;top:6px;left:6px;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);color:#fff;font-size:.6rem;font-weight:700;padding:3px 7px;border-radius:999px;">BFS</span>
                    </div>
                    <div style="padding:.6rem;">
                        <div style="font-weight:700;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:.25rem;"><?= htmlspecialchars($r['title'] ?? 'Titre inconnu') ?></div>
                        <div style="color:#a1a1aa;font-size:.72rem;display:flex;gap:6px;">
                            <span>⭐ <?= number_format((float)($r['imdb_rating'] ?? 0), 1) ?></span>
                            <span>📅 <?= htmlspecialchars((string)($r['start_year'] ?? '')) ?></span>
                        </div>
                        <?php if (!empty($r['genres_list'])): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:.3rem;">
                            <?php foreach (array_slice($r['genres_list'], 0, 2) as $gn):
                                $gMatch = in_array($gn, $preferredGenres, true);
                            ?>
                            <span style="font-size:.6rem;padding:2px 7px;border-radius:999px;background:<?= $gMatch ? 'rgba(99,102,241,0.2)' : 'rgba(255,255,255,0.07)' ?>;color:<?= $gMatch ? '#a5b4fc' : '#ccc' ?>;"><?= htmlspecialchars($gn) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>



        <!-- Dominant Mood -->
        <div class="mj-glass-card mj-dominant" style="--dom-h: <?=explode(',', $domReact['glowHsl'])[0]?>; --dom-s: <?=explode(',', $domReact['glowHsl'])[1]?>; --dom-l: <?=explode(',', $domReact['glowHsl'])[2]?>;">
            <div class="mj-dominant-content">
                <div class="mj-badge" style="background:transparent; padding:0; margin-bottom:8px; border:none;">🏆 Mood dominant</div>
                <h3 class="mj-title"><?=ucfirst($dominantMood ?: 'Vide')?></h3>
                <p style="color:var(--clr-text-muted); max-w-sm; margin-bottom:20px;">
                    Tu sembles surtout <strong><?=strtolower($dominantMood ?: 'vide')?></strong> ces jours-ci.<br>Laisse 3ami l Felfil te suggérer un film qui colle à ton vibe.
                </p>
                <?php if($dominantMood): ?>
                <button class="mj-btn-ask" onclick="askAiReco('<?= addslashes($dominantMood) ?>')">
                    💬 Demander une reco à 3ami l Felfil
                </button>
                <?php endif; ?>
            </div>
            <div style="filter: drop-shadow(0 0 50px hsla(var(--dom-h), var(--dom-s), var(--dom-l), 0.8));">
                 <img src="<?=$base?>/assets/img/moodjar/<?=$domReact['image']?>" style="width: 150px; height: 150px; object-fit: contain;">
            </div>
        </div>

        <!-- Insights -->
        <div class="mj-grid-2">
            <div class="mj-glass-card">
                <h3 class="mj-title">🔮 Future Mood Predictor</h3>
                <?php
                   $insightIcon = "🫙";
                   $insightTxt = "Continue à remplir ton Mood Jar pour que l'IA puisse prédire tes tendances.";
                   if(count($history) > 3) {
                       $last = array_slice($history, -3);
                       $trend = $last[0]['emotional_intensity'] + $last[1]['emotional_intensity'] + $last[2]['emotional_intensity'];
                       if($trend < 12) { $insightIcon = "🌧️"; $insightTxt = "Tendance basse détectée. Tu sembles glisser vers des états calmes 🌧️. Essaye une comédie !"; }
                       else if($trend > 22) { $insightIcon = "🔥"; $insightTxt = "Tendance haute détectée. Beaucoup d'adrénaline 🔥. Prêt pour un film relaxant ?"; }
                       else { $insightIcon = "⚖️"; $insightTxt = "Équilibre parfait. Tes émotions voyagent de manière harmonieuse ⚖️."; }
                   }
                ?>
                <div style="font-size:3rem; margin:10px 0;"><?=$insightIcon?></div>
                <p style="color:var(--clr-text-muted); font-size:0.9rem;"><?=$insightTxt?></p>
            </div>
            
            <div class="mj-glass-card">
                <h3 class="mj-title">🏅 Badges émotionnels</h3>
                <div style="display:flex; gap:15px; margin-top:20px; flex-wrap:wrap;">
                    <?php 
                      $uniqueMoodsCount = count(array_unique(array_column($history, 'mood_id')));
                      $hasBadge = false;
                      if ($uniqueMoodsCount >= 5) {
                          $hasBadge = true;
                          echo '<div style="background:rgba(255,255,255,0.05); padding:10px; border-radius:12px; text-align:center; min-width:80px; border:1px solid rgba(16, 185, 129, 0.3);"><div style="font-size:2rem; filter:drop-shadow(0 0 10px #10b981);">🧭</div><div style="font-size:0.75rem; margin-top:5px; font-weight:600;">Explorateur</div></div>';
                      }
                      if ($dominantMood && $counts[$dominantMood] >= 4) {
                          $hasBadge = true;
                          echo '<div style="background:rgba(255,255,255,0.05); padding:10px; border-radius:12px; text-align:center; min-width:80px; border:1px solid rgba(245, 158, 11, 0.3);"><div style="font-size:2rem; filter:drop-shadow(0 0 10px #f59e0b);">🧸</div><div style="font-size:0.75rem; margin-top:5px; font-weight:600;">Comfort</div></div>';
                      }
                      if (!$hasBadge) {
                          echo '<div style="background:rgba(255,255,255,0.05); padding:10px; border-radius:12px; text-align:center; min-width:80px; border:1px solid rgba(255,255,255,0.1);"><div style="font-size:2rem; opacity:0.5;">🌱</div><div style="font-size:0.75rem; margin-top:5px; color:#888;">Novice</div></div>';
                      }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const peppersData = <?= json_encode($peppers) ?>;
const historyData = <?= json_encode($history) ?>;
const baseUrl = "<?=$base?>/assets/img/moodjar/";

// --- Render Peppers in Jar ---
const container = document.getElementById('peppersContainer');
const jarFilters = document.querySelectorAll('.mj-filter-btn');

function renderPeppers(filterId = 'all') {
    container.innerHTML = '';
    peppersData.forEach((p, i) => {
        // Apply filter transparency
        const isDim = filterId !== 'all' && p.mood_id !== filterId;
        
        const el = document.createElement('div');
        el.className = 'mj-pepper';
        
        // Randomize position based on index to look chaotic but fixed
        const seed = (i * 9301 + 49297) % 233280;
        const r1 = (seed % 1000) / 1000;
        const r2 = ((seed * 7) % 1000) / 1000;
        const r3 = ((seed * 13) % 1000) / 1000;
        
        const left = 18 + r1 * 64;
        const bottom = 10 + r2 * 55;
        const rotate = -35 + r3 * 70;
        const delay = r1 * 4;
        const duration = 5 + r2 * 4;
        const size = 38 + r3 * 22;

        el.style.left = left + '%';
        el.style.bottom = bottom + '%';
        el.style.width = size + 'px';
        el.style.height = size + 'px';
        el.style.setProperty('--r', rotate + 'deg');
        let dropDelay = r1 * 1.5; // drop slightly faster
        el.style.animation = `dropIntoJar 2.5s cubic-bezier(0.25, 1, 0.5, 1) ${dropDelay}s both, floatAnim ${duration}s ease-in-out calc(${dropDelay}s + 2.5s) infinite alternate`;
        
        // Filter effects
        el.style.filter = `drop-shadow(0 0 12px hsl(${p.glowHsl} / 0.85))`;
        if (isDim) {
            el.style.opacity = '0.15';
            el.style.pointerEvents = 'none';
        }

        el.innerHTML = `
            <img src="${baseUrl}${p.image}" style="transform: rotate(${rotate}deg)">
            <div class="mj-pepper-tooltip">
                <div class="mj-pepper-tooltip-title">${p.title}</div>
                <div class="mj-pepper-tooltip-date">${p.mood_name} · ${p.date}</div>
            </div>
        `;
        container.appendChild(el);
    });
}
renderPeppers();

const jarFiltersEl = document.getElementById('jarFilters');
if (jarFiltersEl) {
    jarFiltersEl.addEventListener('click', (e) => {
        const btn = e.target.closest('.mj-filter-btn');
        if(!btn) return;
        
        document.querySelectorAll('.mj-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        renderPeppers(btn.dataset.mood);
    });
}

// --- Chart.js ---
const ctxLine = document.getElementById('moodLineChart').getContext('2d');
const ctxPie = document.getElementById('moodPieChart').getContext('2d');

let lineLabels = peppersData.map(p => p.title.substring(0,10)+ (p.title.length>10?'...':''));
let lineVals = peppersData.map(p => parseInt(p.intensity) || 0);

// Line Chart
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: lineLabels,
        datasets: [{
            label: 'Intensité',
            data: lineVals,
            borderColor: '#e63946',
            backgroundColor: 'rgba(230, 57, 70, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#fff',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { min: 0, max: 10, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#888' } },
            x: { grid: { display: false }, ticks: { display: false } }
        }
    }
});

// Pie Chart Logic
let freq = {};
peppersData.forEach(p => {
    if(!freq[p.mood_name]) freq[p.mood_name] = { count: 0, color: `hsl(${p.glowHsl})` };
    freq[p.mood_name].count++;
});

new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: Object.keys(freq),
        datasets: [{
            data: Object.values(freq).map(f => f.count),
            backgroundColor: Object.values(freq).map(f => f.color),
            borderColor: '#050308',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right', labels: { color: '#a1a1aa', font: { size: 10 } } }
        }
    }
});

function askAiReco(moodName) {
    if(window.top && window.top.toggleChat) {
        window.top.toggleChat();
        setTimeout(() => {
            const input = document.getElementById('chatInput');
            if(input) {
                input.value = `Mon humeur dominante est ${moodName}. Tu peux me recommander un film adapté pour équilibrer ou renforcer ça ?`;
            }
        }, 500);
    } else {
        alert("Ouvre le Chat 3ami l Felfil !");
    }
}
</script>

<?php require_once '_footer.php'; ?>

