<?php
// ============================================================
//  VUE : DÉTAIL DU FILM (Détaillé & Premium)
// ============================================================
session_start();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../controller/tmdb.php';
require_once __DIR__.'/../controller/traitement.php';
require_once __DIR__.'/../model/TitleModel.php';
require_once __DIR__.'/../model/PersonModel.php';

$titleModel = new TitleModel();
$personModel = new PersonModel();

$user = $_SESSION['user'] ?? null;
$id   = $_GET['id'] ?? ''; // Peut être 'tt12345' ou '123' (local)
$type = $_GET['type'] ?? 'movie';

if (!$id) {
    header('Location: index.php');
    exit;
}

// ── RÉCUPÉRATION DES DONNÉES ────────────────────────────────
$is_local = false;
$d = [];

// 1. Essayer de trouver dans la table 'titles' (OMDb sync)
if (str_starts_with($id, 'tt')) {
    $stmt = $cnx->prepare("SELECT * FROM titles WHERE imdb_id = ?");
    $stmt->execute([$id]);
    $local_title = $stmt->fetch();
    
    if ($local_title) {
        $d = [
            'id'           => $local_title['imdb_id'],
            'title'        => $local_title['title'],
            'overview'     => $local_title['plot'] ?? '',
            'release_date' => $local_title['start_year'] . '-01-01',
            'vote_average' => $local_title['imdb_rating'],
            'poster_path'  => $local_title['poster_url'],
            'backdrop_path'=> null,
            'genres'       => [], 
            'runtime'      => $local_title['runtime'] ?? 0,
            'pepper'       => (int)round($local_title['imdb_rating'] / 2)
        ];
        $type = $local_title['type'];
        
        // Charger les détails TMDB complets (pour trailers, backdrops, etc.) via IMDb ID
        $found = tmdbFindById($id);
        if ($found) {
            $tmdb_id = $found['id'];
            $full_data = ($found['media_type'] === 'tv') ? tmdbTVDetail($tmdb_id, 'fr-FR') : tmdbMovieDetail($tmdb_id, 'fr-FR');
            if ($full_data) {
                $d = array_merge($d, $full_data);
            }
        }
    }
}

// 2. Essayer de trouver dans la table 'movies' (Tunisien Felflix)
if (empty($d) && is_numeric($id)) {
    $stmt = $cnx->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    $tunisian = $stmt->fetch();
    
    if ($tunisian) {
        $d = [
            'id'           => $tunisian['id'],
            'title'        => $tunisian['title'],
            'overview'     => $tunisian['description'],
            'release_date' => $tunisian['year'] . '-01-01',
            'vote_average' => $tunisian['rating'] * 2, // On remet sur 10 pour le calcul des stars
            'poster_path'  => $tunisian['poster_url'],
            'pepper'       => $tunisian['pepper'],
            'trailer_url'  => $tunisian['trailer_url'],
            'type'         => $tunisian['type']
        ];
        $is_local = true;
    }
}

// 3. Fallback TMDB si toujours rien (pour les films internationaux non indexés)
if (empty($d) && is_numeric($id)) {
    $tmdb_id = (int)$id;
    $d = ($type === 'tv') ? tmdbTVDetail($tmdb_id, 'fr-FR') : tmdbMovieDetail($tmdb_id, 'fr-FR');
    if (empty($d['id'])) {
        $d = ($type === 'tv') ? tmdbTVDetail($tmdb_id, 'en-US') : tmdbMovieDetail($tmdb_id, 'en-US');
    }
}

// Sécurité finale
if (empty($d)) {
    header('Location: index.php');
    exit;
}

// Formatage final
$title    = htmlspecialchars($d['title'] ?? $d['name'] ?? 'Sans titre');
$year     = substr($d['release_date'] ?? $d['first_air_date'] ?? '', 0, 4);
$rating   = round($d['vote_average'] ?? 0, 1);
$stars    = min(5, round($rating / 2));
$pepper   = $d['pepper'] ?? (int)round($rating / 2);
$overview = htmlspecialchars($d['overview'] ?? '');
$poster   = (str_starts_with($d['poster_path']??'', 'http')) ? $d['poster_path'] : tmdbPoster($d['poster_path'] ?? null, 'w500');
$backdrop = (!empty($d['backdrop_path'])) ? "https://image.tmdb.org/t/p/w1280".$d['backdrop_path'] : null;
$trailer  = $d['trailer_url'] ?? '';

// Extraire proprement les genres et la durée
$genres = [];
if (!empty($d['genres'])) {
    foreach ($d['genres'] as $g) {
        if (is_array($g) && isset($g['name'])) {
            $genres[] = $g['name'];
        } elseif (is_string($g)) {
            $genres[] = $g;
        }
    }
}
$runtime = $d['runtime'] ?? 0;

if (!$trailer && !empty($d['videos']['results'])) {
    foreach($d['videos']['results'] as $v) {
        if(($v['type']==='Trailer' || $v['type']==='Teaser') && $v['site']==='YouTube') {
            $trailer = 'https://www.youtube.com/embed/'.$v['key'];
            break;
        }
    }
}
// Fallback en-US pour les vidéos si toujours rien
if (!$trailer && !empty($id) && str_starts_with($id, 'tt')) {
    $found = tmdbFindById($id);
    if ($found) {
        $en_data = ($found['media_type'] === 'tv') ? tmdbTVDetail($found['id'], 'en-US') : tmdbMovieDetail($found['id'], 'en-US');
        if (!empty($en_data['videos']['results'])) {
            foreach($en_data['videos']['results'] as $v) {
                if(($v['type']==='Trailer' || $v['type']==='Teaser') && $v['site']==='YouTube') {
                    $trailer = 'https://www.youtube.com/embed/'.$v['key'];
                    break;
                }
            }
        }
    }
}
echo "<!-- DEBUG TRAILER: $trailer -->";

// Récupération du Réalisateur
$director = $d['director'] ?? '';
if (!$director && !empty($d['credits']['crew'])) {
    foreach($d['credits']['crew'] as $c) {
        if ($c['job'] === 'Director') {
            $director = $c['name'];
            break;
        }
    }
}
if (!$director && !empty($d['created_by'])) {
    $director = $d['created_by'][0]['name'] ?? '';
}

// Acteurs (Priorité TMDB pour avoir les photos)
$cast = [];
if (!empty($d['credits']['cast'])) {
    $cast = array_slice($d['credits']['cast'], 0, 12);
} elseif (str_starts_with($id, 'tt')) {
    $cast = $personModel->getActorsByTitle($id);
} elseif ($is_local) {
    // Charger les acteurs locaux via pivot movie_actors
    $stmt = $cnx->prepare("SELECT a.* FROM actors a JOIN movie_actors ma ON a.id = ma.actor_id WHERE ma.movie_id = ?");
    $stmt->execute([$id]);
    $cast = $stmt->fetchAll();
}

// Moods & Comments
$moods = []; try { $moods = $cnx->query("SELECT * FROM moods ORDER BY id ASC")->fetchAll(); } catch(Exception $e) {}

// Watchlist check
$inWL = false;
if($user && isset($user['id'])){
    $chk = $cnx->prepare("SELECT id FROM watchlist WHERE user_id=? AND tmdb_id=?");
    $chk->execute([$user['id'], $id]); 
    $inWL = (bool)$chk->fetch();
}

// Toggle watchlist / Add with mood
if($user && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_to_wl'])){
    try {
        $mood_id = (int)$_POST['mood_id'];
        $category = trim($_POST['category_name']) ?: 'My List';
        
        if (empty($user['id'])) throw new Exception("ID utilisateur manquant dans la session. Déconnectez-vous et reconnectez-vous.");

        // 1. Ajout Watchlist
        $stmt1 = $cnx->prepare("INSERT IGNORE INTO watchlist(user_id, tmdb_id, tmdb_type, tmdb_title, tmdb_poster, category_name) VALUES(?,?,?,?,?,?)");
        $stmt1->execute([$user['id'], $id, $type, $title, $poster, $category]);
        
        // 2. Ajout Historique (Mood Jar)
        $stmt2 = $cnx->prepare("INSERT INTO watch_history(user_id, tmdb_id, tmdb_type, tmdb_title, mood_id) VALUES(?,?,?,?,?)");
        $stmt2->execute([$user['id'], $id, $type, $title, $mood_id]);
        
        $_SESSION['success_msg'] = "Piment ajouté au bocal ! 🌶️";
        header("Location: detail.php?id=$id&type=$type"); exit;
        
    } catch(Exception $e) {
        $error_msg = "Erreur BDD : " . $e->getMessage();
    }
}

// Add comment
if($user && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['comment'])){
    try {
        $txt = trim($_POST['comment']);
        if($txt) $cnx->prepare("INSERT INTO comments(user_id,tmdb_id,tmdb_type,content) VALUES(?,?,?,?)")->execute([$user['id'],$id,$type,$txt]);
        header("Location: detail.php?id=$id&type=$type#comments"); exit;
    } catch(Exception $e) { $error_msg = "Erreur commentaire : " . $e->getMessage(); }
}

try {
    $cStmt = $cnx->prepare("SELECT c.*, u.nom, u.avatar FROM comments c JOIN users u ON u.id = c.user_id WHERE c.tmdb_id = ? ORDER BY c.created_at DESC");
    $cStmt->execute([$id]);
    $comments = $cStmt->fetchAll();
} catch(Exception $e) { $comments = []; }

$pageTitle = "$title — Felflix 🌶";
$activePage = '';
require_once '_header.php';
?>
<style>
.actor-pill{background:var(--glass);border:1px solid var(--border);border-radius:20px;padding:3px 11px;font-size:.75rem;color:var(--muted);text-decoration:none;transition:border-color .15s,color .15s;display:inline-block}
.actor-pill:hover{border-color:var(--red);color:#fff}
.cast-avatar{width:70px;height:70px;border-radius:50%;overflow:hidden;border:2px solid var(--border);transition:all .2s ease;background:var(--card)}
.cast-member:hover .cast-avatar{border-color:var(--red);transform:scale(1.1);box-shadow:var(--glow-soft)}
.cast-member:hover .cast-name{color:#fff !important}
.cast-fallback{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(230,57,70,0.2),rgba(5,3,8,0.5));color:var(--red);font-weight:800;font-family:'Syne',sans-serif;font-size:1.4rem}
</style>
<!-- BACKDROP HERO -->
<div style="padding-top:66px">
<?php if($backdrop):?>
<div style="width:100%;height:420px;overflow:hidden;position:relative">
  <img src="<?=$backdrop?>" style="width:100%;height:100%;object-fit:cover;object-position:center 25%"/>
  <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(5,3,8,.2) 0%,rgba(5,3,8,1) 100%)"></div>
  <div style="position:absolute;inset:0;background:linear-gradient(to right,rgba(5,3,8,.6) 0%,transparent 60%)"></div>
</div>
<?php else:?><div style="height:80px;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(230,57,70,.2),transparent 70%)"></div><?php endif;?>

<div class="wrap" style="margin-top:<?=$backdrop?'-200px':'20px'?>;position:relative;z-index:2;padding-bottom:60px">
  <a href="javascript:history.back()" class="btn-ghost btn-sm" style="display:inline-flex;margin-bottom:24px">← Rej3</a>

  <?php if(isset($error_msg)): ?>
    <div style="background:rgba(230,57,70,0.15); border:1px solid var(--red); color:#fff; padding:15px; border-radius:12px; margin-bottom:20px; font-size:0.9rem">
        ⚠️ <?= $error_msg ?>
    </div>
  <?php endif; ?>

  <?php if(isset($_SESSION['success_msg'])): ?>
    <div style="background:rgba(34,197,94,0.15); border:1px solid #22c55e; color:#fff; padding:15px; border-radius:12px; margin-bottom:20px; font-size:0.9rem">
        ✅ <?= $_SESSION['success_msg'] ?>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
  <?php endif; ?>

  <div class="film-info-grid">
    <div>
      <?php if($poster):?><div class="film-poster"><img src="<?=$poster?>" alt="<?=$title?>"/></div>
      <?php else:?><div style="aspect-ratio:2/3;background:var(--card);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:5rem"><?=$type==='tv'?'📺':'🎬'?></div><?php endif;?>
    </div>
    <div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;align-items:center">
        <span class="badge-type <?=$type==='tv'?'type-serie':'type-film'?>" style="position:static;font-size:.75rem;padding:4px 12px"><?=$type==='tv'?'Mosalsala':'Film'?></span>
        <?php foreach($genres as $g):?><span style="background:rgba(255,255,255,.07);border:1px solid var(--border);border-radius:8px;padding:3px 12px;font-size:.72rem;color:var(--muted)"><?=htmlspecialchars($g)?></span><?php endforeach;?>
        <?php if($year):?><span style="color:var(--dim);font-size:.82rem"><?=$year?></span><?php endif;?>
        <?php if($runtime):?><span style="color:var(--dim);font-size:.82rem">⏱ <?=$runtime?> min</span><?php endif;?>
      </div>
      <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(1.7rem,4vw,2.8rem);color:#fff;margin-bottom:14px;line-height:1.1;text-shadow:0 2px 20px rgba(0,0,0,.8)"><?=$title?></h1>
      <div style="display:flex;align-items:center;gap:20px;margin-bottom:24px;flex-wrap:wrap">
        <!-- Stars -->
        <div style="display:flex;flex-direction:column;gap:4px">
          <span style="font-size:0.65rem;color:var(--dim);letter-spacing:1px;text-transform:uppercase">Stars</span>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="color:var(--gold);font-size:1.2rem"><?php for($i=1;$i<=5;$i++) echo $i<=$stars?'★':'☆';?></span>
            <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:#fff"><?=$rating?></span>
          </div>
        </div>

        <!-- Felfel (Peppers) -->
        <div style="display:flex;flex-direction:column;gap:4px">
          <span style="font-size:0.65rem;color:var(--dim);letter-spacing:1px;text-transform:uppercase">M7ar7er</span>
          <div style="display:flex;align-items:center;gap:4px">
            <span style="font-size:1.2rem; filter: drop-shadow(0 0 5px rgba(230,57,70,0.5));">
                <?php for($i=1;$i<=5;$i++) echo $i<=$pepper?'🌶️':'<span style="opacity:0.2">🌶️</span>';?>
            </span>
            <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:var(--red)"><?=$pepper?>/5</span>
          </div>
        </div>

        <div class="glass-card" style="border-radius:10px;padding:7px 14px;text-align:center">
          <div style="font-size:.62rem;color:var(--dim);letter-spacing:2px">POPULARITÉ</div>
          <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;color:var(--red)"><?= min(99, (int)($rating * 10)) ?>%</div>
        </div>
        
        <span style="color:var(--dim);font-size:.82rem">💬 <?=count($comments)?> avis</span>
      </div>
      <?php if($director):?><p style="color:var(--muted);font-size:.9rem;margin-bottom:10px">🎬 <strong style="color:#fff">Réalisateur:</strong> <?=$director?></p><?php endif;?>
      <div style="margin-bottom:24px">
        <p style="color:var(--muted);font-size:0.7rem;margin-bottom:16px;text-transform:uppercase;letter-spacing:2px;font-weight:700">🎭 El Casting</p>
        <div style="display:flex;gap:16px;flex-wrap:wrap">
          <?php foreach($cast as $a): 
              $actorName = $a['name'] ?? '';
              $actorId = $a['id'] ?? ($a['actor_id'] ?? null);
              $actorImg = $a['photo_url'] ?? (isset($a['profile_path']) ? tmdbPoster($a['profile_path'], 'w185') : null);
              $link = $actorId ? "actor.php?id=$actorId" : "search.php?q=".urlencode($actorName)."&filter=person";
          ?>
            <a href="<?= $link ?>" class="cast-member" style="text-decoration:none;text-align:center;width:70px">
              <div class="cast-avatar">
                <?php if($actorImg): ?>
                  <img src="<?=$actorImg?>" alt="<?=$actorName?>" style="width:100%;height:100%;object-fit:cover"/>
                <?php else: ?>
                  <div class="cast-fallback"><?= mb_strtoupper(mb_substr($actorName, 0, 1)) ?></div>
                <?php endif; ?>
              </div>
              <div class="cast-name" style="margin-top:8px;font-size:0.7rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.2"><?= htmlspecialchars($actorName) ?></div>
            </a>
          <?php endforeach;?>
        </div>
      </div>
      <?php if($overview):?><p style="color:var(--muted);line-height:1.8;font-size:.9rem;margin-bottom:20px"><?=$overview?></p><?php endif;?>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php if($trailer):?><a href="#trailer" class="btn-hero btn-sm" style="text-decoration:none">▶ Trailer</a><?php endif;?>
        <?php if($user):?>
        <?php if($inWL): ?>
           <a href="?id=<?=$id?>&type=<?=$type?>&wl=1" class="wl-rm btn-sm">✓ Lista mta3i</a>
        <?php else: ?>
           <button class="wl-add btn-sm" onclick="document.getElementById('moodModal').style.display='flex'">+ Lista mta3i</button>
        <?php endif; ?>
        <a href="community.php?tmdb_id=<?=$id?>&tmdb_type=<?=$type?>&title=<?=urlencode($title)?>" class="btn-ghost btn-sm" style="text-decoration:none">💬 7ot ra2yik</a>
        <?php else:?><a href="login.php" class="btn-ghost btn-sm" style="text-decoration:none">🔑 D5ol</a><?php endif;?>
      </div>
    </div>
  </div>

  <?php if($trailer):?>
  <div id="trailer" style="margin-top:52px">
    <div class="sec-head" style="margin-bottom:16px"><div class="sec-title">🎬 <span class="accent">Trailer</span></div></div>
    <div class="sec-divider"></div>
    <div class="glass-card" style="border-radius:14px;overflow:hidden;padding:0">
      <iframe src="<?=htmlspecialchars($trailer)?>" style="width:100%;aspect-ratio:16/9;display:block" allowfullscreen loading="lazy"></iframe>
    </div>
  </div>
  <?php endif;?>

  <!-- COMMENTS -->
  <div id="comments" style="margin-top:52px">
    <div class="sec-head" style="margin-bottom:8px"><div class="sec-title">💬 <span class="accent">Ra2yet el 3omla</span> (<?=count($comments)?>)</div></div>
    <div class="sec-divider"></div>
    <?php if($user):?>
    <div class="write-box">
      <div style="display:flex;gap:12px">
        <div class="post-av"><?=htmlspecialchars($user['avatar']??'🌶')?></div>
        <form method="POST" style="flex:1">
          <textarea name="comment" rows="3" placeholder="Ra2yik fi <?=$title?>... 🌶" style="width:100%;background:var(--glass);border:1px solid var(--border);color:var(--txt);border-radius:12px;padding:12px;font-family:'Space Grotesk',sans-serif;font-size:.88rem;resize:none;outline:none;margin-bottom:10px" onfocus="this.style.borderColor='var(--red)';this.style.boxShadow='var(--glow-soft)'" onblur="this.style.borderColor='var(--border)';this.style.boxShadow='none'"></textarea>
          <button type="submit" class="btn-hero btn-sm">7ot ra2yek 🌶</button>
        </form>
      </div>
    </div>
    <?php else:?>
    <div class="write-box" style="text-align:center;padding:28px">
      <p style="color:var(--muted);margin-bottom:14px">D5ol bech t7achi 🌶</p>
      <a href="login.php" class="btn-hero btn-sm">D5ol</a>
    </div>
    <?php endif;?>
    <?php if(empty($comments)):?>
    <div class="empty-s" style="grid-column:auto"><span class="ei">💬</span><p>Mafammach ra2yet — kunti l'awel! 🌶</p></div>
    <?php else: foreach($comments as $c):?>
    <div class="post-card animate-fade-up">
      <div class="post-head">
        <div class="post-av"><?=htmlspecialchars($c['avatar']??'🌶')?></div>
        <div><div class="post-user"><?=htmlspecialchars($c['nom']??'Anonyme')?></div><div class="post-time"><?=substr($c['created_at']??'',0,10)?></div></div>
        <?php if($user&&($user['id']==$c['user_id']||$user['role']==='admin')):?>
        <a href="?id=<?=$id?>&type=<?=$type?>&del_c=<?=$c['id']?>" class="act-btn del" style="margin-left:auto" onclick="return confirm('Supprimer?')">🗑️</a>
        <?php endif;?>
      </div>
      <p class="post-text"><?=htmlspecialchars($c['content'])?></p>
      <div class="post-actions"><a href="?id=<?=$id?>&type=<?=$type?>&like_c=<?=$c['id']?>" class="act-btn">❤️ <?=$c['likes']??0?></a></div>
    </div>
    <?php endforeach;endif;?>
  </div>

  <!-- SIMILAR -->
  <?php if(!empty($similar)):?>
  <div style="margin-top:52px">
    <div class="sec-head" style="margin-bottom:8px"><div class="sec-title">🎬 <span class="accent">Yichbhoh bih</span></div></div>
    <div class="sec-divider"></div>
    <div class="grid">
      <?php foreach($similar as $m):$sp=tmdbPoster($m['poster_path']??null,'w185');$st=htmlspecialchars($m['title']??$m['name']??'');?>
      <a href="detail.php?id=<?=$m['id']?>&type=<?=$type?>" class="mcard">
        <div class="mcard-poster"><?php if($sp):?><img src="<?=$sp?>" alt="<?=$st?>" loading="lazy"/><?php else:?><div class="mcard-poster-empty">🎬</div><?php endif;?></div>
        <div class="mcard-body"><div class="mcard-title"><?=$st?></div><span class="mcard-rating" style="font-size:.75rem">⭐ <?=round($m['vote_average']??0,1)?>/10</span></div>
      </a>
      <?php endforeach;?>
    </div>
  </div>
  <?php endif;?>
</div>
</div>

<!-- MOOD MODAL -->
<div id="moodModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;justify-content:center;align-items:center;backdrop-filter:blur(8px)">
  <div class="glass-card" style="width:90%;max-width:520px;border-radius:24px;padding:32px;border:1px solid rgba(230,57,70,.2)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h2 style="font-family:'Syne',sans-serif;margin:0;color:#fff;font-size:1.3rem">🌶 Ajouter à ma liste</h2>
      <button onclick="document.getElementById('moodModal').style.display='none'" style="background:none;border:none;color:var(--muted);font-size:1.5rem;cursor:pointer;padding:4px 8px;border-radius:8px" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--muted)'">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="add_to_wl" value="1">
      <div style="margin-bottom:18px">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:8px">Catégorie :</label>
        <select name="category_name" required style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:12px 14px;border-radius:12px;font-family:'Space Grotesk',sans-serif;outline:none">
          <option value="A voir">A voir</option>
          <option value="Favoris">Favoris</option>
          <option value="Déjà vu">Déjà vu</option>
          <option value="Soirée entre amis">Soirée entre amis</option>
          <option value="Chill">Chill</option>
        </select>
      </div>
      <div style="margin-bottom:20px">
        <label style="color:var(--muted);font-size:.85rem;display:block;margin-bottom:10px">🫙 Ton mood par rapport à ce film :</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center">
          <?php foreach($moods as $m): ?>
          <label class="mood-lbl" style="cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:5px;padding:10px 8px;background:rgba(255,255,255,.05);border-radius:12px;border:1px solid var(--border);transition:all .2s;width:76px;text-align:center">
            <input type="radio" name="mood_id" value="<?=$m['id']?>" required style="display:none" onchange="updateMoodSel(this)">
            <span style="font-size:1.8rem;filter:drop-shadow(0 0 6px <?=$m['color']?>)">
                <?php if(strpos($m['icon'], '.png') !== false): ?>
                    <img src="<?= $base ?>/assets/img/moods/<?= $m['icon'] ?>" style="width:38px; height:38px; object-fit:contain; filter: drop-shadow(0 0 5px <?= $m['color'] ?>);">
                <?php else: ?>
                    <?= $m['icon'] ?>
                <?php endif; ?>
            </span>
            <span style="font-size:.68rem;color:var(--muted)"><?=$m['name']?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="submit" class="btn-hero" style="width:100%;justify-content:center;padding:14px">Valider et Ajouter 🌶</button>
    </form>
  </div>
</div>
<script>
function updateMoodSel(radio){
  document.querySelectorAll('.mood-lbl').forEach(l=>{l.style.background='rgba(255,255,255,.05)';l.style.borderColor='var(--border)';});
  radio.parentElement.style.background='rgba(230,57,70,.12)';
  radio.parentElement.style.borderColor='var(--red)';
  radio.parentElement.style.boxShadow='var(--glow-soft)';
}
</script>

<?php require_once '_footer.php';?>
