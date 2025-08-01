<?php namespace App\Controllers; use App\Core\Controller; use App\Models\Event;
class HomeController extends Controller {
  public function index(){ $events=Event::all(); $tpl='home'; include __DIR__.'/../Views/layout.php'; }
  public function detail($id){ $event=Event::find((int)$id); $tpl='public/event_detail'; include __DIR__.'/../Views/layout.php'; }
  public function flyer($id){
    $e=Event::find((int)$id); if(!$e){ http_response_code(404); exit; }
    $images = [];
    if (!empty($e['photo_path'])) {
      $p = __DIR__.'/../../public'.$e['photo_path']; if (is_file($p)) $images[] = $p;
    }
    $extra = \App\Models\EventPhoto::listByEvent((int)$e['id']);
    foreach ($extra as $ph) { $p = __DIR__.'/../../public'.$ph['path']; if (is_file($p)) $images[] = $p; }
    $title = $this->config['app_name'].' — '.($e['name'] ?? '');
    $desc  = $e['description'] ?? '';
    $price = (int)($e['price'] ?? 0);
    $pdf=\App\Core\PDFExporter::flyerRich($title, $desc, $price, $images);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="flyer_'.((int)$e['id']).'.pdf"');
    echo $pdf;
  }
}