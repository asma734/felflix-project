<?php
session_start();if(!isset($_SESSION['user'])){header('Location: login.php');exit;}
require_once '../controller/traitement.php';
$uid=$_SESSION['user']['id'];
if(isset($_GET['rm'])){$cnx->prepare("DELETE FROM watchlist WHERE user_id=? AND id=?")->execute([$uid,(int)$_GET['rm']]);header('Location: watchlist.php');exit;}
$items=$cnx->prepare("SELECT * FROM watchlist WHERE user_id=? ORDER BY added_at DESC");$items->execute([$uid]);$wl=$items->fetchAll();
$pageTitle='Ma liste — Felflix';$activePage='watchlist';require_once '_header.php';
?>
<div class="wrap" style="padding-top:90px;padding-bottom:60px">
  <div class="sec-head"><div class="sec-title">📋 <span class="accent">Lista mta3i</span></div></div>
  <p style="color:var(--muted);margin-bottom:28px;font-size:.9rem"><?=count($wl)?> film(s) / mosalsala(t)</p>
  
  <?php if(empty($wl)):?>
  <div class="empty-s"><span class="ei">📋</span><p>Ta liste est vide!</p><a href="search.php" class="btn-hero btn-sm">Découvrir des films</a></div>
  <?php else:
      // Group by category
      $grouped = [];
      foreach($wl as $w) {
          $cat = $w['category_name'] ?: 'My List';
          if(!isset($grouped[$cat])) $grouped[$cat] = [];
          $grouped[$cat][] = $w;
      }
      foreach($grouped as $category => $items):
  ?>
  <h2 style="font-family:'Syne',sans-serif; color:#e63946; margin: 40px 0 20px; font-size:1.8rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">
      <?= htmlspecialchars($category) ?> (<?= count($items) ?>)
  </h2>
  <div class="grid">
    <?php foreach($items as $w):?>
    <div style="position:relative">
      <a href="detail.php?id=<?=$w['tmdb_id']?>&type=<?=htmlspecialchars($w['tmdb_type']??'movie')?>" class="mcard" style="display:block;text-decoration:none">
        <div class="mcard-poster">
          <?php if($w['tmdb_poster']):?><img src="<?=htmlspecialchars($w['tmdb_poster'])?>" loading="lazy"/>
          <?php else:?><div class="mcard-poster-empty"><?=$w['tmdb_type']==='tv'?'📺':'🎬'?></div><?php endif;?>
          <span class="badge-type <?=$w['tmdb_type']==='tv'?'type-serie':'type-film'?>"><?=$w['tmdb_type']==='tv'?'Série':'Film'?></span>
        </div>
        <div class="mcard-body">
          <div class="mcard-title"><?=htmlspecialchars($w['tmdb_title']??'Film')?></div>
          <div style="font-size:.72rem;color:var(--dim)">Ajouté le <?=substr($w['added_at']??'',0,10)?></div>
        </div>
      </a>
      <a href="?rm=<?=$w['id']?>" onclick="return confirm('Retirer?')" style="position:absolute;top:8px;right:8px;z-index:10;background:rgba(0,0,0,.75);border:none;color:#ff9999;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;text-decoration:none">✕</a>
    </div>
    <?php endforeach;?>
  </div>
  <?php endforeach; endif;?>
</div>
<?php require_once '_footer.php';?>
