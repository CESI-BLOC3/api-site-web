<?php use App\Core\View; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); if(!$event): ?>
<p class="text-red-600">Événement introuvable.</p>
<?php else: ?>
<div class="flex flex-col md:flex-row gap-6">
  <div class="flex-1">
    <h1 class="text-3xl font-bold"><?php echo View::e($event['name']); ?></h1>
    <p class="text-slate-500 mb-2"><?php echo View::e($event['event_date']); ?> · <?php echo (int)$event['price']; ?> €</p>
    <p class="mb-3"><?php echo nl2br(View::e($event['description'])); ?></p>
    <div id="map" class="h-64 rounded border border-slate-200 dark:border-slate-700"></div>
  </div>
  <div class="w-full md:w-64">
    <?php
$photos = [];
if (!empty($event['photo_path'])) $photos[] = $event['photo_path'];
$extra = \App\Models\EventPhoto::listByEvent((int)$event['id']);
foreach ($extra as $ph) { $photos[] = $ph['path']; }
?>
<?php if ($photos): ?>
  <div class="grid grid-cols-2 gap-2">
    <?php foreach ($photos as $p): ?>
      <img src="<?php echo $base . View::e($p); ?>" class="rounded w-full object-cover max-h-56"/>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
    <a class="mt-3 block text-center px-4 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900" href="<?php echo $base; ?>/events/<?php echo (int)$event['id']; ?>/flyer.pdf">Télécharger le flyer (PDF)</a>
  </div>
</div>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map=L.map('map').setView([<?php echo $event['latitude']; ?>,<?php echo $event['longitude']; ?>],12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OSM'}).addTo(map);
L.marker([<?php echo $event['latitude']; ?>,<?php echo $event['longitude']; ?>]).addTo(map);
</script>
<?php endif; ?>
