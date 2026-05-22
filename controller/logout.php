<?php
session_start();session_unset();session_destroy();
$base=dirname(dirname($_SERVER['SCRIPT_NAME']));
header('Location: '.$base.'/view/login.php');return;
