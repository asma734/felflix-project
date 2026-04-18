<?php
session_start();if(!isset($_SESSION['user'])){header('Location: login.php');exit;}
require_once '../controller/traitement.php';
$user=$_SESSION['user'];$success='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nom=trim($_POST['nom']??'');$email=trim($_POST['email']??'');$avatar=$_POST['avatar']??$user['avatar'];$bio=trim($_POST['bio']??'');
    if(!$nom||!$email){$error='Remplis le nom et l\'email!';}
    else{updateUser($cnx,$user['id'],$nom,$email,$avatar,$bio);$_SESSION['user']=getUserById($cnx,$user['id']);$user=$_SESSION['user'];$success='Profil mis à jour ✅';}
}
$wlCnt=$cnx->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id=?");$wlCnt->execute([$user['id']]);$wlCnt=$wlCnt->fetchColumn();
$postCnt=$cnx->prepare("SELECT COUNT(*) FROM posts WHERE user_id=? AND is_deleted=0");$postCnt->execute([$user['id']]);$postCnt=$postCnt->fetchColumn();

// Fetch User's Posts
$postsStmt=$cnx->prepare("SELECT * FROM posts WHERE user_id=? AND is_deleted=0 ORDER BY created_at DESC LIMIT 5");
$postsStmt->execute([$user['id']]);
$userPosts=$postsStmt->fetchAll();

// Handle new post from profile
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new_post'])){
    $content = trim($_POST['post_content']??'');
    if($content){
        $cnx->prepare("INSERT INTO posts(user_id,content) VALUES(?,?)")->execute([$user['id'],$content]);
        header("Location: profile.php");
        exit;
    }
}

$pageTitle='Mon profil — Felflix';$activePage='profile';require_once '_header.php';
?>
<div class="wrap" style="padding-top:90px;padding-bottom:60px;max-width:640px">
  <div class="p-hero">
    <div class="p-avatar"><?=htmlspecialchars($user['avatar']??'🌶')?></div>
    <div class="p-name"><?=htmlspecialchars($user['nom'])?></div>
    <div style="color:var(--muted);font-size:.84rem;margin-bottom:6px"><?=htmlspecialchars($user['email'])?></div>
    <?php if(!empty($user['bio'])):?><p style="color:var(--muted);font-size:.86rem;max-width:420px;margin:0 auto 12px;line-height:1.6"><?=htmlspecialchars($user['bio'])?></p><?php endif;?>
    <span class="p-badge <?=$user['role']==='admin'?'badge-admin':'badge-user'?>"><?=$user['role']==='admin'?'⚙️ Administrateur':'👤 Membre'?></span>
    <div class="p-stats">
      <div class="pstat"><div class="n"><?=$wlCnt?></div><div class="l">Ma liste</div></div>
      <div class="pstat"><div class="n"><?=$postCnt?></div><div class="l">Posts</div></div>
      <div class="pstat"><div class="n">🌶</div><div class="l">Niveau chaud</div></div>
    </div>
  </div>

  <div class="form-card" style="margin-bottom:16px;">
    <form action="search_friends.php" method="GET" style="display:flex;gap:10px;">
        <input type="text" name="q" placeholder="Fettich 3la s7abek (Facebook style)..." class="finput" style="margin:0;flex:1;" required>
        <button type="submit" class="btn-hero btn-sm">🔍</button>
    </form>
  </div>
  
  <div class="form-card" style="margin-bottom:16px; background:rgba(230,57,70,0.05);">
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:#e63946;margin-bottom:10px">💬 3abbar 3la feeling mte3ek (Post)</h3>
    <form method="POST">
        <input type="hidden" name="new_post" value="1">
        <textarea name="post_content" class="finput" placeholder="Wich theb te7ki 3la film walla mosalsala?... 🌶️" rows="3" required style="resize:none; padding:12px;"></textarea>
        <button type="submit" class="btn-hero" style="width:100%; border-radius:10px;">Poster 🌶️</button>
    </form>
  </div>
  <div class="form-card" style="margin-bottom:16px">
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;margin-bottom:18px">Modifier mon profil ✏️</h3>
    <?php if($error):?><div class="err-msg" style="display:block"><?=htmlspecialchars($error)?></div><?php endif;?>
    <?php if($success):?><div class="ok-msg" style="display:block"><?=htmlspecialchars($success)?></div><?php endif;?>
    <form method="POST">
      <p style="color:var(--muted);font-size:.8rem;margin-bottom:8px">Avatar</p>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <?php foreach(['🌶','🔥','🍿','🎬','⭐','🎭','🦁','🐉'] as $av):?>
        <label style="cursor:pointer">
          <input type="radio" name="avatar" value="<?=$av?>" <?=($user['avatar']??'🌶')===$av?'checked':''?> style="display:none"/>
          <span style="display:inline-block;font-size:1.4rem;padding:6px 10px;border-radius:10px;border:2px solid <?=($user['avatar']??'🌶')===$av?'var(--red)':'var(--border)'?>;display:inline-block;cursor:pointer"><?=$av?></span>
        </label>
        <?php endforeach;?>
      </div>
      <input class="finput" name="nom"   type="text"  required placeholder="Nom..."   value="<?=htmlspecialchars($user['nom'])?>"/>
      <input class="finput" name="email" type="email" required placeholder="Email..." value="<?=htmlspecialchars($user['email'])?>"/>
      <input class="finput" name="bio"   type="text"  placeholder="Ta bio..."         value="<?=htmlspecialchars($user['bio']??'')?>"/>
      <button type="submit" class="btn-hero" style="width:100%;padding:12px;border-radius:12px">Sauvegarder 🌶</button>
    </form>
  </div>
  <div style="margin-top:40px;">
      <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.4rem;color:#fff;margin-bottom:20px;">📝 Mes Récentes Publications</h3>
      <?php if(empty($userPosts)):?>
      <div class="empty-s" style="padding:20px;"><span class="ei">💬</span><p>Tu n'as encore rien posté !</p></div>
      <?php else: foreach($userPosts as $p):?>
      <div class="post-card" style="margin-bottom:15px; text-align:left;">
          <div class="post-text"><?=htmlspecialchars($p['content'])?></div>
          <div class="post-time" style="font-size:0.75rem; color:var(--dim); margin-top:10px;"><?=substr($p['created_at'],0,16)?></div>
      </div>
      <?php endforeach; endif;?>
  </div>

  <div style="display:flex;flex-direction:column;gap:10px; margin-top:30px;">
    <?php if($user['role']==='admin'):?><a href="admin.php" class="btn-hero" style="display:block;text-align:center;text-decoration:none;border-radius:12px;padding:12px">⚙️ Panel Admin</a><?php endif;?>
    <a href="watchlist.php" class="btn-ghost" style="display:block;text-align:center;text-decoration:none;border-radius:12px;padding:12px">📋 Ma liste (<?=$wlCnt?>)</a>
    <a href="community.php" class="btn-ghost" style="display:block;text-align:center;text-decoration:none;border-radius:12px;padding:12px">💬 Communauté</a>
    <a href="../controller/logout.php" style="display:block;text-align:center;text-decoration:none;border-radius:12px;padding:12px;background:var(--glass);border:1px solid var(--border);color:var(--muted)">🚪 Déconnexion</a>
  </div>
</div>
<?php require_once '_footer.php';?>
