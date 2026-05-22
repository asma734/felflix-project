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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title><?=htmlspecialchars($pageTitle)?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?=$base?>/assets/css/style.css?v=<?=time()?>"/>
  <link rel="stylesheet" href="<?=$base?>/assets/css/chat.css?v=<?=time()?>"/>
  <style>
    html, body { overflow-x: hidden !important; width: 100vw !important; position: relative; }
    
    /* Auto-completion suggestions styles */
    .nav-search, .m-search {
      position: relative !important;
      overflow: visible !important;
    }
    .nav-wrap, #navbar {
      overflow: visible !important;
    }
    .suggestions-dropdown {
      position: absolute;
      top: 100%;
      left: 0;
      width: 100%;
      min-width: 320px;
      max-height: 420px;
      overflow-y: auto;
      background: #110c1e !important; /* Fully solid, high-contrast dark background */
      border: 2px solid rgba(230, 57, 70, 0.7) !important; /* Extremely visible Felflix Red border */
      border-radius: 16px;
      margin-top: 12px;
      z-index: 999999 !important; /* Ensure it stays on top of everything! */
      box-shadow: 0 25px 60px rgba(0,0,0,0.85), 0 0 20px rgba(230, 57, 70, 0.2) !important;
      display: none;
      padding: 8px 0;
    }
    .suggestions-dropdown.open {
      display: block;
    }
    .suggestion-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 16px;
      color: #fff;
      text-decoration: none;
      transition: all 0.2s ease-in-out;
      border-bottom: 1px solid rgba(255,255,255,0.03);
    }
    .suggestion-item:last-child {
      border-bottom: none;
    }
    .suggestion-item:hover {
      background: rgba(230, 57, 70, 0.15) !important; /* High-contrast Red hover background */
      color: #fff !important;
      transform: translateX(6px);
    }
    .suggestion-poster {
      width: 32px;
      height: 46px;
      object-fit: cover;
      border-radius: 8px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
    }
    .suggestion-info {
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .suggestion-title {
      font-size: 0.85rem;
      font-weight: 700;
      margin: 0;
      color: #fff;
    }
    .suggestion-meta {
      font-size: 0.72rem;
      color: rgba(255,255,255,0.6);
      margin-top: 2px;
    }
    .suggestion-rating {
      background: rgba(245, 166, 35, 0.15);
      color: #f5a623;
      padding: 3px 8px;
      border-radius: 8px;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: -0.2px;
    }
  </style>
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
      <a href="<?=$base?>/view/tunisian.php"  class="nl <?=$activePage==='tunisian' ?'active':''?>">🇹🇳 Tounssi</a>
      <a href="<?=$base?>/view/community.php" class="nl <?=$activePage==='community'?'active':''?>">💬 Communauté</a>
      <?php if($user): ?>
      <a href="<?=$base?>/view/mood_jar.php" class="nl <?=$activePage==='moodjar'?'active':''?>" style="color: #ff6a00; font-weight: bold;">🫙 Mood Jar</a>
      <a href="<?=$base?>/view/watchlist.php" class="nl <?=$activePage==='watchlist'?'active':''?>">📋 Lista mta3i</a>
      <a href="<?=$base?>/view/profile.php"   class="nl <?=$activePage==='profile'  ?'active':''?>">👤 Profili</a>
      <?php endif; ?>
    </div>

    <!-- User -->
    <div class="nav-user">
      <!-- Theme Toggle -->
      <button class="nl" onclick="toggleTheme()" title="Badel l'ambiance 🌓" style="font-size: 1.2rem; margin-right: 5px;">🌓</button>

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

<script>
  function toggleTheme() {
    const isLight = document.body.classList.toggle('light-theme');
    localStorage.setItem('felflix-theme', isLight ? 'light' : 'dark');
  }
  // Load theme preference
  if (localStorage.getItem('felflix-theme') === 'light') {
    document.body.classList.add('light-theme');
  }
</script>

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
    <a href="<?=$base?>/view/tunisian.php"  class="nl">🇹🇳 Tounssi</a>
    <a href="<?=$base?>/view/community.php" class="nl">💬 Communauté</a>
    <?php if($user): ?>
    <a href="<?=$base?>/view/mood_jar.php"  class="nl" style="color: #ff6a00; font-weight: bold;">🫙 Mood Jar</a>
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

// Autocomplete Realtime suggestions JS
document.addEventListener('DOMContentLoaded', () => {
    const searchInputs = document.querySelectorAll('.nav-search input[name="q"], .m-search input[name="q"]');
    
    searchInputs.forEach(input => {
        const dropdown = document.createElement('div');
        dropdown.className = 'suggestions-dropdown';
        input.parentNode.appendChild(dropdown);
        
        let debounceTimer;
        
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const query = input.value.trim();
            
            if (query.length < 1) {
                dropdown.innerHTML = '';
                dropdown.classList.remove('open');
                return;
            }
            
            debounceTimer = setTimeout(() => {
                fetch(`<?=$base?>/controller/search_suggestions.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const poster = item.poster_url ? item.poster_url : 'https://placehold.co/32x46?text=🎬';
                                const itemEl = document.createElement('a');
                                itemEl.className = 'suggestion-item';
                                itemEl.href = `<?=$base?>/view/detail.php?id=${item.imdb_id}`;
                                
                                itemEl.innerHTML = `
                                    <img src="${poster}" alt="${item.title}" class="suggestion-poster" onerror="this.src='https://placehold.co/32x46?text=🎬'">
                                    <div class="suggestion-info">
                                        <h4 class="suggestion-title">${item.title}</h4>
                                        <span class="suggestion-meta">${item.type === 'movie' ? '🎬 Film' : '📺 Série'} • ${item.year}</span>
                                    </div>
                                    ${item.rating ? `<span class="suggestion-rating">⭐ ${parseFloat(item.rating).toFixed(1)}</span>` : ''}
                                `;
                                dropdown.appendChild(itemEl);
                            });
                            dropdown.classList.add('open');
                        } else {
                            dropdown.classList.remove('open');
                        }
                    })
                    .catch(() => {
                        dropdown.classList.remove('open');
                    });
            }, 150); // Fast 150ms debounce
        });
        
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    });
});
</script>
