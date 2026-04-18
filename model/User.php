<?php
class User {
    public $id,$nom,$email,$password,$role,$avatar,$bio,$created_at;
    public function __construct($nom,$email,$password,$role='user',$avatar='🌶',$bio=''){
        $this->nom=$nom;$this->email=$email;$this->password=$password;
        $this->role=$role;$this->avatar=$avatar;$this->bio=$bio;
    }
}
