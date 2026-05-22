<?php
session_start();require_once __DIR__.'/../controller/traitement.php';
$base=dirname(dirname($_SERVER['SCRIPT_NAME']));
if(!isset($_SESSION['user'])||$_SESSION['user']['role']!=='admin'){header('Location: '.$base.'/view/index.php');return;}
if(isset($_GET['id'])&&(int)$_GET['id']!==(int)$_SESSION['user']['id']) deleteUser($cnx,(int)$_GET['id']);
header('Location: '.$base.'/view/admin.php?tab=users');return;
