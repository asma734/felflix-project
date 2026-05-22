<?php
session_start();require_once __DIR__.'/../controller/traitement.php';
$base=dirname(dirname($_SERVER['SCRIPT_NAME']));
if(!isset($_SESSION['user']))
    {header('Location: '.$base.'/view/login.php');
    return;}
$isAdmin=$_SESSION['user']['role']==='admin';
$eid=(int)($_POST['id']??0);
if(!$isAdmin&&$eid!==(int)$_SESSION['user']['id']){
    header('Location: '.$base.'/view/index.php');
    return;} // ******
if(isset($_POST['nom'],$_POST['email'])){
    updateUser($cnx,$eid,$_POST['nom'],$_POST['email'],$_POST['avatar']??null,$_POST['bio']??null);
    if(isset($_POST['role'])&&$isAdmin) $cnx->prepare("UPDATE users SET role=? WHERE id=?")->execute([$_POST['role'],$eid]);
    if($eid===(int)$_SESSION['user']['id']) $_SESSION['user']=getUserById($cnx,$eid);
}
header('Location: '.$base.($isAdmin?'/view/admin.php?tab=users':'/view/profile.php'));return;
