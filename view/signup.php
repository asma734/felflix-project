<?php
session_start();

if(isset($_SESSION['user'])){
    if($_SESSION['user']['role']==='admin'){
      header('Location: admin.php'); // si role admin irect
      exit;
}
    header('Location: index.php');exit;   // snn ole user ya3ni yod5ol direct  
}


$error=''; // ken famma erreur y5arajha f wesst variable $error
if($_SERVER['REQUEST_METHOD']==='POST'){ //    method="POST" : les données sont envoyées de façon cachée elles n'apparaissent PAS dans l'URL xemple URL : sign_in.php  (rien de visible) utilisé pour les données sensibles : email, mot de passe
    require_once '../controller/traitement.php'; // importer le traitement de controller
    $nom=trim($_POST['nom']??'');$email=trim($_POST['email']??'');$pwd=$_POST['password']??'';$avatar=$_POST['avatar']??'🌶';$bio=trim($_POST['bio']??'');$code=trim($_POST['admin_code']??'');// importer les donner du formulaire POST
    if(!$nom||!$email||!$pwd){$error='3amer les champs lkol! 🌶';}
    elseif(strlen($pwd)<4){$error='Kalmet es-sir 9serha (4 7rof fi l2a9al)';}
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){$error='Email m3awej!';}
    else{
        $role=$code==='FELLIX2025'?'admin':'user';
        $r=addUser($cnx,['nom'=>$nom,'email'=>$email,'password'=>$pwd,'role'=>$role,'avatar'=>$avatar,'bio'=>$bio]); // yajouti  fil  utulisateur jdid fil base de donnee
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
<main style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:100px 16px 60px;position:relative;">
  <div style="position:absolute;inset:0;z-index:0;background:radial-gradient(ellipse 60% 50% at 50% 30%,rgba(230,57,70,.3),transparent 70%);pointer-events:none"></div>
  <div class="zellige-bg" style="position:absolute;inset:0;z-index:0;opacity:.25;pointer-events:none"></div>

  <div style="position:relative;z-index:1;width:100%;max-width:480px">
    <div class="form-card animate-scale-in" style="border-radius:24px;padding:36px">
      <div style="text-align:center;font-size:3.2rem;margin-bottom:12px;display:inline-block;animation:floatSlow 6s ease-in-out infinite;filter:drop-shadow(0 0 20px rgba(244,114,30,.8))">🌶</div>
      <h1 class="form-title" style="font-size:1.9rem;margin-bottom:4px">Mar7be bik fi Felflix!</h1>
      <p class="form-sub">Awel site tounssi lil aflam w el mosalsalat 🇹🇳</p>
      <?php if($error):?><div class="err-msg" style="display:block"><?=htmlspecialchars($error)?></div><?php endif;?>
      <form method="POST">
        <!-- Avatar picker -->
        <div style="margin-bottom:18px;text-align:center">
          <p style="color:var(--muted);font-size:.8rem;margin-bottom:10px">Akhtar avatar mta3ek</p>
          <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
            <?php foreach(['🌶','🔥','🍿','🎬','⭐','🎭','🦁','🐉'] as $av):?>
            <label style="cursor:pointer">
              <input type="radio" name="avatar" value="<?=$av?>" <?=$av==='🌶'?'checked':''?> style="display:none"/>
              <span style="display:inline-block;font-size:1.6rem;padding:8px;border-radius:10px;border:2px solid var(--border);transition:all .2s;cursor:pointer" onclick="document.querySelectorAll('.av-s').forEach(x=>{x.style.borderColor='var(--border)';x.style.background='transparent'});this.style.borderColor='var(--red)';this.style.background='rgba(230,57,70,.12)'" class="av-s"><?=$av?></span>
            </label>
            <?php endforeach;?>
          </div>
        </div>
        <input class="finput" name="nom"      type="text"     placeholder="Isemk walla pseudo..." required value="<?=htmlspecialchars($_POST['nom']??'')?>"/>
        <input class="finput" name="email"    type="email"    placeholder="Email mta3ek..."       required value="<?=htmlspecialchars($_POST['email']??'')?>"/>
        <input class="finput" name="password" type="password" placeholder="Kalmet es-sir (4+ 7rof)..." required/>
        <input class="finput" name="bio"      type="text"     placeholder="Chnou 7kaya mta3ek... (ikhtiyeri)" value="<?=htmlspecialchars($_POST['bio']??'')?>"/>
    
        <button type="submit" class="btn-hero" style="width:100%;padding:14px;font-size:.95rem;border-radius:12px;justify-content:center">Inscri ro7ek 🔥</button>
      </form>
      <div class="switch-link" style="margin-top:18px">3andek compte? <a href="login.php">D5ol</a></div>
    </div>
  </div>
</main>
<?php require_once '_footer.php';?>
