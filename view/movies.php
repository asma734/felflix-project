<?php
session_start();require_once '../controller/tmdb.php';
$genre=$_GET['genre']??'';$page=max(1,(int)($_GET['page']??1));
$movies=$genre?tmdbDiscover(tmdbGenreId($genre,'movie'),'movie','fr-FR',$page):tmdbMovies($page,'fr-FR');
$pageTitle=($genre?ucfirst($genre).' — ':'').'Films — Felflix';$activePage='movies';require_once '_header.php';
$GENRES=['action'=>'🔥 Action','horreur'=>'👻 Horreur','scifi'=>'🚀 Sci-Fi','comedie'=>'😂 Comédie','romance'=>'💕 Romance','drame'=>'🎭 Drame','animation'=>'🌟 Animation','thriller'=>'🕵️ Thriller','crime'=>'🔫 Crime','aventure'=>'⚔️ Aventure','disney'=>'🏰 Famille','documentaire'=>'📖 Docu'];
?>
<div class="wrap" style="padding-top:90px;padding-bottom:60px">
  <div class="sec-head"><div class="sec-title">🎬 <span class="accent"><?=$genre?htmlspecialchars($GENRES[$genre]??ucfirst($genre)):'Tous les films'?></span></div></div>
  <div class="sec-divider"></div>
  <div class="cat-pills" style="margin-bottom:28px">
    <a href="movies.php" class="cpill <?=!$genre?'active':''?>">Tout</a>
    <?php foreach($GENRES as $k=>$l):?><a href="movies.php?genre=<?=$k?>" class="cpill <?=$genre===$k?'active':''?>"><?=$l?></a><?php endforeach;?>
  </div>
  <div class="grid">
    <?php foreach($movies as $m):
      $poster=tmdbPoster($m['poster_path']??null,'w300');
      $title=htmlspecialchars($m['title']??$m['original_title']??'');
      $year=substr($m['release_date']??'',0,4);$rating=round($m['vote_average']??0,1);
    ?>
    <a href="detail.php?id=<?=$m['id']?>&type=movie" class="mcard">
      <div class="mcard-poster">
        <?php if($poster):?><img src="<?=$poster?>" alt="<?=$title?>" loading="lazy"/>
        <?php else:?><div class="mcard-poster-empty">🎬</div><?php endif;?>
        <span class="badge-type type-film">Film</span>
      </div>
      <div class="mcard-body">
        <div class="mcard-title"><?=$title?></div>
        <div class="mcard-meta"><span class="mcard-rating">⭐ <?=$rating?></span><?php if($year):?><span class="mcard-year"><?=$year?></span><?php endif;?></div>
        <button class="mcard-btn">Voir + 🌶</button>
      </div>
    </a>
    <?php endforeach;?>
  </div>
  <div style="text-align:center;margin-top:32px;display:flex;gap:10px;justify-content:center">
    <?php if($page>1):?><a href="?genre=<?=htmlspecialchars($genre)?>&page=<?=$page-1?>" class="btn-ghost btn-sm">← Préc.</a><?php endif;?>
    <a href="?genre=<?=htmlspecialchars($genre)?>&page=<?=$page+1?>" class="btn-hero btn-sm">Suivant →</a>
  </div>
</div>
<?php require_once '_footer.php';?>
