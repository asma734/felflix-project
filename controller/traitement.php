<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../model/User.php';
function addUser($cnx,$data){
    $c=$cnx->prepare("SELECT id FROM users WHERE email=?");$c->execute([$data['email']]);
    if($c->fetch()) return['success'=>false,'message'=>'Cet email est déjà utilisé!'];
    $r=$cnx->prepare("INSERT INTO users(nom,email,password,role,avatar,bio) VALUES(?,?,?,?,?,?)");
    $ok=$r->execute([$data['nom'],$data['email'],password_hash($data['password'],PASSWORD_BCRYPT),$data['role']??'user',$data['avatar']??'🌶',$data['bio']??'']);
    return $ok?['success'=>true,'id'=>$cnx->lastInsertId()]:['success'=>false,'message'=>'Erreur'];
}
function loginUser($cnx,$email,$password){
    $r=$cnx->prepare("SELECT * FROM users WHERE email=?");$r->execute([$email]);$row=$r->fetch();
    if(!$row) return['success'=>false,'message'=>'Email mawjoudch! ❌'];
    if(!password_verify($password,$row['password'])) return['success'=>false,'message'=>'Kalmet es-sir ghaltha! 🔐'];
    return['success'=>true,'user'=>$row];
}
function getAllUsers($cnx){
    $rows=$cnx->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();$users=[];
    foreach($rows as $row){$u=new User($row['nom'],$row['email'],$row['password'],$row['role'],$row['avatar'],$row['bio']);$u->id=$row['id'];$u->created_at=$row['created_at']??'';$users[]=$u;}
    return $users;
}
function searchUsers($cnx,$q){
    $r=$cnx->prepare("SELECT * FROM users WHERE nom LIKE ? OR email LIKE ? ORDER BY id DESC");$r->execute(["%$q%","%$q%"]);$users=[];
    foreach($r->fetchAll() as $row){$u=new User($row['nom'],$row['email'],$row['password'],$row['role'],$row['avatar'],$row['bio']);$u->id=$row['id'];$u->created_at=$row['created_at']??'';$users[]=$u;}
    return $users;
}
function getUserById($cnx,$id){$r=$cnx->prepare("SELECT * FROM users WHERE id=?");$r->execute([$id]);return $r->fetch();}
function updateUser($cnx,$id,$nom,$email,$avatar=null,$bio=null){
    if($avatar!==null&&$bio!==null) return $cnx->prepare("UPDATE users SET nom=?,email=?,avatar=?,bio=? WHERE id=?")->execute([$nom,$email,$avatar,$bio,$id]);
    return $cnx->prepare("UPDATE users SET nom=?,email=? WHERE id=?")->execute([$nom,$email,$id]);
}
function deleteUser($cnx,$id){return $cnx->prepare("DELETE FROM users WHERE id=?")->execute([$id]);}
function countUsers($cnx){return $cnx->query("SELECT COUNT(*) FROM users")->fetchColumn();}
function countMovies($cnx){try{return $cnx->query("SELECT COUNT(*) FROM movies")->fetchColumn();}catch(Exception $e){return 0;}}
function countPosts($cnx){try{return $cnx->query("SELECT COUNT(*) FROM posts WHERE is_deleted=0")->fetchColumn();}catch(Exception $e){return 0;}}
function getTunisianMovies($cnx){try{return $cnx->query("SELECT * FROM movies WHERE country='Tunisia'")->fetchAll();}catch(Exception $e){return[];}}
