<?php namespace App\Core; use App\Models\User;
class Auth { public static function attempt(string $email,string $password):bool{ $u=User::findByEmail($email); if($u && password_verify($password,$u['password_hash'])){ $_SESSION['uid']=$u['id']; $_SESSION['uname']=$u['name']; $_SESSION['uemail']=$u['email']; $_SESSION['is_admin']=(int)$u['is_admin']; return true; } return false; }
  public static function check():bool{ return !empty($_SESSION['uid']); }
  public static function admin():bool{ return !empty($_SESSION['is_admin']); }
  public static function logout():void{ $_SESSION=[]; session_destroy(); } }