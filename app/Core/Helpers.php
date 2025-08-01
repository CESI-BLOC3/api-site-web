<?php namespace App\Core;
class Helpers { public static function sanitizeEmail(string $email): ?string { $e=filter_var(trim($email), FILTER_VALIDATE_EMAIL); return $e?:null; }
  public static function sanitizeInt($v): int { return (int) filter_var($v, FILTER_VALIDATE_INT, ['options'=>['default'=>0]]); }
  public static function sanitizeFloat($v): float { return (float) filter_var($v, FILTER_VALIDATE_FLOAT, ['options'=>['default'=>0]]); }
  public static function sanitizeDate($v): ?string { $d=date_create($v); return $d? $d->format('Y-m-d'): null; }
  public static function json(): array { $raw=file_get_contents('php://input'); $j=json_decode($raw,true); return is_array($j)?$j:[]; }
}