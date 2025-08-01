<?php namespace App\Models; use App\Core\Database;
class Event {
  public static function all(): array { return Database::pdo()->query("SELECT * FROM events ORDER BY id DESC")->fetchAll(); }
  public static function find(int $id): ?array { $q=Database::pdo()->prepare("SELECT * FROM events WHERE id=?"); $q->execute([$id]); $r=$q->fetch(); return $r?:null; }
  public static function create(array $d): int {
    $q=Database::pdo()->prepare("INSERT INTO events (name,description,event_date,price,latitude,longitude,contact_name,contact_email,photo_path,confirmation_token,is_confirmed,created_at,updated_at) VALUES (:name,:description,:event_date,:price,:latitude,:longitude,:contact_name,:contact_email,:photo_path,:confirmation_token,0,NOW(),NOW())");
    $q->execute($d); return (int) Database::pdo()->lastInsertId();
  }
  public static function update(int $id,array $d): bool {
    $sets=[]; foreach($d as $k=>$v){ $sets[]="$k=:$k"; }
    $sql="UPDATE events SET ".implode(',',$sets).", updated_at=NOW() WHERE id=:id"; $d['id']=$id; $q=Database::pdo()->prepare($sql); return $q->execute($d);
  }
  public static function delete(int $id): bool { $q=Database::pdo()->prepare("DELETE FROM events WHERE id=?"); return $q->execute([$id]); }
}