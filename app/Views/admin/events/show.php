<?php use App\Core\View; $base=rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<h1 class="text-2xl font-semibold mb-2">#<?php echo (int)$event['id']; ?> — <?php echo View::e($event['name']); ?></h1>
<p class="text-slate-500 mb-2"><?php echo View::e($event['event_date']); ?> · <?php echo (int)$event['price']; ?> €</p>
<p class="mb-3"><?php echo nl2br(View::e($event['description'])); ?></p>
<?php if (!empty($event['photo_path'])): ?><img src="<?php echo $base . View::e($event['photo_path']); ?>" class="rounded w-64 mb-3"/><?php endif; ?>
<div class="mt-3 flex flex-wrap gap-3 items-center">
  <a class="px-3 py-2 rounded bg-amber-500 text-white" href="<?php echo $base; ?>/admin/events/<?php echo (int)$event['id']; ?>/edit">Éditer</a>
  <form action="<?php echo $base; ?>/admin/events/<?php echo (int)$event['id']; ?>/delete" method="post" onsubmit="return confirm('Supprimer cet événement ?');">
    <?php echo App\Core\CSRF::input(); ?>
    <button class="px-3 py-2 rounded bg-red-600 text-white">Supprimer</button>
  </form>
  <a class="px-3 py-2 rounded bg-slate-200 dark:bg-slate-800" href="<?php echo $base; ?>/events/<?php echo (int)$event['id']; ?>">Voir la page publique</a>
  <a class="px-3 py-2 rounded bg-slate-900 text-white dark:bg-white dark:text-slate-900" href="<?php echo $base; ?>/events/<?php echo (int)$event['id']; ?>/flyer.pdf">Flyer PDF</a>
</div>

<?php
$photos = [];
if (!empty($event['photo_path'])) $photos[] = $event['photo_path'];
$extra = \App\Models\EventPhoto::listByEvent((int)$event['id']);
foreach ($extra as $ph) { $photos[] = $ph['path']; }
if ($photos): ?>
<div class="mt-4 grid grid-cols-3 gap-2">
  <?php foreach ($photos as $p): ?>
    <img src="<?php echo $base . App\Core\View::e($p); ?>" class="rounded w-full object-cover max-h-40"/>
  <?php endforeach; ?>
</div>
<?php endif; ?>
