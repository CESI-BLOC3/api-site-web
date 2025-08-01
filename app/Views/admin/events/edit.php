<?php use App\Core\View; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<h1 class="text-2xl font-semibold mb-4">Éditer #<?php echo (int)$event['id']; ?></h1>
<form method="post" action="<?php echo $base; ?>/admin/events/<?php echo (int)$event['id']; ?>/update" enctype="multipart/form-data" class="space-y-3">
  <?php echo App\Core\CSRF::input(); ?>
  <div class="grid md:grid-cols-2 gap-3">
    <label>Nom<input name="name" value="<?php echo View::e($event['name']); ?>" class="w-full rounded border p-2"/></label>
    <label>Date<input name="event_date" type="date" value="<?php echo View::e($event['event_date']); ?>" class="w-full rounded border p-2"/></label>
    <label>Prix (€)<input name="price" type="number" value="<?php echo (int)$event['price']; ?>" class="w-full rounded border p-2"/></label>
    <label>Contact (nom)<input name="contact_name" value="<?php echo View::e($event['contact_name']); ?>" class="w-full rounded border p-2"/></label>
    <label>Contact (email)<input name="contact_email" type="email" value="<?php echo View::e($event['contact_email']); ?>" class="w-full rounded border p-2"/></label>
  </div>
  <label>Description<textarea name="description" rows="4" class="w-full rounded border p-2"><?php echo View::e($event['description']); ?></textarea></label>
  <div class="grid md:grid-cols-2 gap-3">
    <label>Latitude<input id="lat" name="latitude" type="number" step="any" value="<?php echo View::e((string)$event['latitude']); ?>" class="w-full rounded border p-2"/></label>
    <label>Longitude<input id="lng" name="longitude" type="number" step="any" value="<?php echo View::e((string)$event['longitude']); ?>" class="w-full rounded border p-2"/></label>
  </div>
  <div id="map" class="h-64 rounded border"></div>
  <?php if (!empty($event['photo_path'])): ?><img src="<?php echo $base . View::e($event['photo_path']); ?>" class="rounded w-40"/><?php endif; ?>
  <label>Photo (couverture)<input name="photo" type="file" accept="image/png,image/jpeg" class="w-full"/></label>
  <label>Ajouter des photos<input id="photos" name="photos[]" type="file" accept="image/png,image/jpeg" class="w-full" multiple/></label>
  <div id="preview" class="grid grid-cols-3 gap-2 mt-2"></div>
  <button class="px-4 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900">Mettre à jour</button>
</form>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const lat0=parseFloat(document.getElementById('lat').value||'46.8'); const lng0=parseFloat(document.getElementById('lng').value||'2.2');
const map=L.map('map').setView([lat0,lng0],12); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OSM'}).addTo(map); let marker=L.marker([lat0,lng0]).addTo(map);
map.on('click', e=>{ const {lat,lng}=e.latlng; document.getElementById('lat').value=lat.toFixed(6); document.getElementById('lng').value=lng.toFixed(6); marker.setLatLng([lat,lng]); });
</script>

<script>
document.getElementById('photos').addEventListener('change', (e)=>{
  const wrap=document.getElementById('preview'); wrap.innerHTML='';
  [...e.target.files].forEach(f=>{ const rd=new FileReader(); rd.onload=()=>{ const img=new Image(); img.src=rd.result; img.className='w-full h-24 object-cover rounded border'; wrap.appendChild(img); }; rd.readAsDataURL(f); });
});
</script>
