<?php
namespace App\Core;
class Controller {
  protected array $config;
  public function __construct(){ $this->config = require __DIR__.'/../../config/config.php'; }
  protected function view(string $tpl, array $data=[]): void { extract($data); include __DIR__.'/../Views/layout.php'; }
  protected function redirect(string $path): void { $base=rtrim(dirname($_SERVER['SCRIPT_NAME']),'/'); header('Location: '.($base?:'').$path); exit; }
}
