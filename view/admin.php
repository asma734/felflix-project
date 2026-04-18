<?php
session_start();
if(!isset($_SESSION['user'])||$_SESSION['user']['role']!=='admin'){header('Location: index.php');exit;}
require_once '../controller/traitement.php';
$user=$_SESSION['user'];
// Actions
if(isset($_GET['del_user'])&&(int)$_GET['del_user']!==$user['id']){deleteUser($cnx,(int)$_GET['del_user']);header('Location: admin.php?tab=users');exit;}
if(isset($_GET['del_comment'])){$cnx->prepare("UPDATE comments SET is_deleted=1 WHERE id=?")->execute([(int)$_GET['del_comment']]);header('Location: admin.php?tab=comments');exit;}
if(isset($_GET['restore_comment'])){$cnx->prepare("UPDATE comments SET is_deleted=0 WHERE id=?")->execute([(int)$_GET['restore_comment']]);header('Location: admin.php?tab=comments');exit;}
if(isset($_GET['del_post'])){$cnx->prepare("UPDATE posts SET is_deleted=1 WHERE id=?")->execute([(int)$_GET['del_post']]);header('Location: admin.php?tab=posts');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['edit_uid'])){
    $eid=(int)$_POST['edit_uid'];
    updateUser($cnx,$eid,$_POST['nom']??'',$_POST['email']??'',$_POST['avatar']??null,$_POST['bio']??null);
    if(isset($_POST['role'])) $cnx->prepare("UPDATE users SET role=? WHERE id=?")->execute([$_POST['role'],$eid]);
    header('Location: admin.php?tab=users');exit;
}
$tab=$_GET['tab']??'dashboard';$s=trim($_GET['s']??'');
$totalU=countUsers($cnx);
$totalC=$cnx->query("SELECT COUNT(*) FROM comments WHERE is_deleted=0")->fetchColumn();
$totalP=$cnx->query("SELECT COUNT(*) FROM posts WHERE is_deleted=0")->fetchColumn();
$totalWL=$cnx->query("SELECT COUNT(*) FROM watchlist")->fetchColumn();
$flagged=$cnx->query("SELECT COUNT(*) FROM comments WHERE is_deleted=1")->fetchColumn();
$editUser=isset($_GET['edit'])?getUserById($cnx,(int)$_GET['edit']):null;
$pageTitle='Admin — Felflix';$activePage='admin';require_once '_header.php';
?>
<div class="wrap" style="padding-top:90px;padding-bottom:60px">
  <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:2rem;color:#fff;margin-bottom:6px">⚙️ Panel Admin</h1>
  <p style="color:var(--muted);font-size:.84rem;margin-bottom:28px">Salam <?=htmlspecialchars($user['nom'])?> — Gestion Felflix 🌶 🇺🇳</p>
  <div class="admin-stats">
    <div class="astat"><div class="n"><?=$totalU?></div><div class="l">Utilisateurs</div></div>
    <div class="astat"><div class="n"><?=$totalC?></div><div class="l">Commentaires</div></div>
    <div class="astat"><div class="n"><?=$totalP?></div><div class="l">Posts</div></div>
    <div class="astat"><div class="n"><?=$totalWL?></div><div class="l">Watchlists</div></div>
    <div class="astat" style="border-color:rgba(234,179,8,.3)"><div class="n" style="color:#fde047"><?=$flagged?></div><div class="l">Signalés</div></div>
  </div>
  <div class="type-tabs" style="margin-bottom:28px">
    <a href="?tab=dashboard" class="ttab <?=$tab==='dashboard'?'active':''?>" style="text-decoration:none;color:inherit">📊 Dashboard</a>
    <a href="?tab=users"     class="ttab <?=$tab==='users'    ?'active':''?>" style="text-decoration:none;color:inherit">👥 Utilisateurs</a>
    <a href="?tab=comments"  class="ttab <?=$tab==='comments' ?'active':''?>" style="text-decoration:none;color:inherit">💬 Commentaires</a>
    <a href="?tab=posts"     class="ttab <?=$tab==='posts'    ?'active':''?>" style="text-decoration:none;color:inherit">📢 Posts</a>
  </div>

  <?php if($tab==='dashboard'):?>
  <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px">
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;color:#fff;margin-bottom:16px">Derniers inscrits</h3>
    <?php foreach($cnx->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll() as $u):?>
    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
      <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--red),var(--orange));display:flex;align-items:center;justify-content:center;font-size:1.1rem"><?=htmlspecialchars($u['avatar']??'🌶')?></div>
      <div><div style="font-weight:600;font-size:.88rem;color:#fff"><?=htmlspecialchars($u['nom'])?></div><div style="font-size:.72rem;color:var(--dim)"><?=htmlspecialchars($u['email'])?> · <?=$u['role']?></div></div>
      <div style="margin-left:auto;font-size:.72rem;color:var(--dim)"><?=substr($u['created_at']??'',0,10)?></div>
    </div>
    <?php endforeach;?>
  </div>

  <?php elseif($tab==='users'):?>
  <form method="GET" style="display:flex;gap:8px;max-width:400px;margin-bottom:20px">
    <input type="hidden" name="tab" value="users"/>
    <input class="finput" name="s" placeholder="Cherche un utilisateur..." value="<?=htmlspecialchars($s)?>" style="margin:0"/>
    <button type="submit" class="btn-hero btn-sm" style="white-space:nowrap">Go</button>
  </form>
  <?php $users=$s?searchUsers($cnx,$s):getAllUsers($cnx);?>
  <div class="tbl-wrap"><table class="atbl">
    <thead><tr><th>Avatar</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Inscrit le</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if(empty($users)):?><tr><td colspan="6" style="text-align:center;padding:20px;color:var(--dim)">Aucun utilisateur</td></tr>
    <?php else: foreach($users as $u):?>
    <tr>
      <td style="font-size:1.2rem"><?=htmlspecialchars($u->avatar??'🌶')?></td>
      <td style="color:#fff;font-weight:600"><?=htmlspecialchars($u->nom)?></td>
      <td><?=htmlspecialchars($u->email)?></td>
      <td><span style="padding:3px 10px;border-radius:6px;font-size:.72rem;font-weight:700;<?=$u->role==='admin'?'background:rgba(230,57,70,.2);color:#ff9999':'background:rgba(255,255,255,.07);color:var(--muted)'?>"><?=$u->role==='admin'?'Admin':'Membre'?></span></td>
      <td style="font-size:.75rem"><?=substr($u->created_at??'',0,10)?></td>
      <td>
        <a href="?tab=users&edit=<?=$u->id?>" class="abtn ab-edit">Modifier</a>
        <?php if($u->id!=$user['id']):?><a href="?tab=users&del_user=<?=$u->id?>" class="abtn ab-del" onclick="return confirm('Supprimer?')">Supprimer</a><?php endif;?>
      </td>
    </tr>
    <?php endforeach;endif;?>
    </tbody>
  </table></div>

  <?php elseif($tab==='comments'):?>
  <?php $showDel=isset($_GET['show_del']);?>
  <div style="display:flex;gap:8px;margin-bottom:16px">
    <a href="?tab=comments" class="abtn ab-edit <?=!$showDel?'':'ab-warn'?>">Actifs</a>
    <a href="?tab=comments&show_del=1" class="abtn ab-warn">Supprimés (<?=$flagged?>)</a>
  </div>
  <?php $sql=$showDel?"SELECT c.*,u.nom FROM comments c JOIN users u ON u.id=c.user_id WHERE c.is_deleted=1 ORDER BY c.created_at DESC LIMIT 50":"SELECT c.*,u.nom FROM comments c JOIN users u ON u.id=c.user_id WHERE c.is_deleted=0 ORDER BY c.created_at DESC LIMIT 50";$coms=$cnx->query($sql)->fetchAll();?>
  <div class="tbl-wrap"><table class="atbl">
    <thead><tr><th>Auteur</th><th>Contenu</th><th>Film/Série</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
    <?php if(empty($coms)):?><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--dim)">Aucun commentaire</td></tr>
    <?php else: foreach($coms as $c):?>
    <tr>
      <td style="color:#fff;font-weight:600"><?=htmlspecialchars($c['nom']??'')?></td>
      <td style="max-width:300px"><?=htmlspecialchars(substr($c['content']??'',0,80))?>...</td>
      <td><a href="detail.php?id=<?=$c['tmdb_id']?>&type=<?=$c['tmdb_type']?>" style="color:var(--red);font-size:.75rem">Voir</a></td>
      <td style="font-size:.75rem"><?=substr($c['created_at']??'',0,10)?></td>
      <td>
        <?php if($showDel):?><a href="?tab=comments&restore_comment=<?=$c['id']?>" class="abtn ab-edit">Restaurer</a>
        <?php else:?><a href="?tab=comments&del_comment=<?=$c['id']?>" class="abtn ab-del" onclick="return confirm('Supprimer?')">Supprimer</a><?php endif;?>
      </td>
    </tr>
    <?php endforeach;endif;?>
    </tbody>
  </table></div>

  <?php elseif($tab==='posts'):?>
  <?php $posts=$cnx->query("SELECT p.*,u.nom FROM posts p JOIN users u ON u.id=p.user_id WHERE p.is_deleted=0 ORDER BY p.created_at DESC LIMIT 50")->fetchAll();?>
  <div class="tbl-wrap"><table class="atbl">
    <thead><tr><th>Auteur</th><th>Post</th><th>Film lié</th><th>Likes</th><th>Date</th><th>Action</th></tr></thead>
    <tbody>
    <?php if(empty($posts)):?><tr><td colspan="6" style="text-align:center;padding:20px;color:var(--dim)">Aucun post</td></tr>
    <?php else: foreach($posts as $p):?>
    <tr>
      <td style="color:#fff;font-weight:600"><?=htmlspecialchars($p['nom']??'')?></td>
      <td style="max-width:280px"><?=htmlspecialchars(substr($p['content']??'',0,60))?>...</td>
      <td style="font-size:.75rem"><?=$p['tmdb_title']?htmlspecialchars($p['tmdb_title']):'-'?></td>
      <td>❤️ <?=$p['likes']??0?></td>
      <td style="font-size:.75rem"><?=substr($p['created_at']??'',0,10)?></td>
      <td><a href="?tab=posts&del_post=<?=$p['id']?>" class="abtn ab-del" onclick="return confirm('Supprimer?')">Supprimer</a></td>
    </tr>
    <?php endforeach;endif;?>
    </tbody>
  </table></div>
  <?php endif;?>
</div>

<!-- Edit user modal -->
<?php if($editUser):?>
<div style="position:fixed;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(12px);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px">
  <div class="form-card" style="max-width:420px;width:100%;position:relative;max-height:90vh;overflow-y:auto">
    <a href="?tab=users" style="position:absolute;top:12px;right:12px;color:var(--muted);text-decoration:none;font-size:1.2rem">✕</a>
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:#fff;margin-bottom:18px">Modifier <?=htmlspecialchars($editUser['nom'])?></h3>
    <form method="POST">
      <input type="hidden" name="edit_uid" value="<?=$editUser['id']?>"/>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <?php foreach(['🌶','🔥','🍿','🎬','⭐','🎭','🦁','🐉'] as $av):?>
        <label><input type="radio" name="avatar" value="<?=$av?>" <?=($editUser['avatar']??'🌶')===$av?'checked':''?> style="display:none"/><span style="font-size:1.3rem;padding:6px;border-radius:8px;border:2px solid <?=($editUser['avatar']??'🌶')===$av?'var(--red)':'var(--border)'?>;display:inline-block;cursor:pointer"><?=$av?></span></label>
        <?php endforeach;?>
      </div>
      <input class="finput" name="nom"   type="text"  required value="<?=htmlspecialchars($editUser['nom'])?>"/>
      <input class="finput" name="email" type="email" required value="<?=htmlspecialchars($editUser['email'])?>"/>
      <input class="finput" name="bio"   type="text"  value="<?=htmlspecialchars($editUser['bio']??'')?>"/>
      <select name="role" class="finput" style="cursor:pointer">
        <option value="user"  <?=$editUser['role']==='user' ?'selected':''?>>👤 Membre</option>
        <option value="admin" <?=$editUser['role']==='admin'?'selected':''?>>⚙️ Admin</option>
      </select>
      <button type="submit" class="btn-hero" style="width:100%;padding:12px;border-radius:12px">Sauvegarder ✅</button>
    </form>
  </div>
</div>
<?php endif;?>
<?php require_once '_footer.php';?>
