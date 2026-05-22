<?php
session_start();if(isset($_SESSION['user'])){
    if($_SESSION['user']['role']==='admin'){header('Location: admin.php');exit;}
    header('Location: index.php');exit;
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once '../controller/traitement.php';
    $r=loginUser($cnx,trim($_POST['email']??''),$_POST['password']??'');
    if($r['success']){
        $_SESSION['user']=$r['user'];
        if($r['user']['role']==='admin'){header('Location: admin.php');exit;}
        header('Location: index.php');exit;
    }
    else{$error=$r['message'];}
}
$pageTitle='Login — Felflix';$activePage='login';require_once '_header.php';
?>
<main style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:100px 16px 60px;position:relative;">
  <!-- Radial background glow -->
  <div style="position:absolute;inset:0;z-index:0;background:radial-gradient(ellipse 60% 50% at 50% 30%,rgba(230,57,70,.35),transparent 70%);pointer-events:none"></div>
  <!-- Zellige overlay -->
  <div class="zellige-bg" style="position:absolute;inset:0;z-index:0;opacity:.3;pointer-events:none"></div>

  <div style="position:relative;z-index:1;width:100%;max-width:420px">
    <div class="form-card animate-scale-in" style="text-align:center;border-radius:24px;padding:40px 36px">
      <div style="font-size:3.5rem;margin-bottom:12px;display:inline-block;animation:floatSlow 6s ease-in-out infinite;filter:drop-shadow(0 0 24px rgba(244,114,30,.8))">🌶</div>
      <h1 class="form-title" style="font-size:2rem;margin-bottom:4px">Marhba bik!</h1>
      <p class="form-sub">D5ol l Felflix 🌶</p>
      <?php if($error):?><div class="err-msg" style="display:block"><?=htmlspecialchars($error)?></div><?php endif;?>
      <form method="POST" style="text-align:left">
        <input class="finput" name="email"    type="email"    placeholder="Email mta3ek..."   required value="<?=htmlspecialchars($_POST['email']??'')?>"/>
        <input class="finput" name="password" type="password" placeholder="Kalmet es-sir..."  required/>
        <button type="submit" class="btn-hero" style="width:100%;padding:14px;font-size:.95rem;border-radius:12px;justify-content:center;margin-top:4px">D5ol 🌶</button>
      </form>
      <div class="switch-link" style="margin-top:18px">Ma3ndekch compte? <a href="signup.php">Inscri ro7ek</a></div>
      
    </div>
  </div>
</main>
<?php require_once '_footer.php';?>
