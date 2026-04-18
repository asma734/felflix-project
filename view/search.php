<?php
session_start();require_once '../controller/tmdb.php';
$q=trim($_GET['q']??'');$filter=$_GET['filter']??'all';$genre=$_GET['genre']??'';
$results=[];
if($q){
    $raw=tmdbSearch($q,'fr-FR');if(empty($raw)) $raw=tmdbSearch($q,'en-US');
    foreach($raw as $r){$t=$r['media_type']??'movie';if($filter!=='all'&&$t!==$filter) continue;$results[]=$r;}
}elseif($genre){
    $gid=tmdbGenreId($genre,'movie');$m=tmdbDiscover($gid,'movie','fr-FR');
    $gid2=tmdbGenreId($genre,'tv');$tv=tmdbDiscover($gid2,'tv','fr-FR');
    foreach($m as $x){$x['media_type']='movie';$results[]=$x;}
    foreach($tv as $x){$x['media_type']='tv';$results[]=$x;}
    usort($results,fn($a,$b)=>($b['popularity']??0)-($a['popularity']??0));
}else{$raw=tmdbTrending('all','fr-FR');foreach($raw as $r) $results[]=$r;}
$pageTitle=($q?"\"$q\" — ":'').'Recherche — Felflix';$activePage='search';require_once '_header.php';
$GENRES=['action'=>'🔥 Action','horreur'=>'👻 Horreur','scifi'=>'🚀 Sci-Fi','comedie'=>'😂 Comédie','romance'=>'💕 Romance','drame'=>'🎭 Drame','animation'=>'🌟 Animation','thriller'=>'🕵️ Thriller','crime'=>'🔫 Crime','aventure'=>'⚔️ Aventure','disney'=>'🏰 Famille','documentaire'=>'📖 Docu'];
?>
<div class="wrap" style="padding-top:90px;padding-bottom:60px">
  <form method="GET" class="search-big">
    <i class="fas fa-search" style="color:var(--dim);font-size:1.1rem;padding:0 8px;flex-shrink:0"></i>
    <input name="q" type="search" placeholder="Cherche un film, une série, un acteur..." value="<?=htmlspecialchars($q)?>"/>
    <button type="submit">Rechercher</button>
  </form>
  <!-- Genre pills -->
  <div class="cat-pills" style="margin-bottom:24px">
    <?php foreach($GENRES as $k=>$l):?><a href="?genre=<?=$k?>" class="cpill <?=$genre===$k&&!$q?'active':''?>"><?=$l?></a><?php endforeach;?>
  </div>
  <?php if($q&&!$genre):?>
  <div class="type-tabs">
    <button class="ttab <?=$filter==='all'  ?'active':''?>" onclick="setF('all')">Tout</button>
    <button class="ttab <?=$filter==='movie'?'active':''?>" onclick="setF('movie')">🎬 Films</button>
    <button class="ttab <?=$filter==='tv'   ?'active':''?>" onclick="setF('tv')">📺 Séries</button>
  </div>
  <p style="color:var(--muted);font-size:.85rem;margin-bottom:20px"><?=count($results)?> résultat(s) pour "<span style="color:var(--red)"><?=htmlspecialchars($q)?></span>"</p>
  <?php elseif($genre):?>
  <h2 class="sec-title" style="margin-bottom:8px"><?=$GENRES[$genre]??ucfirst($genre)?></h2><div class="sec-divider"></div>
  <?php else:?>
  <h2 class="sec-title" style="margin-bottom:8px">🔥 Tendances</h2><div class="sec-divider"></div>
  <?php endif;?>
  <div class="grid">
    <?php if(empty($results)):?>
    <div class="empty-s"><span class="ei">🎬</span><p>Aucun résultat<?=$q?" pour \"$q\"":'';?></p><a href="search.php" class="btn-ghost btn-sm">Voir les tendances</a></div>
    <?php else: foreach($results as $m):
      $type=$m['media_type']??($m['first_air_date']??false?'tv':'movie');
      $title=htmlspecialchars($m['title']??$m['name']??$m['original_title']??$m['original_name']??'');
      $year=substr($m['release_date']??$m['first_air_date']??'',0,4);
      $rating=round($m['vote_average']??0,1);
      $poster=tmdbPoster($m['poster_path']??null,'w300');
    ?>
    <a href="detail.php?id=<?=$m['id']?>&type=<?=$type?>" class="mcard">
      <div class="mcard-poster">
        <?php if($poster):?><img src="<?=$poster?>" alt="<?=$title?>" loading="lazy"/>
        <?php else:?><div class="mcard-poster-empty"><?=$type==='tv'?'📺':'🎬'?></div><?php endif;?>
        <span class="badge-type <?=$type==='tv'?'type-serie':'type-film'?>"><?=$type==='tv'?'Série':'Film'?></span>
      </div>
      <div class="mcard-body">
        <div class="mcard-title"><?=$title?></div>
        <div class="mcard-meta"><span class="mcard-rating">⭐ <?=$rating?></span><?php if($year):?><span class="mcard-year"><?=$year?></span><?php endif;?></div>
        <button class="mcard-btn">Voir + 🌶</button>
      </div>
    </a>
    <?php endforeach;endif;?>
  </div>
</div>
<script>function setF(f){const u=new URL(window.location);u.searchParams.set('filter',f);window.location.href=u.toString();}</script>
<?php require_once '_footer.php';?>
