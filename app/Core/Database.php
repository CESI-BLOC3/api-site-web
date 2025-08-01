<?php
namespace App\Core; use PDO, PDOException;
class Database {
  private static ?PDO $pdo=null;
  public static function init(array $c):void{
    if(self::$pdo) return;
    $dsn="mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4";
    try{ self::$pdo=new PDO($dsn,$c['user'],$c['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
    catch(PDOException $e){ http_response_code(500); die('DB error: '.htmlspecialchars($e->getMessage())); }
  }
  public static function pdo():PDO{ return self::$pdo; }
}
