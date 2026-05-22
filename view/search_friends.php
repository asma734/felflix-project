<?php
session_start();
if(!isset($_SESSION['user'])){ header('Location: login.php'); exit; }
require_once '../controller/traitement.php';
$user = $_SESSION['user'];
$q = trim($_GET['q'] ?? '');

$results = [];
if($q) {
    try {
        $stmt = $cnx->prepare("SELECT * FROM users WHERE (nom LIKE ? OR email LIKE ?) AND id != ?");
        $stmt->execute(["%$q%", "%$q%", $user['id']]);
        $results = $stmt->fetchAll();
    } catch(PDOException $e) {}
}

if(isset($_GET['add_friend'])) {
    $f_id = (int)$_GET['add_friend'];
    try {
        $cnx->prepare("INSERT IGNORE INTO friendships(user_id, friend_id, status) VALUES(?, ?, 'accepted')")->execute([$user['id'], $f_id]);
        $cnx->prepare("INSERT IGNORE INTO friendships(user_id, friend_id, status) VALUES(?, ?, 'accepted')")->execute([$f_id, $user['id']]); // Mutual for simplicity
    } catch(PDOException $e) {}
    header("Location: search_friends.php?q=" . urlencode($q));
    exit;
}

$pageTitle = 'Chercher des amis — Felflix';
$activePage = 'profile';
require_once '_header.php';
?>
<div class="wrap" style="padding-top:90px;padding-bottom:60px;max-width:800px;margin:0 auto;">
  <h2 style="font-family:'Syne',sans-serif; color:#fff; margin-bottom:20px;">🔍 Fettich 3la s7abek</h2>
  
  <form action="search_friends.php" method="GET" style="display:flex; gap:10px; margin-bottom: 30px;">
      <input type="text" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Nom ou Email..." class="finput" style="flex:1;" required>
      <button type="submit" class="btn-hero">Chercher</button>
  </form>

  <?php if($q): ?>
      <h3 style="color:var(--muted); font-size:1rem; margin-bottom:20px;">Résultats pour "<?=htmlspecialchars($q)?>"</h3>
      <?php if(empty($results)): ?>
          <div class="empty-s"><p>Ma famma 7ad b hal esm! 🤷‍♂️</p></div>
      <?php else: ?>
          <div style="display:grid; gap:15px;">
              <?php foreach($results as $r): 
                  // Check friend status
                  $fchk = $cnx->prepare("SELECT * FROM friendships WHERE user_id=? AND friend_id=?");
                  $fchk->execute([$user['id'], $r['id']]);
                  $isFriend = (bool)$fchk->fetch();
              ?>
              <div class="post-card" style="display:flex; justify-content:space-between; align-items:center;">
                  <div style="display:flex; gap:15px; align-items:center;">
                      <div class="post-av" style="font-size:2rem; width:50px; height:50px; border-radius:50%; background:var(--glass); display:flex; align-items:center; justify-content:center;"><?=htmlspecialchars($r['avatar']??'🌶')?></div>
                      <div>
                          <div style="color:#fff; font-weight:bold; font-size:1.1rem;"><?=htmlspecialchars($r['nom'])?></div>
                          <div style="color:var(--muted); font-size:0.85rem;"><?=htmlspecialchars($r['email'])?></div>
                      </div>
                  </div>
                  <div>
                      <?php if($isFriend): ?>
                         <span style="color:var(--green); font-weight:bold;">✓ Sadi9ek</span>
                      <?php else: ?>
                         <a href="?q=<?=urlencode($q)?>&add_friend=<?=$r['id']?>" class="btn-hero btn-sm">+ Ajouter</a>
                      <?php endif; ?>
                  </div>
              </div>
              <?php endforeach; ?>
          </div>
      <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once '_footer.php'; ?>
