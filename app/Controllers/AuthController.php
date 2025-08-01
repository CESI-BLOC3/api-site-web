<?php namespace App\Controllers; use App\Core\Controller; use App\Core\Auth; use App\Core\CSRF;
class AuthController extends Controller {
  public function showLogin(){ $tpl='auth/login'; include __DIR__.'/../Views/layout.php'; }
  public function login(){ if(!CSRF::check($_POST['_csrf']??null)) die('Invalid CSRF'); $email=trim($_POST['email']??''); $pass=$_POST['password']??''; if(Auth::attempt($email,$pass)) $this->redirect('/admin/events'); $_SESSION['flash']='Identifiants invalides'; $this->redirect('/login'); }
  public function logout(){ if(!CSRF::check($_POST['_csrf']??null)) die('Invalid CSRF'); Auth::logout(); $this->redirect('/'); }
}