<?php namespace App\Controllers; use App\Core\Controller; use App\Core\CSRF; use App\Core\Mailer; use App\Core\Helpers; use App\Models\User; use App\Models\PasswordReset;
class UserController extends Controller {
  private function requireAdmin(){ if(!\App\Core\Auth::admin()) $this->redirect('/login'); }
  public function create(){ $this->requireAdmin(); $tpl='admin/users/create'; include __DIR__.'/../Views/layout.php'; }
  public function store(){
    $this->requireAdmin(); if(!CSRF::check($_POST['_csrf']??null)) die('Invalid CSRF');
    $name=trim($_POST['name']??''); $email=Helpers::sanitizeEmail($_POST['email']??''); $isAdmin=(int)($_POST['is_admin']??0);
    if(!$name || !$email){ $_SESSION['flash']='Nom/Email requis'; $this->redirect('/admin/users/create'); }
    $tmpPass=bin2hex(random_bytes(4));
    $id=User::create($name,$email,password_hash($tmpPass,PASSWORD_BCRYPT),$isAdmin);
    $code=bin2hex(random_bytes(16)); \App\Models\PasswordReset::create($id,$code,date('Y-m-d H:i:s', time()+3600));
    $link=rtrim($this->config['app_url'],'/').'/set-password?code='.$code;
    $html='<h2>Bienvenue sur '.$this->config['app_name'].'</h2><p>Compte créé pour '.$name.'.</p><p>Mot de passe temporaire: <b>'.$tmpPass.'</b></p><p>Ou définis ton mot de passe ici: <a href="'.$link.'">'.$link.'</a></p>';
    Mailer::send($email,'Bienvenue sur '.$this->config['app_name'],$html);
    $_SESSION['flash']='Utilisateur créé et mail envoyé.'; $this->redirect('/admin/events');
  }
}