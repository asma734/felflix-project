<?php
session_start();require_once '../controller/tmdb.php';
$trending=tmdbTrending('all','fr-FR');
$pageTitle='Felflix 🌶 — Premier site tunisien de films & séries';
$activePage='home';require_once '_header.php';
$GENRES=['action'=>'🔥 Action','horreur'=>'👻 Horreur','scifi'=>'🚀 Sci-Fi','comedie'=>'😂 Comédie','romance'=>'💕 Romance','drame'=>'🎭 Drame','animation'=>'🌟 Animation','thriller'=>'🕵️ Thriller','crime'=>'🔫 Crime','aventure'=>'⚔️ Aventure','disney'=>'🏰 Famille','documentaire'=>'📖 Docu'];
?>
<section class="hero">
  <div style="position:relative;z-index:1;text-align:center;max-width:700px">
    <div class="hero-tag">🌶 Marhbe bik fi Felflix &nbsp;|&nbsp; Premier site tunisien 🇹🇳</div>
    <h1 class="hero-title">Cinéma m7ar7ir<br/>kil felfil!</h1>
    <p class="hero-sub">Films, séries, avis, discussions — toute la culture ciné en derja tunisienne 🌶</p>
    <div class="hero-actions">
      <a href="search.php" class="btn-hero">🔍 Cherche un film</a>
      <a href="community.php" class="btn-ghost">💬 Rejoins la communauté</a>
    </div>
  </div>
</section>

<!-- TENDANCES -->
<div class="wrap section">
  <div class="sec-head"><div class="sec-title">🔥 <span class="accent">Tendances</span> cette semaine</div><a href="search.php" class="sec-more">Voir tout →</a></div>
  <div class="sec-divider"></div>
  <div class="grid">
    <?php foreach(array_slice($trending,0,8) as $m):
      $type=isset($m['title'])?'movie':'tv';
      $title=htmlspecialchars($m['title']??$m['name']??'');
      $year=substr($m['release_date']??$m['first_air_date']??'',0,4);
      $rating=round($m['vote_average']??0,1);$stars=min(5,round($rating/2));
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
        <div class="mcard-meta">
          <span class="mcard-rating">⭐ <?=$rating?></span>
          <?php if($year):?><span class="mcard-year"><?=$year?></span><?php endif;?>
        </div>
        <button class="mcard-btn">Voir + 🌶</button>
      </div>
    </a>
    <?php endforeach;?>
  </div>
</div>

<!-- GENRES -->
<div class="wrap section">
  <div class="sec-title">🌶 <span class="accent">Par genre</span></div>
  <div class="sec-divider"></div>
  <div class="cat-pills">
    <?php foreach($GENRES as $k=>$l):?>
    <a href="movies.php?genre=<?=$k?>" class="cpill"><?=$l?></a>
    <?php endforeach;?>
  </div>
</div>

<!-- SÉRIES POPULAIRES -->
<div class="wrap section">
  <div class="sec-head"><div class="sec-title">📺 <span class="accent">Séries</span> populaires</div><a href="series.php" class="sec-more">Toutes les séries →</a></div>
  <div class="sec-divider"></div>
  <?php $series=tmdbTV(1,'fr-FR');?>
  <div class="grid">
    <?php foreach(array_slice($series,0,4) as $m):
      $title=htmlspecialchars($m['name']??$m['original_name']??'');
      $year=substr($m['first_air_date']??'',0,4);
      $rating=round($m['vote_average']??0,1);
      $poster=tmdbPoster($m['poster_path']??null,'w300');
    ?>
    <a href="detail.php?id=<?=$m['id']?>&type=tv" class="mcard">
      <div class="mcard-poster">
        <?php if($poster):?><img src="<?=$poster?>" alt="<?=$title?>" loading="lazy"/>
        <?php else:?><div class="mcard-poster-empty">📺</div><?php endif;?>
        <span class="badge-type type-serie">Série</span>
      </div>
      <div class="mcard-body">
        <div class="mcard-title"><?=$title?></div>
        <div class="mcard-meta"><span class="mcard-rating">⭐ <?=$rating?></span><?php if($year):?><span class="mcard-year"><?=$year?></span><?php endif;?></div>
        <button class="mcard-btn">Voir + 🌶</button>
      </div>
    </a>
    <?php endforeach;?>
  </div>
</div>
<?php require_once '_footer.php';?>
