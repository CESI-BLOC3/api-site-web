<?php namespace App\Core;
class CSRF { public static function token(): string { if(empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
  public static function check(?string $v): bool { return !empty($v) && hash_equals($_SESSION['_csrf']??'', $v); }
  public static function input(): string { return '<input type="hidden" name="_csrf" value="'.htmlspecialchars(self::token(),ENT_QUOTES).'">'; } }