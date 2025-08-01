<?php namespace App\Models; use App\Core\Database;
class User {
  public static function findByEmail(string $email): ?array { $q=Database::pdo()->prepare("SELECT * FROM users WHERE email=?"); $q->execute([$email]); $r=$q->fetch(); return $r?:null; }
  public static function find(int $id): ?array { $q=Database::pdo()->prepare("SELECT * FROM users WHERE id=?"); $q->execute([$id]); $r=$q->fetch(); return $r?:null; }
  public static function create(string $name,string $email,string $passwordHash,int $isAdmin=0): int { $q=Database::pdo()->prepare("INSERT INTO users (name,email,password_hash,is_admin) VALUES (?,?,?,?)"); $q->execute([$name,$email,$passwordHash,$isAdmin]); return (int)Database::pdo()->lastInsertId(); }
  public static function updateEmail(int $id, string $email): bool { $q=Database::pdo()->prepare("UPDATE users SET email=? WHERE id=?"); return $q->execute([$email,$id]); }
  public static function updatePassword(int $id, string $hash): bool { $q=Database::pdo()->prepare("UPDATE users SET password_hash=? WHERE id=?"); return $q->execute([$hash,$id]); }
}