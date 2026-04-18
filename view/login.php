<?php
session_start();if(isset($_SESSION['user'])){
    // Redirect admin to admin dashboard, regular users to home
    if($_SESSION['user']['role']==='admin'){header('Location: admin.php');exit;}
    header('Location: index.php');exit;
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once '../controller/traitement.php';
    $r=loginUser($cnx,trim($_POST['email']??''),$_POST['password']??'');
    if($r['success']){
        $_SESSION['user']=$r['user'];
        // Admin yimchi l dashboard mta3o
        if($r['user']['role']==='admin'){header('Location: admin.php');exit;}
        header('Location: index.php');exit;
    }
    else{$error=$r['message'];}
}
$pageTitle='Login — Felflix';$activePage='login';require_once '_header.php';
?>
<div class="wrap" style="padding-top:100px;padding-bottom:60px;max-width:420px">
  <div class="form-card">
    <div style="text-align:center;font-size:3rem;margin-bottom:10px">🌶</div>
    <div class="form-title">Marhba bik!</div>
    <div class="form-sub">D5ol l Felflix 🌶</div>
    <?php if($error):?><div class="err-msg" style="display:block"><?=htmlspecialchars($error)?></div><?php endif;?>
    <form method="POST">
      <input class="finput" name="email"    type="email"    placeholder="Email mta3ek..."      required value="<?=htmlspecialchars($_POST['email']??'')?>"/>
      <input class="finput" name="password" type="password" placeholder="Kalmet es-sir..."       required/>
      <button type="submit" class="btn-hero" style="width:100%;padding:13px;font-size:.95rem;border-radius:12px">D5ol 🌶</button>
    </form>
    <div class="switch-link" style="margin-top:16px">Ma3ndekch compte? <a href="signup.php">Inscri ro7ek</a></div>
    <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);font-size:.75rem;color:var(--dim);text-align:center;line-height:1.9">Comptes test 🔑<br/><span>admin@felflix.tn / admin123</span><br/><span>saied@gmail.com / 1234</span></div>
  </div>
</div>
<?php require_once '_footer.php';?>
