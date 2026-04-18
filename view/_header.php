<?php
if(session_status()===PHP_SESSION_NONE) session_start();
$user    = $_SESSION['user'] ?? null;
$isAdmin = $user && $user['role']==='admin';
$activePage = $activePage ?? '';
$pageTitle  = $pageTitle  ?? 'Felflix 🌶';
$protocol = (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off')?'https':'http';
$base     = $protocol.'://'.$_SERVER['HTTP_HOST'].dirname(dirname($_SERVER['SCRIPT_NAME']));
$q        = htmlspecialchars($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?=htmlspecialchars($pageTitle)?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?=$base?>/assets/css/style.css"/>
  <link rel="stylesheet" href="<?=$base?>/assets/css/chat.css"/>
</head>
<body>
<canvas id="bg-canvas"></canvas>

<nav id="navbar">
  <div class="nav-wrap">
    <!-- Logo -->
    <a href="<?=$base?>/view/index.php" class="nav-logo">
      <span class="nav-logo-emoji" style="color:#e63946;filter:drop-shadow(0 0 8px rgba(230,57,70,.7))">🌶</span>
      <span class="nav-logo-text">Felflix</span>
      <span class="nav-logo-badge">HOT 🔥</span>
    </a>

    <!-- Search -->
    <div class="nav-search d-none d-lg-block">
      <form action="<?=$base?>/view/search.php" method="GET">
        <i class="fas fa-search search-icon"></i>
        <input name="q" type="search" placeholder="Film, série, acteur..." value="<?=$q?>"/>
      </form>
    </div>

    <!-- Links -->
    <div class="nav-links">
      <a href="<?=$base?>/view/index.php"     class="nl <?=$activePage==='home'     ?'active':''?>"><i class="fas fa-home" style="font-size:.8rem"></i> eddar</a>
      <a href="<?=$base?>/view/movies.php"    class="nl <?=$activePage==='movies'   ?'active':''?>">🎬 Aflam</a>
      <a href="<?=$base?>/view/series.php"    class="nl <?=$activePage==='series'   ?'active':''?>">📺 Mosalsalat</a>
      <a href="<?=$base?>/view/community.php" class="nl <?=$activePage==='community'?'active':''?>">💬 Communauté</a>
      <?php if($user): ?>
      <a href="<?=$base?>/view/mood_jar.php" class="nl <?=$activePage==='moodjar'?'active':''?>" style="color: #ff6a00; font-weight: bold;">🫙 Mood Jar</a>
      <a href="<?=$base?>/view/watchlist.php" class="nl <?=$activePage==='watchlist'?'active':''?>">📋 Lista mta3i</a>
      <a href="<?=$base?>/view/profile.php"   class="nl <?=$activePage==='profile'  ?'active':''?>">👤 Profili</a>
      <?php endif; ?>
    </div>

    <!-- User -->
    <div class="nav-user">
      <?php if($user): ?>
        <?php if($isAdmin): ?>
        <a href="<?=$base?>/view/admin.php" class="btn-nav btn-outline" style="font-size:.75rem">⚙️ Admin</a>
        <?php endif; ?>
        <a href="<?=$base?>/view/profile.php" class="nav-avatar" title="<?=htmlspecialchars($user['nom'])?>"><?=htmlspecialchars($user['avatar']??'🌶')?></a>
        <a href="<?=$base?>/controller/logout.php" class="btn-nav btn-outline">5roj</a>
      <?php else: ?>
        <a href="<?=$base?>/view/login.php"  class="btn-nav btn-outline <?=$activePage==='login' ?'active':''?>">connecti</a>
        <a href="<?=$base?>/view/signup.php" class="btn-nav btn-fill   <?=$activePage==='signup'?'active':''?>">sajel ro7k</a>
      <?php endif; ?>
      <button class="nav-ham" onclick="toggleDrawer()"><i class="fas fa-bars"></i></button>
    </div>
  </div>
</nav>

<!-- Mobile drawer -->
<div class="m-drawer" id="mDrawer">
  <div class="m-search">
    <form action="<?=$base?>/view/search.php" method="GET">
      <i class="fas fa-search si"></i>
      <input name="q" type="search" placeholder="Fettich 3la film walla mosalsala..." value="<?=$q?>"/>
    </form>
  </div>
  <div class="m-links">
    <a href="<?=$base?>/view/index.php"     class="nl">🏠 Home</a>
    <a href="<?=$base?>/view/movies.php"    class="nl">🎬 Aflam</a>
    <a href="<?=$base?>/view/series.php"    class="nl">📺 Mosalsalat</a>
    <a href="<?=$base?>/view/community.php" class="nl">💬 Communauté</a>
    <?php if($user): ?>
    <a href="<?=$base?>/view/watchlist.php" class="nl">📋 Lista mta3i</a>
    <a href="<?=$base?>/view/profile.php"   class="nl">👤 Profili</a>
    <?php if($isAdmin): ?><a href="<?=$base?>/view/admin.php" class="nl">⚙️ Admin</a><?php endif; ?>
    <a href="<?=$base?>/controller/logout.php" class="nl" style="color:#ff6666">🚪 5roj</a>
    <?php else: ?>
    <a href="<?=$base?>/view/login.php"  class="nl">D5ol</a>
    <a href="<?=$base?>/view/signup.php" class="nl">Inscri ro7ek 🌶</a>
    <?php endif; ?>
  </div>
</div>
<script>
function toggleDrawer(){document.getElementById('mDrawer').classList.toggle('open');}
window.addEventListener('scroll',()=>{document.getElementById('navbar').classList.toggle('scrolled',scrollY>40);});
</script>
