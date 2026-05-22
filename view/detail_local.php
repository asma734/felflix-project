<?php
// ============================================================
//  Vue : Détail Film/Série Tunisien(ne)
//  Affiche : genres, acteurs cliquables, réalisateur, filtres
// ============================================================
session_start();
require_once '../controller/traitement.php';

$protocol = (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
$base     = $protocol.'://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']));
$user     = $_SESSION['user'] ?? null;
$id       = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: index.php'); exit; }

$stmt = $cnx->prepare("SELECT * FROM movies WHERE id=?");
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) { header('Location: index.php'); exit; }

$genres    = getGenresByMovie($cnx, $id);
$actors    = getActorsByMovie($cnx, $id);
$directors = getDirectorsByMovie($cnx, $id);
$isSeries  = ($movie['type'] ?? 'movie') === 'series';

// Moods pour la modal watchlist
$moods = [];
try { $moods = $cnx->query("SELECT * FROM moods ORDER BY id")->fetchAll(); } catch(PDOException $e) {}

// Watchlist
$inWL = false;
if ($user) {
    $chk = $cnx->prepare("SELECT id FROM watchlist WHERE user_id=? AND movie_id=?");
    $chk->execute([$user['id'], $id]);
    $inWL = (bool)$chk->fetch();
}

if ($user && isset($_GET['wl'])) {
    if ($inWL) { $cnx->prepare("DELETE FROM watchlist WHERE user_id=? AND movie_id=?")->execute([$user['id'], $id]); }
    header("Location: detail_local.php?id=$id"); exit;
}
if ($user && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_wl'])) {
    $moodId   = (int)$_POST['mood_id'];
    $category = trim($_POST['category_name']) ?: 'My List';
    $cnx->prepare("INSERT IGNORE INTO watchlist(user_id,movie_id,category_name) VALUES(?,?,?)")->execute([$user['id'],$id,$category]);
    $cnx->prepare("INSERT INTO watch_history(user_id,movie_id,mood_id) VALUES(?,?,?)")->execute([$user['id'],$id,$moodId]);
    header("Location: detail_local.php?id=$id"); exit;
}

// Commentaires
if ($user && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['comment'])) {
    $txt = trim($_POST['comment']);
    if ($txt) $cnx->prepare("INSERT INTO comments(user_id,movie_id,content) VALUES(?,?,?)")->execute([$user['id'],$id,$txt]);
    header("Location: detail_local.php?id=$id#comments"); exit;
}
if ($user && isset($_GET['del_c'])) {
    $cid = (int)$_GET['del_c'];
    if ($user['role']==='admin') $cnx->prepare("UPDATE comments SET is_deleted=1 WHERE id=?")->execute([$cid]);
    else $cnx->prepare("UPDATE comments SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$cid,$user['id']]);
    header("Location: detail_local.php?id=$id#comments"); exit;
}
if ($user && isset($_GET['like_c'])) {
    $cid = (int)$_GET['like_c'];
    try { $cnx->prepare("INSERT INTO comment_likes(user_id,comment_id) VALUES(?,?)")->execute([$user['id'],$cid]); $cnx->prepare("UPDATE comments SET likes=likes+1 WHERE id=?")->execute([$cid]); } catch(PDOException $e) {}
    header("Location: detail_local.php?id=$id#comments"); exit;
}
try {
    $cStmt = $cnx->prepare("SELECT c.*,u.nom,u.avatar FROM comments c JOIN users u ON u.id=c.user_id WHERE c.movie_id=? AND COALESCE(c.is_deleted,0)=0 ORDER BY c.created_at DESC");
    $cStmt->execute([$id]); $comments = $cStmt->fetchAll();
} catch(PDOException $e) { $comments = []; }

$SEASONS = ['spring'=>'🌸 Printemps','summer'=>'☀️ Été','autumn'=>'🍂 Automne','winter'=>'❄️ Hiver','all'=>''];

$title    = htmlspecialchars($movie['title']);
$year     = $movie['year'];
$rating   = $movie['rating'];
$stars    = min(5, round($rating/2));
$heat     = min(99, round(($rating/10)*80+10));
$overview = htmlspecialchars($movie['description'] ?? '');

$pageTitle  = "$title — Felflix 🌶";
$activePage = '';
require_once '_header.php';
?>

<style>
.actor-pill{background:var(--glass);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:.78rem;color:var(--muted);text-decoration:none;transition:all .15s;display:inline-block;margin:2px}
.actor-pill:hover{border-color:var(--red);color:#fff;background:rgba(230,57,70,.1)}
.info-badge{background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;padding:4px 12px;font-size:.75rem;color:var(--muted);display:inline-flex;align-items:center;gap:5px}
</style>

<div style="padding-top:66px">
  <div style="height:80px;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(230,57,70,.15),transparent 70%)"></div>

  <div class="wrap" style="padding-bottom:60px">
    <a href="javascript:history.back()" class="btn-ghost btn-sm" style="display:inline-flex;margin-bottom:24px">← Rej3</a>

    <div class="film-info-grid">
      <!-- Poster -->
      <div>
        <?php if (!empty($movie['poster_url'])): ?>
          <div class="film-poster"><img src="<?= htmlspecialchars($movie['poster_url']) ?>" alt="<?= $title ?>"></div>
        <?php else: ?>
          <div style="aspect-ratio:2/3;background:<?= htmlspecialchars($movie['bg_color']??'#e6394633') ?>;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:6rem">
            <?= htmlspecialchars($movie['emoji']??'🎬') ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Infos -->
      <div>
        <!-- Badges en haut -->
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;align-items:center">
          <span class="badge-type <?= $isSeries ? 'type-serie' : 'type-film' ?>" style="position:static;font-size:.75rem;padding:4px 12px">
            <?= $isSeries ? '📺 Série' : '🎥 Film' ?>
          </span>
          <span class="info-badge">🇹🇳 Tunisien</span>
          <?php if (!empty($movie['age_rating']) && $movie['age_rating'] !== 'all'): ?>
            <span class="info-badge">🔒 <?= htmlspecialchars($movie['age_rating']) ?></span>
          <?php endif; ?>
          <?php if (!empty($movie['season']) && $movie['season'] !== 'all' && isset($SEASONS[$movie['season']])): ?>
            <span class="info-badge"><?= $SEASONS[$movie['season']] ?></span>
          <?php endif; ?>
          <?php if ($movie['is_ramadan']): ?>
            <span style="background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.4);border-radius:8px;padding:4px 12px;font-size:.75rem;color:#f59e0b">🌙 Ramadan</span>
          <?php endif; ?>
          <?php if ($year): ?><span style="color:var(--dim);font-size:.82rem"><?= $year ?></span><?php endif; ?>
          <?php if ($isSeries && !empty($movie['nb_seasons'])): ?>
            <span class="info-badge">📺 <?= $movie['nb_seasons'] ?> saison(s)</span>
          <?php endif; ?>
        </div>

        <!-- Titre -->
        <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(1.7rem,4vw,2.8rem);color:#fff;margin-bottom:14px;line-height:1.1">
          <?= $title ?>
        </h1>

        <!-- Note + Heat -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:18px;flex-wrap:wrap">
          <div style="display:flex;align-items:center;gap:8px">
            <span style="color:var(--gold);font-size:1.2rem"><?php for($i=1;$i<=5;$i++) echo $i<=$stars?'★':'☆'; ?></span>
            <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:#fff"><?= $rating ?></span>
            <span style="color:var(--dim);font-size:.82rem">/10</span>
          </div>
          <div class="glass-card" style="border-radius:10px;padding:7px 14px;text-align:center">
            <div style="font-size:.62rem;color:var(--dim);letter-spacing:2px">HEAT</div>
            <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;color:var(--red)"><?= $heat ?>🌶</div>
          </div>
          <span style="color:var(--dim);font-size:.82rem">💬 <?= count($comments) ?> commentaire(s)</span>
        </div>

        <!-- Genres -->
        <?php if (!empty($genres)): ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
            <?php foreach ($genres as $g): ?>
              <a href="tunisian.php?genre_id=<?= $g['id'] ?>"
                 style="background:rgba(255,255,255,.06);border:1px solid var(--border);border-radius:8px;padding:4px 12px;font-size:.73rem;color:var(--muted);text-decoration:none;transition:all .15s"
                 onmouseover="this.style.borderColor='var(--red)';this.style.color='#fff'"
                 onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">
                <?= htmlspecialchars($g['icon']??'') ?> <?= htmlspecialchars($g['name']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Réalisateur(s) cliquable(s) -->
        <?php if (!empty($directors)): ?>
          <p style="color:var(--muted);font-size:.9rem;margin-bottom:10px">
            🎬 <strong style="color:#fff">Réalisateur :</strong>
            <?php foreach ($directors as $i => $dir): ?>
              <?= $i>0 ? ', ' : '' ?><a href="actor.php?id=<?= $dir['id'] ?>" style="color:var(--red);text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"><?= htmlspecialchars($dir['name']) ?></a>
            <?php endforeach; ?>
          </p>
        <?php endif; ?>

        <!-- Acteurs cliquables -->
        <?php if (!empty($actors)): ?>
          <div style="margin-bottom:16px">
            <p style="color:var(--muted);font-size:.85rem;margin-bottom:8px">🎭 <strong style="color:#fff">Avec :</strong></p>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              <?php foreach ($actors as $a): ?>
                <a href="actor.php?id=<?= $a['id'] ?>" class="actor-pill">
                  <?= htmlspecialchars($a['name']) ?>
                  <?php if (!empty($a['character_name'])): ?><span style="color:var(--dim)"> (<?= htmlspecialchars($a['character_name']) ?>)</span><?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Synopsis -->
        <?php if ($overview): ?>
          <p style="color:var(--muted);line-height:1.8;font-size:.9rem;margin-bottom:20px"><?= $overview ?></p>
        <?php endif; ?>

        <!-- Actions -->
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <?php if (!empty($movie['trailer_url'])): ?><a href="#trailer" class="btn-hero btn-sm" style="text-decoration:none">▶ Trailer</a><?php endif; ?>
          <?php if ($user): ?>
            <?php if ($inWL): ?>
              <a href="?id=<?= $id ?>&wl=1" class="wl-rm btn-sm">✓ Lista mta3i</a>
            <?php else: ?>
              <button class="wl-add btn-sm" onclick="document.getElementById('moodModal').style.display='flex'">+ Lista mta3i</button>
            <?php endif; ?>
          <?php else: ?>
            <a href="login.php" class="btn-ghost btn-sm" style="text-decoration:none">🔑 D5ol</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Trailer -->
    <?php if (!empty($movie['trailer_url'])): ?>
      <div id="trailer" style="margin-top:52px">
        <div class="sec-head" style="margin-bottom:16px"><div class="sec-title">🎬 <span class="accent">Trailer</span></div></div>
        <div class="sec-divider"></div>
        <div class="glass-card" style="border-radius:14px;overflow:hidden;padding:0">
          <iframe src="<?= htmlspecialchars($movie['trailer_url']) ?>" style="width:100%;aspect-ratio:16/9;display:block" allowfullscreen loading="lazy"></iframe>
        </div>
      </div>
    <?php endif; ?>

    <!-- Commentaires -->
    <div id="comments" style="margin-top:52px">
      <div class="sec-head" style="margin-bottom:8px"><div class="sec-title">💬 <span class="accent">Ra2yet el 3omla</span> (<?= count($comments) ?>)</div></div>
      <div class="sec-divider"></div>
      <?php if ($user): ?>
        <div class="write-box">
          <div style="display:flex;gap:12px">
            <div class="post-av"><?= htmlspecialchars($user['avatar']??'🌶') ?></div>
            <form method="POST" style="flex:1">
              <textarea name="comment" rows="3" placeholder="Ra2yik fi <?= $title ?>... 🌶" style="width:100%;background:var(--glass);border:1px solid var(--border);color:var(--txt);border-radius:12px;padding:12px;font-family:'Space Grotesk',sans-serif;font-size:.88rem;resize:none;outline:none;margin-bottom:10px" onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'"></textarea>
              <button type="submit" class="btn-hero btn-sm">7ot ra2yek 🌶</button>
            </form>
          </div>
        </div>
      <?php else: ?>
        <div class="write-box" style="text-align:center;padding:28px">
          <p style="color:var(--muted);margin-bottom:14px">D5ol bech t7achi 🌶</p>
          <a href="login.php" class="btn-hero btn-sm">D5ol</a>
        </div>
      <?php endif; ?>
      <?php if (empty($comments)): ?>
        <div class="empty-s" style="grid-column:auto"><span class="ei">💬</span><p>Mafammach ra2yet — kunti l'awel! 🌶</p></div>
      <?php else: foreach($comments as $c): ?>
        <div class="post-card animate-fade-up">
          <div class="post-head">
            <div class="post-av"><?= htmlspecialchars($c['avatar']??'🌶') ?></div>
            <div><div class="post-user"><?= htmlspecialchars($c['nom']??'Anonyme') ?></div><div class="post-time"><?= substr($c['created_at']??'',0,10) ?></div></div>
            <?php if ($user&&($user['id']==$c['user_id']||$user['role']==='admin')): ?>
              <a href="?id=<?= $id ?>&del_c=<?= $c['id'] ?>" class="act-btn del" style="margin-left:auto" onclick="return confirm('Supprimer?')">🗑️</a>
            <?php endif; ?>
          </div>
          <p class="post-text"><?= htmlspecialchars($c['content']) ?></p>
          <div class="post-actions"><a href="?id=<?= $id ?>&like_c=<?= $c['id'] ?>" class="act-btn">❤️ <?= $c['likes']??0 ?></a></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Mood Modal -->
<div id="moodModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;justify-content:center;align-items:center;backdrop-filter:blur(8px)">
  <div class="glass-card" style="width:90%;max-width:520px;border-radius:24px;padding:32px;border:1px solid rgba(230,57,70,.2)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h2 style="font-family:'Syne',sans-serif;margin:0;color:#fff;font-size:1.3rem">🌶 Ajouter à ma liste</h2>
      <button onclick="document.getElementById('moodModal').style.display='none'" style="background:none;border:none;color:var(--muted);font-size:1.5rem;cursor:pointer">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="add_to_wl" value="1">
      <div style="margin-bottom:18px">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:8px">Catégorie :</label>
        <select name="category_name" required style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:12px 14px;border-radius:12px;font-family:'Space Grotesk',sans-serif;outline:none">
          <option>A voir</option><option>Favoris</option><option>Déjà vu</option><option>Soirée entre amis</option><option>Chill</option>
        </select>
      </div>
      <div style="margin-bottom:20px">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:10px">🫙 Ton mood :</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center">
          <?php foreach ($moods as $m): ?>
            <label style="cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:5px;padding:10px 8px;background:rgba(255,255,255,.05);border-radius:12px;border:1px solid var(--border);transition:all .2s;width:76px;text-align:center">
              <input type="radio" name="mood_id" value="<?= $m['id'] ?>" required style="display:none" onchange="this.parentElement.style.background='rgba(230,57,70,.12)';this.parentElement.style.borderColor='var(--red)'">
              <span style="font-size:1.8rem"><?= $m['icon'] ?></span>
              <span style="font-size:.68rem;color:var(--muted)"><?= $m['name'] ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="submit" class="btn-hero" style="width:100%;justify-content:center;padding:14px">Valider et Ajouter 🌶</button>
    </form>
  </div>
</div>

<?php require_once '_footer.php'; ?>
