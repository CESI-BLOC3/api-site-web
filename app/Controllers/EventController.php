<?php namespace App\Controllers; use App\Core\Controller; use App\Core\CSRF; use App\Core\Helpers; use App\Models\Event;
class EventController extends Controller {
  private function requireAuth(){ if(!\App\Core\Auth::check()) $this->redirect('/login'); }

  private function logUpload(string $msg): void {
    $dir = __DIR__.'/../../storage/logs';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $line = '['.date('Y-m-d H:i:s').'] '.$msg.'; limits up='.ini_get('upload_max_filesize').' post='.ini_get('post_max_size')."\n";
    @file_put_contents($dir.'/upload.log', $line, FILE_APPEND);
  }
  private function saveUpload(string $field): ?string {
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) return null;
    $err = $_FILES[$field]['error'] ?? UPLOAD_ERR_OK;
    if ($err !== UPLOAD_ERR_OK) { $this->logUpload("single error code=$err field=$field"); return null; }
    $tmp = $_FILES[$field]['tmp_name'];
    if (!$tmp || !file_exists($tmp)) { $this->logUpload("single tmp missing field=$field"); return null; }
    $mime = @mime_content_type($tmp) ?: ($_FILES[$field]['type'] ?? '');
    if (!in_array($mime, ['image/jpeg','image/png'])) { $this->logUpload("single mime rejected: $mime"); return null; }
    $ext = ($mime==='image/png' || str_ends_with(strtolower($_FILES[$field]['name']), '.png')) ? 'png' : 'jpg';
    $pubDir = __DIR__.'/../../public/uploads';
    if (!is_dir($pubDir) && !@mkdir($pubDir, 0777, true)) { $this->logUpload("mkdir failed: $pubDir"); return null; }
    if (!is_writable($pubDir)) { @chmod($pubDir, 0777); }
    $fname = '/uploads/'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $dest = __DIR__.'/../../public'.$fname;
    $ok = @move_uploaded_file($tmp, $dest);
    if (!$ok) { $ok = @rename($tmp, $dest); }
    if (!$ok) { $ok = @copy($tmp, $dest); }
    if (!$ok) { $this->logUpload("write failed dest=$dest perms=".(is_writable($pubDir)?'writable':'not-writable')); return null; }
    $this->logUpload("single saved -> $dest");
    return $fname;
  }
  private function saveMultiUploads(string $field): array {
    $saved = [];
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) return $saved;
    $names = $_FILES[$field]['name'];
    $tmps  = $_FILES[$field]['tmp_name'];
    $types = $_FILES[$field]['type'];
    $errs  = $_FILES[$field]['error'];
    $count = is_array($names) ? count($names) : 0;
    $pubDir = __DIR__.'/../../public/uploads';
    if (!is_dir($pubDir)) { @mkdir($pubDir, 0777, true); }
    if (!is_writable($pubDir)) { @chmod($pubDir, 0777); }
    for ($i=0; $i<$count; $i++) {
      if (empty($names[$i])) continue;
      $err = is_array($errs) ? ($errs[$i] ?? UPLOAD_ERR_OK) : UPLOAD_ERR_OK;
      if ($err !== UPLOAD_ERR_OK) { $this->logUpload("multi[$i] error code=$err name=".$names[$i]); continue; }
      $tmp = is_array($tmps) ? $tmps[$i] : null;
      if (!$tmp || !file_exists($tmp)) { $this->logUpload("multi[$i] tmp missing"); continue; }
      $mime = @mime_content_type($tmp) ?: (is_array($types)?($types[$i] ?? ''):'');
      if (!in_array($mime, ['image/jpeg','image/png'])) { $this->logUpload("multi[$i] mime rejected: $mime"); continue; }
      $ext = ($mime==='image/png' or str_ends_with(strtolower($names[$i]), '.png')) ? 'png' : 'jpg';
      $fname = '/uploads/'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
      $dest = __DIR__.'/../../public'.$fname;
      $ok = @move_uploaded_file($tmp, $dest);
      if (!$ok) { $ok = @rename($tmp, $dest); }
      if (!$ok) { $ok = @copy($tmp, $dest); }
      if ($ok) { $saved[] = $fname; $this->logUpload("multi[$i] saved -> $dest"); }
      else { $this->logUpload("multi[$i] write failed dest=$dest"); }
    }
    return $saved;
  }

  public function index(){ $this->requireAuth(); $events=Event::all(); $tpl='admin/events/index'; include __DIR__.'/../Views/layout.php'; }
  public function create(){ $this->requireAuth(); $tpl='admin/events/create'; include __DIR__.'/../Views/layout.php'; }
  public function store(){
    $this->requireAuth(); if(!CSRF::check($_POST['_csrf']??null)) die('Invalid CSRF');
    $name=trim($_POST['name']??''); $description=trim($_POST['description']??'');
    $event_date=Helpers::sanitizeDate($_POST['event_date']??''); $price=Helpers::sanitizeInt($_POST['price']??0);
    $lat=Helpers::sanitizeFloat($_POST['latitude']??0); $lng=Helpers::sanitizeFloat($_POST['longitude']??0);
    $contact_name=trim($_POST['contact_name']??''); $contact_email=Helpers::sanitizeEmail($_POST['contact_email']??'') ?? '';
    $cover = $this->saveUpload('photo'); $more = $this->saveMultiUploads('photos'); $photo_path=$cover;
    $id=Event::create([ 'name'=>$name,'description'=>$description,'event_date'=>$event_date,'price'=>$price,'latitude'=>$lat,'longitude'=>$lng,'contact_name'=>$contact_name,'contact_email'=>$contact_email,'photo_path'=>$photo_path,'confirmation_token'=>null ]);
    foreach($more as $p){ \App\Models\EventPhoto::add($id, $p); }
    $_SESSION['flash']="Événement créé (#$id)"; $this->redirect('/admin/events');
  }
  public function show($id){ $this->requireAuth(); $event=Event::find((int)$id); $tpl='admin/events/show'; include __DIR__.'/../Views/layout.php'; }
  public function edit($id){ $this->requireAuth(); $event=Event::find((int)$id); $tpl='admin/events/edit'; include __DIR__.'/../Views/layout.php'; }
  public function update($id){
    $this->requireAuth(); if(!CSRF::check($_POST['_csrf']??null)) die('Invalid CSRF');
    $data=[]; foreach(['name','description','contact_name'] as $f){ if(isset($_POST[$f])) $data[$f]=trim($_POST[$f]); }
    if(isset($_POST['event_date'])) $data['event_date']=Helpers::sanitizeDate($_POST['event_date']);
    if(isset($_POST['price'])) $data['price']=Helpers::sanitizeInt($_POST['price']);
    if(isset($_POST['latitude'])) $data['latitude']=Helpers::sanitizeFloat($_POST['latitude']);
    if(isset($_POST['longitude'])) $data['longitude']=Helpers::sanitizeFloat($_POST['longitude']);
    if(isset($_POST['contact_email'])) $data['contact_email']=Helpers::sanitizeEmail($_POST['contact_email']) ?? '';
    $cover = $this->saveUpload('photo'); if($cover){ $data['photo_path']=$cover; }
    $more = $this->saveMultiUploads('photos'); foreach($more as $p){ \App\Models\EventPhoto::add((int)$id, $p); }
    Event::update((int)$id,$data); $_SESSION['flash']='Mise à jour OK'; $this->redirect('/admin/events/'.$id);
  }
  public function destroy($id){ $this->requireAuth(); if(!CSRF::check($_POST['_csrf']??null)) die('Invalid CSRF'); Event::delete((int)$id); $_SESSION['flash']='Supprimé'; $this->redirect('/admin/events'); }
}