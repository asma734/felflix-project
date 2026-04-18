<?php
session_start();
require_once '../controller/tmdb.php';
require_once '../controller/traitement.php';
$protocol=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')?'https':'http';
$base=$protocol.'://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']));
$user=$_SESSION['user']??null;
$id=(int)($_GET['id']??0);
$type=in_array($_GET['type']??'movie',['movie','tv'])?$_GET['type']:'movie';
if(!$id){header('Location: index.php');exit;}

// Load details
$d=$type==='tv'?tmdbTVDetail($id,'fr-FR'):tmdbMovieDetail($id,'fr-FR');
if(empty($d['id'])) $d=$type==='tv'?tmdbTVDetail($id,'en-US'):tmdbMovieDetail($id,'en-US');

$title=htmlspecialchars($d['title']??$d['name']??'Sans titre');
$year=substr($d['release_date']??$d['first_air_date']??'',0,4);
$rating=round($d['vote_average']??0,1);$stars=min(5,round($rating/2));
$overview=htmlspecialchars($d['overview']??'');
$runtime=$d['runtime']??($d['episode_run_time'][0]??0);
$poster=tmdbPoster($d['poster_path']??null,'w500');
$backdrop=$d['backdrop_path']?"https://image.tmdb.org/t/p/w1280".$d['backdrop_path']:null;
$genres=array_column($d['genres']??[],'name');
$heat=min(99,round(($rating/10)*80+10));
$director='';foreach($d['credits']['crew']??[] as $c){if($c['job']==='Director'){$director=htmlspecialchars($c['name']);break;}}
if(!$director&&!empty($d['created_by'])) $director=htmlspecialchars($d['created_by'][0]['name']??'');
$cast=array_slice($d['credits']['cast']??[],0,8);
$trailer='';foreach($d['videos']['results']??[] as $v){if($v['type']==='Trailer'&&$v['site']==='YouTube'){$trailer='https://www.youtube.com/embed/'.$v['key'];break;}}
$similar=array_slice($d['similar']['results']??[],0,6);

// Fetch Moods
$moods = [];
try {
    $moods = $cnx->query("SELECT * FROM moods ORDER BY id ASC")->fetchAll();
} catch(PDOException $e) {}

// Watchlist check
$inWL=false;
if($user){
    $chk=$cnx->prepare("SELECT id FROM watchlist WHERE user_id=? AND tmdb_id=? AND tmdb_type=?");
    $chk->execute([$user['id'],$id,$type]);$inWL=(bool)$chk->fetch();
}
// Toggle watchlist / Add with mood
if($user&&isset($_GET['wl'])){
    if($inWL) {
       $cnx->prepare("DELETE FROM watchlist WHERE user_id=? AND tmdb_id=? AND tmdb_type=?")->execute([$user['id'],$id,$type]);
       header("Location: detail.php?id=$id&type=$type");exit;
    }
}
if($user&&$_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_to_wl'])){
    $mood_id = (int)$_POST['mood_id'];
    $category = trim($_POST['category_name']) ?: 'My List';
    $cnx->prepare("INSERT IGNORE INTO watchlist(user_id,tmdb_id,tmdb_title,tmdb_poster,tmdb_type,category_name) VALUES(?,?,?,?,?,?)")
        ->execute([$user['id'],$id,$title,tmdbPoster($d['poster_path']??null,'w300'),$type,$category]);
    
    // Also add to watch_history for Mood Jar
    $cnx->prepare("INSERT INTO watch_history(user_id,tmdb_id,tmdb_type,tmdb_title,mood_id) VALUES(?,?,?,?,?)")
        ->execute([$user['id'],$id,$type,$title,$mood_id]);
        
    header("Location: detail.php?id=$id&type=$type");exit;
}
// Add comment
if($user&&$_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['comment'])){
    $txt=trim($_POST['comment']);
    if($txt) $cnx->prepare("INSERT INTO comments(user_id,tmdb_id,tmdb_type,content) VALUES(?,?,?,?)")->execute([$user['id'],$id,$type,$txt]);
    header("Location: detail.php?id=$id&type=$type#comments");exit;
}
// Delete comment
if($user&&isset($_GET['del_c'])){
    $cid=(int)$_GET['del_c'];
    if($user['role']==='admin') $cnx->prepare("UPDATE comments SET is_deleted=1 WHERE id=?")->execute([$cid]);
    else $cnx->prepare("UPDATE comments SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$cid,$user['id']]);
    header("Location: detail.php?id=$id&type=$type#comments");exit;
}
// Like comment
if($user&&isset($_GET['like_c'])){
    $cid=(int)$_GET['like_c'];
    try{$cnx->prepare("INSERT INTO comment_likes(user_id,comment_id) VALUES(?,?)")->execute([$user['id'],$cid]);$cnx->prepare("UPDATE comments SET likes=likes+1 WHERE id=?")->execute([$cid]);}catch(PDOException $e){}
    header("Location: detail.php?id=$id&type=$type#comments");exit;
}
// Load comments — safe si colonnes manquantes
try{
    $cStmt=$cnx->prepare("SELECT c.*,u.nom,u.avatar FROM comments c JOIN users u ON u.id=c.user_id WHERE c.tmdb_id=? AND c.tmdb_type=? AND COALESCE(c.is_deleted,0)=0 ORDER BY c.created_at DESC");
    $cStmt->execute([$id,$type]);$comments=$cStmt->fetchAll();
}catch(PDOException $e){
    $comments=[];
}

$pageTitle="$title — Felflix 🌶";$activePage='';require_once '_header.php';
?>
<div style="padding-top:66px">
<?php if($backdrop):?>
<div style="width:100%;height:380px;overflow:hidden;position:relative">
  <img src="<?=$backdrop?>" style="width:100%;height:100%;object-fit:cover;object-position:center 25%"/>
  <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(7,5,13,.25) 0%,rgba(7,5,13,1) 100%)"></div>
</div>
<?php else:?><div style="height:80px"></div><?php endif;?>

<div class="wrap" style="margin-top:<?=$backdrop?'-180px':'20px'?>;position:relative;z-index:2;padding-bottom:60px">
  <a href="javascript:history.back()" class="btn-ghost btn-sm" style="display:inline-flex;margin-bottom:24px">← Rej3</a>

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
      <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(1.7rem,4vw,2.6rem);color:#fff;margin-bottom:14px;line-height:1.1"><?=$title?></h1>
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:18px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px">
          <span style="color:var(--gold);font-size:1.2rem"><?php for($i=1;$i<=5;$i++) echo $i<=$stars?'★':'☆';?></span>
          <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:#fff"><?=$rating?></span>
          <span style="color:var(--dim);font-size:.82rem">/10</span>
        </div>
        <div style="background:rgba(230,57,70,.1);border:1px solid rgba(230,57,70,.2);border-radius:10px;padding:7px 14px;text-align:center">
          <div style="font-size:.62rem;color:var(--dim);letter-spacing:2px">HEAT</div>
          <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;color:var(--red)"><?=$heat?>🌶</div>
        </div>
        <span style="color:var(--dim);font-size:.82rem">💬 <?=count($comments)?> commentaire(s)</span>
      </div>
      <?php if($director):?><p style="color:var(--muted);font-size:.9rem;margin-bottom:10px">🎬 <strong style="color:#fff">Réalisateur:</strong> <?=$director?></p><?php endif;?>
      <?php if(!empty($cast)):?>
      <div style="margin-bottom:16px">
        <p style="color:var(--muted);font-size:.85rem;margin-bottom:8px">🎭 <strong style="color:#fff">Avec:</strong></p>
        <div style="display:flex;gap:7px;flex-wrap:wrap">
          <?php foreach($cast as $a):?><span style="background:var(--glass);border:1px solid var(--border);border-radius:20px;padding:3px 11px;font-size:.75rem;color:var(--muted)"><?=htmlspecialchars($a['name'])?></span><?php endforeach;?>
        </div>
      </div>
      <?php endif;?>
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
  <div id="trailer" style="margin-top:48px">
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.3rem;color:#fff;margin-bottom:16px">🎬 Trailer</h3>
    <div style="border:1px solid var(--border);border-radius:14px;overflow:hidden">
      <iframe src="<?=htmlspecialchars($trailer)?>" style="width:100%;aspect-ratio:16/9" allowfullscreen loading="lazy"></iframe>
    </div>
  </div>
  <?php endif;?>

  <!-- COMMENTS -->
  <div id="comments" style="margin-top:48px">
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.3rem;color:#fff;margin-bottom:20px">💬 Ra2yet el 3omla (<?=count($comments)?>)</h3>
    <?php if($user):?>
    <div class="write-box">
      <div style="display:flex;gap:12px">
        <div class="post-av"><?=htmlspecialchars($user['avatar']??'🌶')?></div>
        <form method="POST" style="flex:1">
          <textarea name="comment" rows="3" placeholder="Ra2yik fi <?=$title?>... 🌶" style="width:100%;background:var(--glass);border:1px solid var(--border);color:var(--txt);border-radius:12px;padding:12px;font-family:'Inter',sans-serif;font-size:.88rem;resize:none;outline:none;margin-bottom:10px" onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'"></textarea>
          <button type="submit" class="btn-hero btn-sm">7ot ra2yek 🌶</button>
        </form>
      </div>
    </div>
    <?php else:?>
    <div class="write-box" style="text-align:center;padding:24px">
      <p style="color:var(--muted);margin-bottom:12px">D5ol bech t7achi 🌶</p>
      <a href="login.php" class="btn-hero btn-sm">D5ol</a>
    </div>
    <?php endif;?>
    <?php if(empty($comments)):?>
    <div class="empty-s" style="grid-column:auto"><span class="ei">💬</span><p>Mafammach ra2yet — kunti l'awel! 🌶</p></div>
    <?php else: foreach($comments as $c):?>
    <div class="post-card">
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
  <div style="margin-top:48px">
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.3rem;color:#fff;margin-bottom:20px">🎬 Yichbhoh bih</h3>
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

<!-- MOOD SELECTION MODAL -->
<div id="moodModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; justify-content:center; align-items:center; backdrop-filter:blur(5px);">
    <div style="background:var(--card); width:90%; max-width:500px; border-radius:20px; padding:30px; box-shadow:0 10px 40px rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-family:'Syne',sans-serif; margin:0; color:#fff;">Ajouter à ma liste</h2>
            <button onclick="document.getElementById('moodModal').style.display='none'" style="background:none; border:none; color:var(--muted); font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="add_to_wl" value="1">
            <div style="margin-bottom:20px;">
                <label style="color:var(--dim); font-size:0.9rem; display:block; margin-bottom:10px;">Catégorie :</label>
                <select name="category_name" required style="width:100%; background:var(--bg); border:1px solid var(--border); color:#fff; padding:12px; border-radius:10px;">
                    <option value="A voir">A voir</option>
                    <option value="Favoris">Favoris</option>
                    <option value="Déjà vu">Déjà vu</option>
                    <option value="Soirée entre amis">Soirée entre amis</option>
                    <option value="Chill">Chill</option>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label style="color:var(--dim); font-size:0.9rem; display:block; margin-bottom:10px;">Comment tu te sens / Ton mood par rapport à ce film ? (🌶️ Mood Jar)</label>
                <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
                    <?php foreach($moods as $m): ?>
                    <label class="mood-lbl" style="cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:5px; padding:10px; background:rgba(255,255,255,0.05); border-radius:10px; transition:0.3s; width:80px; text-align:center;">
                        <input type="radio" name="mood_id" value="<?=$m['id']?>" required style="display:none;" onchange="updateMoodSel(this)">
                        <span style="font-size:2rem; filter:drop-shadow(0 0 5px <?=$m['color']?>)"><?=$m['icon']?></span>
                        <span style="font-size:0.7rem; color:var(--muted);"><?=$m['name']?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn-hero" style="width:100%;">Valider et Ajouter 🌶️</button>
        </form>
    </div>
</div>
<script>
function updateMoodSel(radio) {
    document.querySelectorAll('.mood-lbl').forEach(l => {
        l.style.background = 'rgba(255,255,255,0.05)';
        l.style.border = '1px solid transparent';
    });
    radio.parentElement.style.background = 'rgba(230,57,70,0.1)';
    radio.parentElement.style.border = '1px solid #e63946';
}
</script>

<?php require_once '_footer.php';?>
