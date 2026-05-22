<?php
// ============================================================
//  Vue : Page Acteur / Réalisateur Tunisien
//  Affiche profil + toute la filmographie (films + séries)
// ============================================================
session_start();
require_once '../controller/traitement.php';

$actorId = (int)($_GET['id'] ?? 0);
if (!$actorId) { 
  header('Location: index.php'); 
  exit; }

$stmt = $cnx->prepare("SELECT * FROM actors WHERE id=?");
$stmt->execute([$actorId]);
$actor = $stmt->fetch();

$filmography = [];
$is_tmdb = false;

if (!$actor) {
    // Essayer TMDB (pour les acteurs internationaux)
    require_once '../controller/tmdb.php';
    $actorData = tmdbGet("https://api.themoviedb.org/3/person/$actorId?api_key=$tmdb_api_key&language=fr-FR&append_to_response=combined_credits");
    // recupere l id depuis l url 
    if (!empty($actorData['id'])) { //verifier si  api mil tmdb l9at acteur 
        $is_tmdb = true;
        // maintenent je suis entrain de faire une etape de mappage sert  dan sle code html 
        $actor = [
            'id' => $actorData['id'],
            'name' => $actorData['name'],
            'photo_url' => tmdbPoster($actorData['profile_path'], 'h632'),
            'bio' => $actorData['biography'],
            'birth_year' => substr($actorData['birthday']??'', 0, 4),
            'nationality' => $actorData['place_of_birth']
        ];
        
        // Formater la filmographie TMDB
        foreach ($actorData['combined_credits']['cast'] ?? [] as $m) {
            $filmography[] = [
                'id' => $m['id'],
                'title' => $m['title'] ?? $m['name'],
                'year' => substr($m['release_date'] ?? $m['first_air_date'] ?? '', 0, 4),
                'rating' => round($m['vote_average'] ?? 0, 1),
                'poster_url' => tmdbPoster($m['poster_path']),
                'type' => $m['media_type'],
                'role' => 'actor',
                'character_name' => $m['character']
            ];
        }
        // Limiter aux 40 plus importants par popularité si besoin, ou trier par date
        usort($filmography, fn($a, $b) => $b['year'] <=> $a['year']);
        $filmography = array_slice($filmography, 0, 50);
    }
}

if (!$actor) { header('Location: index.php'); exit; }

// Si c'est un acteur local, charger sa filmographie depuis la BDD
if (!$is_tmdb) {
    try {
        $stmt = $cnx->prepare(
            "SELECT m.*, ma.role, ma.character_name
             FROM movies m
             JOIN movie_actors ma ON m.id = ma.movie_id
             WHERE ma.actor_id = ?
             ORDER BY m.year DESC, m.rating DESC"
        );
        $stmt->execute([$actorId]);
        $filmography = $stmt->fetchAll();
    } catch(PDOException $e) { $filmography = []; }
}

$asDirector = array_values(array_filter($filmography, fn($f) => ($f['role']??'')==='director'));
$asActor    = array_values(array_filter($filmography, fn($f) => ($f['role']??'')==='actor' || empty($f['role'])));
$asWriter   = array_values(array_filter($filmography, fn($f) => ($f['role']??'')==='writer'));

$SEASONS = ['spring'=>'🌸','summer'=>'☀️','autumn'=>'🍂','winter'=>'❄️','all'=>''];

$pageTitle  = htmlspecialchars($actor['name']).' — Felflix 🌶';
$activePage = '';
require_once '_header.php';
?>

<style>
.actor-pill{background:var(--glass);border:1px solid var(--border);border-radius:20px;padding:3px 10px;font-size:.72rem;color:var(--muted);text-decoration:none;transition:all .15s;display:inline-block;margin:2px}
.actor-pill:hover{border-color:var(--red);color:#fff}
.role-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 14px;border-radius:8px;font-size:.75rem;font-weight:700}
</style>

<div style="padding-top:66px">
  <div style="height:80px;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(230,57,70,.12),transparent 70%)"></div>

  <div class="wrap" style="padding-bottom:60px">
    <a href="javascript:history.back()" class="btn-ghost btn-sm" style="display:inline-flex;margin-bottom:28px">← Rej3</a>

    <!-- Profil -->
    <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;margin-bottom:48px">
      <!-- Avatar -->
      <div style="width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,rgba(230,57,70,.3),rgba(120,40,200,.3));border:2px solid rgba(230,57,70,.3);display:flex;align-items:center;justify-content:center;font-size:2.8rem;font-weight:800;color:var(--red);flex-shrink:0;font-family:'Syne',sans-serif;overflow:hidden">
        <?php if (!empty($actor['photo_url'])): ?>
          <img src="<?= htmlspecialchars($actor['photo_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <?= mb_strtoupper(mb_substr($actor['name'], 0, 1)) ?>
        <?php endif; ?>
      </div>

      <div style="flex:1;min-width:200px">
        <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(1.6rem,4vw,2.4rem);color:#fff;margin-bottom:6px;line-height:1.1">
          <?= htmlspecialchars($actor['name']) ?>
          <?php if (!empty($actor['name_ar'])): ?>
            <span style="font-size:1.2rem;color:var(--muted);font-weight:400"> / <?= htmlspecialchars($actor['name_ar']) ?></span>
          <?php endif; ?>
        </h1>

        <!-- Badges rôles + infos -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
          <?php if (!empty($asDirector)): ?>
            <span class="role-badge" style="background:rgba(230,57,70,.15);border:1px solid rgba(230,57,70,.3);color:var(--red)">🎬 Réalisateur</span>
          <?php endif; ?>
          <?php if (!empty($asActor)): ?>
            <span class="role-badge" style="background:rgba(124,58,237,.15);border:1px solid rgba(124,58,237,.3);color:#a78bfa">🎭 Acteur</span>
          <?php endif; ?>
          <?php if (!empty($asWriter)): ?>
            <span class="role-badge" style="background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);color:#f59e0b">✍️ Scénariste</span>
          <?php endif; ?>
          <span style="background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;padding:4px 12px;font-size:.75rem;color:var(--dim)">
            🎬 <?= count($filmography) ?> production(s)
          </span>
          <?php if (!empty($actor['birth_year'])): ?>
            <span style="background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;padding:4px 12px;font-size:.75rem;color:var(--dim)">
              📅 Né(e) en <?= $actor['birth_year'] ?>
            </span>
          <?php endif; ?>
          <?php if (!empty($actor['nationality'])): ?>
            <span style="background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;padding:4px 12px;font-size:.75rem;color:var(--dim)">
              🇹🇳 <?= htmlspecialchars($actor['nationality']) ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if (!empty($actor['bio'])): ?>
          <p style="color:var(--muted);line-height:1.7;font-size:.9rem;max-width:640px"><?= htmlspecialchars($actor['bio']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Filmographie vide -->
    <?php if (empty($filmography)): ?>
      <div class="empty-s"><span class="ei">🎬</span><p>Mafammach films lih encore dans la BDD 🌶</p></div>
    <?php else: ?>

    <?php
    // Fonction helper pour afficher une section de filmographie
    function renderFilmoSection($cnx, $items, $title, $SEASONS) {
        if (empty($items)) return;
        echo '<div style="margin-bottom:48px">';
        echo '<div class="sec-head" style="margin-bottom:8px"><div class="sec-title">' . $title . '</div></div>';
        echo '<div class="sec-divider"></div>';
        echo '<div class="grid">';
        foreach ($items as $m) {
            $mId = $m['id'];
            $mType = $m['type'] ?? 'movie';
            $mGenres = [];
            // Ne chercher les genres locaux que si c'est un film local
            if (is_numeric($mId) && $mId < 1000000) { 
                $mGenres = getGenresByMovie($cnx, (int)$mId);
            }
            
            $isSeries = $mType === 'series' || $mType === 'tv';
            $seasonIcon = $SEASONS[$m['season'] ?? 'all'] ?? '';
            
            echo '<a href="detail.php?id=' . $mId . '&type=' . $mType . '" class="mcard">';
            echo '<div class="mcard-poster">';
            if (!empty($m['poster_url'])) {
                $pUrl = (str_starts_with($m['poster_url'], 'http')) ? $m['poster_url'] : $m['poster_url']; // Déjà géré par tmdbPoster
                echo '<img src="' . htmlspecialchars($pUrl) . '" alt="" loading="lazy">';
            } else {
                echo '<div class="mcard-poster-empty" style="background:' . htmlspecialchars($m['bg_color']??'#e6394633') . ';font-size:3.2rem">' . htmlspecialchars($m['emoji']??'🎬') . '</div>';
            }
            $badgeClass = $isSeries ? 'background:rgba(124,58,237,.8)' : 'background:rgba(230,57,70,.8)';
            echo '<span style="position:absolute;top:8px;right:8px;' . $badgeClass . ';border-radius:6px;padding:2px 7px;font-size:.62rem;font-weight:700;color:#fff">';
            echo $isSeries ? '📺' : '🎥';
            echo '</span>';
            echo '</div>';
            echo '<div class="mcard-body">';
            echo '<div class="mcard-title">' . htmlspecialchars($m['title']) . '</div>';
            if (!empty($m['character_name'])) echo '<div style="color:var(--dim);font-size:.7rem;margin-top:2px;font-style:italic">→ ' . htmlspecialchars($m['character_name']) . '</div>';
            echo '<div class="mcard-meta"><span class="mcard-rating">⭐ ' . $m['rating'] . '</span><span class="mcard-year">' . $m['year'] . '</span>';
            if ($seasonIcon) echo '<span style="font-size:.72rem">' . $seasonIcon . '</span>';
            echo '</div>';
            
            if (!empty($mGenres)) {
                echo '<div style="display:flex;gap:3px;flex-wrap:wrap;margin-top:3px">';
                foreach (array_slice($mGenres, 0, 2) as $g) {
                    echo '<span style="font-size:.6rem;background:rgba(255,255,255,.06);border-radius:5px;padding:1px 5px;color:var(--dim)">' . htmlspecialchars($g['icon']??'') . ' ' . htmlspecialchars($g['name']) . '</span>';
                }
                echo '</div>';
            }
            echo '<button class="mcard-btn" style="margin-top:7px">Voir + 🌶</button>';
            echo '</div>';
            echo '</a>';
        }
        echo '</div></div>';
    }
    ?>

    <?php renderFilmoSection($cnx, $asDirector, '🎬 <span class="accent">Films réalisés</span>', $SEASONS); ?>
    <?php renderFilmoSection($cnx, $asActor, '🎭 <span class="accent">Films joués</span>', $SEASONS); ?>
    <?php renderFilmoSection($cnx, $asWriter, '✍️ <span class="accent">Films écrits</span>', $SEASONS); ?>

    <?php endif; ?>
  </div>
</div>

<?php require_once '_footer.php'; ?>
