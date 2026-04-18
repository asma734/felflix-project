<?php
session_start();require_once '../controller/traitement.php';
$user=$_SESSION['user']??null;
$tmdb_id=(int)($_GET['tmdb_id']??0);
$tmdb_type=isset($_GET['tmdb_type'])&&in_array($_GET['tmdb_type'],['movie','tv'])?$_GET['tmdb_type']:'movie';
$tmdb_title=htmlspecialchars($_GET['title']??'');
// Handle post
if($user&&$_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['content'])){
    $txt=trim($_POST['content']);
    if($txt){
        $tid=(int)($_POST['tmdb_id']??0);$ttype=$_POST['tmdb_type']??'movie';$ttitle=trim($_POST['tmdb_title']??'');
        $cnx->prepare("INSERT INTO posts(user_id,content,tmdb_id,tmdb_title,tmdb_type) VALUES(?,?,?,?,?)")->execute([$user['id'],$txt,$tid?:null,$ttitle?:null,$ttype]);
        header('Location: community.php');exit;
    }
}
if($user&&isset($_GET['del'])){
    $pid=(int)$_GET['del'];
    if($user['role']==='admin') $cnx->prepare("UPDATE posts SET is_deleted=1 WHERE id=?")->execute([$pid]);
    else $cnx->prepare("UPDATE posts SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$pid,$user['id']]);
    header('Location: community.php');exit;
}
if($user&&isset($_GET['like'])){
    $pid=(int)$_GET['like'];
    try{$cnx->prepare("INSERT INTO post_likes(user_id,post_id) VALUES(?,?)")->execute([$user['id'],$pid]);$cnx->prepare("UPDATE posts SET likes=likes+1 WHERE id=?")->execute([$pid]);}catch(PDOException $e){}
    header('Location: community.php');exit;
}
// Query safe — si is_deleted n'existe pas encore, COALESCE le gère
try{
    $posts=$cnx->query("SELECT p.*,u.nom,u.avatar FROM posts p JOIN users u ON u.id=p.user_id WHERE COALESCE(p.is_deleted,0)=0 ORDER BY p.created_at DESC LIMIT 50")->fetchAll();
}catch(PDOException $e){
    $posts=[];
}
$pageTitle='Communauté — Felflix';$activePage='community';require_once '_header.php';
?>
<div class="wrap" style="padding-top:90px;padding-bottom:60px;max-width:800px">
  <div class="sec-head"><div class="sec-title">💬 <span class="accent">Communauté</span></div></div>
  <p style="color:var(--muted);margin-bottom:28px;font-size:.9rem">Cheri ra2yik fi ay film walla mosalsala 🌶</p>
  <?php if($user):?>
  <div class="write-box">
    <div style="display:flex;gap:12px">
      <div class="post-av"><?=htmlspecialchars($user['avatar']??'🌶')?></div>
      <form method="POST" style="flex:1">
        <?php if($tmdb_id):?>
        <div style="background:rgba(230,57,70,.08);border:1px solid rgba(230,57,70,.2);border-radius:10px;padding:8px 12px;margin-bottom:10px;font-size:.82rem;color:#ff9999">🎬 Avis sur: <strong><?=$tmdb_title?></strong></div>
        <input type="hidden" name="tmdb_id" value="<?=$tmdb_id?>"/><input type="hidden" name="tmdb_type" value="<?=$tmdb_type?>"/><input type="hidden" name="tmdb_title" value="<?=$tmdb_title?>"/>
        <?php else:?>
        <input type="hidden" name="tmdb_id" value="0"/><input type="hidden" name="tmdb_type" value="movie"/><input type="hidden" name="tmdb_title" value=""/>
        <?php endif;?>
        <textarea name="content" rows="3" placeholder="Ton avis sur un film, une série... 🌶" style="width:100%;background:var(--glass);border:1px solid var(--border);color:var(--txt);border-radius:12px;padding:12px;font-family:'Inter',sans-serif;font-size:.9rem;resize:none;outline:none;margin-bottom:10px" onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'"></textarea>
        <div style="display:flex;justify-content:flex-end"><button type="submit" class="btn-hero btn-sm">Poster 🔥</button></div>
      </form>
    </div>
  </div>
  <?php else:?>
  <div class="write-box" style="text-align:center;padding:24px">
    <p style="color:var(--muted);margin-bottom:12px">D5ol bech t7achi 🌶</p>
    <a href="login.php" class="btn-hero btn-sm">D5ol</a>
  </div>
  <?php endif;?>
  <?php if(empty($posts)):?>
  <div class="empty-s"><span class="ei">💬</span><p>Mafammach posts — kunti l'awel! 🌶</p></div>
  <?php else: foreach($posts as $p):?>
  <div class="post-card">
    <div class="post-head">
      <div class="post-av"><?=htmlspecialchars($p['avatar']??'🌶')?></div>
      <div><div class="post-user"><?=htmlspecialchars($p['nom']??'Anonyme')?></div><div class="post-time"><?=substr($p['created_at']??'',0,16)?></div></div>
      <?php if($user&&($user['id']==$p['user_id']||$user['role']==='admin')):?>
      <a href="?del=<?=$p['id']?>" class="act-btn del" style="margin-left:auto" onclick="return confirm('Supprimer?')">🗑️</a>
      <?php endif;?>
    </div>
    <?php if($p['tmdb_title']):?>
    <div style="margin-bottom:8px"><a href="detail.php?id=<?=$p['tmdb_id']?>&type=<?=$p['tmdb_type']?>" style="display:inline-flex;align-items:center;gap:6px;background:rgba(230,57,70,.08);border:1px solid rgba(230,57,70,.2);border-radius:8px;padding:4px 12px;font-size:.75rem;color:#ff9999;text-decoration:none"><?=$p['tmdb_type']==='tv'?'📺':'🎬'?> <?=htmlspecialchars($p['tmdb_title'])?></a></div>
    <?php endif;?>
    <p class="post-text"><?=htmlspecialchars($p['content'])?></p>
    <div class="post-actions"><a href="?like=<?=$p['id']?>" class="act-btn">❤️ <?=$p['likes']??0?></a></div>
  </div>
  <?php endforeach;endif;?>
</div>
<?php require_once '_footer.php';?>
