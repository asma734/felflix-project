<?php
session_start();if(isset($_SESSION['user'])){
    if($_SESSION['user']['role']==='admin'){header('Location: admin.php');exit;}
    header('Location: index.php');exit;
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once '../controller/traitement.php';
    $nom=trim($_POST['nom']??'');$email=trim($_POST['email']??'');$pwd=$_POST['password']??'';$avatar=$_POST['avatar']??'🌶';$bio=trim($_POST['bio']??'');$code=trim($_POST['admin_code']??'');
    if(!$nom||!$email||!$pwd){$error='Amiréh l7a9el kolou! 🌶';}
    elseif(strlen($pwd)<4){$error='Kalmet es-sir 9serha (4 7rof fi l2a9al)';}
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){$error='Email m3awej!';}
    else{
        $role=$code==='FELLIX2025'?'admin':'user';
        $r=addUser($cnx,['nom'=>$nom,'email'=>$email,'password'=>$pwd,'role'=>$role,'avatar'=>$avatar,'bio'=>$bio]);
        if($r['success']){
            $_SESSION['user']=getUserById($cnx,$r['id']);
            if($role==='admin'){header('Location: admin.php');exit;}
            header('Location: index.php');exit;
        }
        else{$error=$r['message'];}
    }
}
$pageTitle="Inscri ro7ek — Felflix";$activePage='signup';require_once '_header.php';
?>
<div class="wrap" style="padding-top:100px;padding-bottom:60px;max-width:480px">
  <div class="form-card">
    <div style="text-align:center;font-size:3rem;margin-bottom:10px">🌶</div>
    <div class="form-title">Nheb Felflix!</div>
    <div class="form-sub">Awel site tounssi lil aflam w el mosalsalat 🇺🇳</div>
    <?php if($error):?><div class="err-msg" style="display:block"><?=htmlspecialchars($error)?></div><?php endif;?>
    <form method="POST">
      <div style="margin-bottom:16px;text-align:center">
        <p style="color:var(--muted);font-size:.8rem;margin-bottom:8px">Akhtar avatar mta3ek</p>
        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
          <?php foreach(['🌶','🔥','🍿','🎬','⭐','🎭','🦁','🐉'] as $av):?>
          <label style="cursor:pointer">
            <input type="radio" name="avatar" value="<?=$av?>" <?=$av==='🌶'?'checked':''?> style="display:none"/>
            <span style="display:inline-block;font-size:1.6rem;padding:8px;border-radius:10px;border:2px solid var(--border);transition:all .2s;cursor:pointer" onclick="document.querySelectorAll('.av-s').forEach(x=>x.style.borderColor='var(--border)');this.style.borderColor='var(--red)'" class="av-s"><?=$av?></span>
          </label>
          <?php endforeach;?>
        </div>
      </div>
      <input class="finput" name="nom"      type="text"     placeholder="Isemk walla pseudo..." required value="<?=htmlspecialchars($_POST['nom']??'')?>"/>
      <input class="finput" name="email"    type="email"    placeholder="Email mta3ek..."       required value="<?=htmlspecialchars($_POST['email']??'')?>"/>
      <input class="finput" name="password" type="password" placeholder="Kalmet es-sir (4+ 7rof)..." required/>
      <input class="finput" name="bio"      type="text"     placeholder="Chnou 7kaya mta3ek... (ikhtiyeri)" value="<?=htmlspecialchars($_POST['bio']??'')?>"/>
      <details style="margin-bottom:12px">
        <summary style="color:var(--dim);font-size:.78rem;cursor:pointer;padding:6px 0">⚙️ Code administrateur (ikhtiyeri)</summary>
        <input class="finput" name="admin_code" type="password" placeholder="Code admin..." style="margin-top:8px"/>
        <p style="color:var(--dim);font-size:.72rem;margin-top:4px">Khali feragh ki ma kontich admin</p>
      </details>
      <button type="submit" class="btn-hero" style="width:100%;padding:13px;font-size:.95rem;border-radius:12px">Inscri ro7ek 🔥</button>
    </form>
    <div class="switch-link" style="margin-top:16px">3andek compte? <a href="login.php">D5ol</a></div>
  </div>
</div>
<?php require_once '_footer.php';?>
