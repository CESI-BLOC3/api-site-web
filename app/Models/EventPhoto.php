<?php namespace App\Models; use App\Core\Database;
class EventPhoto {
  public static function listByEvent(int $eventId): array {
    $q=Database::pdo()->prepare("SELECT * FROM event_photos WHERE event_id=? ORDER BY id ASC");
    $q->execute([$eventId]); return $q->fetchAll();
  }
  public static function add(int $eventId, string $path): int {
    $q=Database::pdo()->prepare("INSERT INTO event_photos (event_id, path, created_at) VALUES (?,?,NOW())");
    $q->execute([$eventId,$path]); return (int) Database::pdo()->lastInsertId();
  }
}