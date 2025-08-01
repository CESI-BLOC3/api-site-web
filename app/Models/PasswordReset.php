<?php namespace App\Models; use App\Core\Database;
class PasswordReset {
  public static function create(int $userId, string $code, string $expiresAt): bool {
    $q=Database::pdo()->prepare("INSERT INTO password_resets (user_id, code, expires_at, used) VALUES (?,?,?,0)");
    return $q->execute([$userId,$code,$expiresAt]);
  }
  public static function consume(string $code): ?int {
    $q=Database::pdo()->prepare("SELECT user_id, expires_at, used FROM password_resets WHERE code=? ORDER BY id DESC LIMIT 1");
    $q->execute([$code]); $r=$q->fetch(); if(!$r) return null;
    if($r['used'] || strtotime($r['expires_at'])<time()) return null;
    $u=Database::pdo()->prepare("UPDATE password_resets SET used=1 WHERE code=?"); $u->execute([$code]);
    return (int)$r['user_id'];
  }
}