<?php $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<h1 class="text-2xl font-semibold mb-4">Créer un événement</h1>
<form method="post" action="<?php echo $base; ?>/admin/events/store" enctype="multipart/form-data" class="space-y-3">
  <?php echo App\Core\CSRF::input(); ?>
  <div class="grid md:grid-cols-2 gap-3">
    <label>Nom<input name="name" class="w-full rounded border p-2"/></label>
    <label>Date<input name="event_date" type="date" class="w-full rounded border p-2"/></label>
    <label>Prix (€)<input name="price" type="number" class="w-full rounded border p-2"/></label>
    <label>Contact (nom)<input name="contact_name" class="w-full rounded border p-2"/></label>
    <label>Contact (email)<input name="contact_email" type="email" class="w-full rounded border p-2"/></label>
  </div>
  <label>Description<textarea name="description" rows="4" class="w-full rounded border p-2"></textarea></label>
  <div class="grid md:grid-cols-2 gap-3">
    <label>Latitude<input id="lat" name="latitude" type="number" step="any" class="w-full rounded border p-2"/></label>
    <label>Longitude<input id="lng" name="longitude" type="number" step="any" class="w-full rounded border p-2"/></label>
  </div>
  <div id="map" class="h-64 rounded border"></div>
  <div class="grid md:grid-cols-2 gap-3">
    <label>Photo (couverture)<input name="photo" type="file" accept="image/png,image/jpeg" class="w-full"/></label>
    <label>Galerie (plusieurs images)<input id="photos" name="photos[]" type="file" accept="image/png,image/jpeg" class="w-full" multiple/></label>
  </div>
  <div id="preview" class="grid grid-cols-3 gap-2 mt-2"></div>
  <button class="px-4 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900">Enregistrer</button>
</form>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map=L.map('map').setView([46.8,2.2],5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OSM'}).addTo(map);
let marker=null; map.on('click', e=>{ const {lat,lng}=e.latlng; document.getElementById('lat').value=lat.toFixed(6); document.getElementById('lng').value=lng.toFixed(6); if(marker) marker.remove(); marker=L.marker([lat,lng]).addTo(map); });
document.getElementById('photos').addEventListener('change', (e)=>{
  const wrap=document.getElementById('preview'); wrap.innerHTML='';
  [...e.target.files].forEach(f=>{ const rd=new FileReader(); rd.onload=()=>{ const img=new Image(); img.src=rd.result; img.className='w-full h-24 object-cover rounded border'; wrap.appendChild(img); }; rd.readAsDataURL(f); });
});
</script>
