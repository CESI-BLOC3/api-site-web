<?php namespace App\Core;
class Mailer { public static function send(string $to,string $subject,string $html,array $headers=[]):bool{
  $config=require __DIR__.'/../../config/config.php';
  $h=[ 'MIME-Version: 1.0','Content-type: text/html; charset=utf-8','From: '.$config['mail']['from_name'].' <'.$config['mail']['from_email'].'>' ];
  $headers = implode("\r\n", array_merge($h,$headers));
  $ok=@mail($to,$subject,$html,$headers);
  if(!$ok){ $dir=__DIR__.'/../../storage/mail_logs'; if(!is_dir($dir)) @mkdir($dir,0777,true); $fname=$dir.'/'.date('Ymd_His').'_'.preg_replace('/[^a-z0-9]+/i','_', $to).'.eml'; file_put_contents($fname, "To: $to\nSubject: $subject\n$headers\n\n$html"); }
  return true; } }